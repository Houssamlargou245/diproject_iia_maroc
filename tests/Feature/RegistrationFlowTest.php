<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\EditionStatus;
use App\Enums\MembershipStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Mail\OrderStatusChanged;
use App\Models\Cart;
use App\Models\Country;
use App\Models\Edition;
use App\Models\Membership;
use App\Models\Order;
use App\Models\Payment;
use App\Models\TicketType;
use App\Models\User;
use App\Services\Payment\CmiGateway;
use App\Services\Payment\CmiHasher;
use App\Services\Payment\TestGateway;
use App\Services\Registration\CheckoutService;
use App\Services\Registration\InvoiceService;
use App\Support\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\TestCase;

/**
 * The commercial flow, end to end.
 *
 * The 2024 build's payment path could not be tested without moving real
 * money, which is how it shipped with a `callback.php` that wrote to a table
 * the conference flow never populated — so a customer could pay 7 500 MAD and
 * the order stayed 'encours' forever. These tests walk the whole path instead:
 * basket, checkout, signed hand-off, server-to-server callback, settlement,
 * invoice, notification.
 *
 * Each test pins a specific defect the legacy build had, rather than just
 * asserting that a page returns 200.
 */
class RegistrationFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // `Edition::current()` memoises in a static property for the request.
        // In a suite that would hand the first test's edition to every test
        // after it, and the failure would look like a data problem.
        Edition::forgetCurrent();

        // The test driver, so nothing here can reach CMI even if a credential
        // is present in the environment.
        config()->set('cmi.driver', 'test');
    }

    protected function tearDown(): void
    {
        Edition::forgetCurrent();

        parent::tearDown();
    }

    // --- The basket -------------------------------------------------------

    public function test_a_ticket_can_be_added_to_the_basket(): void
    {
        [$ticket] = $this->seedConference();

        // Signed out, which is where most visitors start. The 2024 basket was
        // only reachable once logged in, so the pricing page's buttons did
        // nothing for anyone who had not already registered.
        $this->post('/panier', [
            'ticket_type_id' => $ticket->getKey(),
            'quantity' => 2,
            'member_quantity' => 0,
        ])->assertRedirect(route('cart'));

        $this->assertSame(2, (int) Cart::query()->firstOrFail()->items()->sum('quantity'));
    }

    public function test_a_signed_in_visitor_keeps_their_basket_across_requests(): void
    {
        [$ticket, $user] = $this->seedConference(withUser: true);

        $this->actingAs($user)->post('/panier', [
            'ticket_type_id' => $ticket->getKey(),
            'quantity' => 2,
            'member_quantity' => 0,
        ])->assertRedirect(route('cart'));

        // The basket belongs to the account, not the browser session, so it
        // has to still be there on the next request — and it has to still be
        // there from a different session, which is what happens when someone
        // assembles a group order on a phone and pays from a laptop.
        $this->actingAs($user)->get('/panier')->assertOk();
        $this->actingAs($user)->get(route('cart'))->assertOk();

        $this->assertSame(2, (int) Cart::query()->firstOrFail()->items()->sum('quantity'));
    }

    public function test_a_second_add_replaces_the_line_rather_than_adding_one(): void
    {
        [$ticket, $user] = $this->seedConference(withUser: true);

        $payload = ['ticket_type_id' => $ticket->getKey(), 'member_quantity' => 0];

        $this->actingAs($user)->post('/panier', $payload + ['quantity' => 2]);
        $this->actingAs($user)->post('/panier', $payload + ['quantity' => 5]);

        // Two rows for one tariff would be summed at checkout into a quantity
        // the buyer never chose.
        $cart = Cart::query()->firstOrFail();
        $this->assertSame(1, $cart->items()->count());
        $this->assertSame(5, (int) $cart->items()->sum('quantity'));
    }

    public function test_a_withdrawn_ticket_cannot_be_added(): void
    {
        [$ticket, $user] = $this->seedConference(withUser: true);
        $ticket->forceFill(['is_active' => false])->save();

        $this->actingAs($user)->post('/panier', [
            'ticket_type_id' => $ticket->getKey(),
            'quantity' => 1,
            'member_quantity' => 0,
        ])->assertSessionHasErrors('ticket_type_id');

        $this->assertDatabaseCount('cart_items', 0);
    }

    public function test_a_claim_cannot_exceed_the_quantity(): void
    {
        [$ticket, $user] = $this->seedConference(withUser: true);

        $this->actingAs($user)->post('/panier', [
            'ticket_type_id' => $ticket->getKey(),
            'quantity' => 1,
            'member_quantity' => 3,
        ])->assertSessionHasErrors('member_quantity');
    }

    // --- Pricing integrity ------------------------------------------------

    public function test_the_member_rate_is_granted_only_to_an_active_member(): void
    {
        [$ticket] = $this->seedConference();

        $cart = $this->basket($ticket, quantity: 4, memberQuantity: 4);
        $quote = app(CheckoutService::class)->quote($cart, $this->verifiedUser());

        // The registrant claimed all four were members. Without a membership
        // record every one is priced at the standard rate. The 2024 form
        // trusted a hidden `adherent1` checkbox, which is how the member rate
        // was obtainable by anyone willing to open the page source.
        $this->assertSame(0, $quote->memberCount);
        $this->assertSame(4, $quote->standardCount);
        $this->assertSame(4 * 850000, $quote->total);

        $member = $this->verifiedUser();
        $this->grantMembership($member);

        $memberQuote = app(CheckoutService::class)->quote($cart->fresh(), $member);

        $this->assertSame(4, $memberQuote->memberCount);
        $this->assertSame(4 * 750000, $memberQuote->total);
    }

    public function test_prices_are_snapshotted_so_a_later_price_change_cannot_rewrite_an_invoice(): void
    {
        [$ticket, $user] = $this->seedConference(withUser: true);

        $order = $this->placeOrder($user, quantity: 2, memberQuantity: 0);
        $this->assertSame(2 * 850000, $order->total);

        // Repricing the tariff must not touch the order. The 2024 `facture.php`
        // re-read the live price on every open, so changing the tariff after
        // someone had paid silently rewrote their invoice.
        $ticket->forceFill(['price_standard' => 1200000])->save();

        $order->refresh()->load('items');
        $this->assertSame(2 * 850000, $order->total);
        $this->assertSame(850000, (int) $order->items->first()->unit_price_standard);
    }

    // --- The checkout -----------------------------------------------------

    public function test_the_checkout_requires_a_signed_in_verified_account(): void
    {
        [$ticket] = $this->seedConference();

        // No basket at all: the checkout sends the visitor back to the tariffs
        // rather than showing an empty form they cannot complete.
        $this->get('/inscription')->assertRedirect(route('pricing'));

        // Signed in but the authenticator is not confirmed: sent to enrolment,
        // with a reason, rather than bounced to a bare 403.
        $unverified = User::factory()->unconfirmedTotp()->create();
        $this->actingAs($unverified)->post('/panier', [
            'ticket_type_id' => $ticket->getKey(),
            'quantity' => 1,
            'member_quantity' => 0,
        ]);

        $this->actingAs($unverified)->get('/inscription')
            ->assertRedirect(route('totp.setup'));
    }

    public function test_a_tampered_participant_count_is_refused(): void
    {
        [$ticket, $user] = $this->seedConference(withUser: true);

        $this->actingAs($user)->post('/panier', [
            'ticket_type_id' => $ticket->getKey(),
            'quantity' => 2,
            'member_quantity' => 0,
        ]);

        // Two places bought, one participant submitted. The 2024 form read the
        // count straight from a posted field.
        $this->actingAs($user)
            ->post('/inscription', $this->checkoutPayload(participants: 1))
            ->assertSessionHasErrors('participants');

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_placing_an_order_empties_the_basket(): void
    {
        [$ticket, $user] = $this->seedConference(withUser: true);

        $this->actingAs($user)->post('/panier', [
            'ticket_type_id' => $ticket->getKey(),
            'quantity' => 2,
            'member_quantity' => 0,
        ]);

        $this->actingAs($user)->post('/inscription', $this->checkoutPayload(participants: 2))
            ->assertRedirect();

        $this->assertDatabaseCount('orders', 1);
        $this->assertDatabaseCount('participants', 2);

        // The legacy bug: one stray 'encours' row permanently blocked that
        // customer from ever ordering again.
        $this->assertDatabaseCount('carts', 0);
        $this->assertDatabaseCount('cart_items', 0);
    }

    public function test_registration_is_refused_once_the_venue_is_full(): void
    {
        config()->set('conference.capacity', 1);

        [$ticket] = $this->seedConference();

        $taken = $this->placeOrder($this->verifiedUser(), quantity: 1, memberQuantity: 0);
        $taken->transitionTo(OrderStatus::Paid);

        $next = $this->verifiedUser();

        $this->actingAs($next)->post('/panier', [
            'ticket_type_id' => $ticket->getKey(),
            'quantity' => 1,
            'member_quantity' => 0,
        ]);

        $this->actingAs($next)->post('/inscription', $this->checkoutPayload(participants: 1))
            ->assertSessionHasErrors('participants');

        $this->assertDatabaseCount('orders', 1);
    }

    // --- Adding and removing places from the checkout ----------------------
    //
    // The participant count is not a free choice: it is the number of seats the
    // basket holds, so "add a participant" on the checkout page means "buy
    // another place". Each test below pins one way that can go wrong.

    public function test_a_place_can_be_added_from_the_checkout(): void
    {
        [$ticket, $user] = $this->seedConference(withUser: true);

        $this->actingAs($user)->post('/panier', [
            'ticket_type_id' => $ticket->getKey(),
            'quantity' => 2,
            'member_quantity' => 0,
        ]);

        $this->actingAs($user)
            ->post(route('checkout.seats'), ['adjust' => '+'.$ticket->getKey()])
            ->assertRedirect();

        $this->assertSame(3, (int) Cart::query()->firstOrFail()->items()->sum('quantity'));

        // The point of the feature: the extra seat has to come back as an extra
        // name field. A count that moved without a field to type into would
        // leave the buyer on a form `store()` still refuses to accept.
        $this->actingAs($user)->get('/inscription')
            ->assertOk()
            ->assertSee('participants[2][full_name]', false)
            // The control itself, aimed at this tariff and at its own route
            // rather than at the order: the buttons share the buyer's form, so
            // the tariff and the intent travel in the button's own value.
            ->assertSee('value="+'.$ticket->getKey().'"', false)
            ->assertSee(route('checkout.seats'), false);
    }

    public function test_a_place_can_be_removed_from_the_checkout(): void
    {
        [$ticket, $user] = $this->seedConference(withUser: true);

        $this->actingAs($user)->post('/panier', [
            'ticket_type_id' => $ticket->getKey(),
            'quantity' => 3,
            'member_quantity' => 0,
        ]);

        $this->actingAs($user)
            ->post(route('checkout.seats'), ['adjust' => '-'.$ticket->getKey()])
            ->assertRedirect();

        $this->assertSame(2, (int) Cart::query()->firstOrFail()->items()->sum('quantity'));

        $this->actingAs($user)->get('/inscription')
            ->assertOk()
            ->assertSee('participants[1][full_name]', false)
            ->assertDontSee('participants[2][full_name]', false);
    }

    public function test_the_last_place_on_a_tariff_cannot_be_removed(): void
    {
        [$ticket, $user] = $this->seedConference(withUser: true);

        $this->actingAs($user)->post('/panier', [
            'ticket_type_id' => $ticket->getKey(),
            'quantity' => 1,
            'member_quantity' => 0,
        ]);

        // Removing the last place would empty the basket, which the checkout
        // treats as no order at all. The refusal is a message, not a deletion.
        $this->actingAs($user)
            ->post(route('checkout.seats'), ['adjust' => '-'.$ticket->getKey()])
            ->assertRedirect()
            ->assertSessionHasErrors('participants');

        $this->assertSame(1, (int) Cart::query()->firstOrFail()->items()->sum('quantity'));
    }

    public function test_adding_a_place_raises_the_quoted_total(): void
    {
        [$ticket, $user] = $this->seedConference(withUser: true);

        $this->actingAs($user)->post('/panier', [
            'ticket_type_id' => $ticket->getKey(),
            'quantity' => 2,
            'member_quantity' => 0,
        ]);

        $before = app(CheckoutService::class)
            ->quote(Cart::query()->firstOrFail(), $user)
            ->total;

        $this->actingAs($user)
            ->post(route('checkout.seats'), ['adjust' => '+'.$ticket->getKey()]);

        $after = app(CheckoutService::class)
            ->quote(Cart::query()->firstOrFail(), $user)
            ->total;

        // One more standard place, and nothing else: the "+" is a sale, so the
        // figure the buyer is about to be charged has to move with it.
        $this->assertSame($before + $ticket->price_standard, $after);
    }

    public function test_an_added_place_is_standard_rated_when_the_member_places_are_used_up(): void
    {
        [$ticket, $user] = $this->seedConference(withUser: true);
        $this->grantMembership($user);

        // Two member places declared and two bought.
        $this->actingAs($user)->post('/panier', [
            'ticket_type_id' => $ticket->getKey(),
            'quantity' => 2,
            'member_quantity' => 2,
        ]);

        $this->actingAs($user)
            ->post(route('checkout.seats'), ['adjust' => '+'.$ticket->getKey()]);

        $quote = app(CheckoutService::class)->quote(Cart::query()->firstOrFail(), $user);

        // The third seat is a colleague, not a member, and must be priced at the
        // standard rate. Silently extending the member rate to cover it would be
        // the 2024 build's bug: one member buying a group at a member price.
        $this->assertSame(2, $quote->memberCount);
        $this->assertSame(1, $quote->standardCount);
        $this->assertSame(
            ($ticket->price_member * 2) + $ticket->price_standard,
            $quote->total,
        );
    }

    public function test_a_place_cannot_be_added_once_the_venue_is_full(): void
    {
        config()->set('conference.capacity', 1);

        [$ticket, $user] = $this->seedConference(withUser: true);

        $this->placeOrder($this->verifiedUser(), quantity: 1, memberQuantity: 0)
            ->transitionTo(OrderStatus::Paid);

        $this->actingAs($user)->post('/panier', [
            'ticket_type_id' => $ticket->getKey(),
            'quantity' => 1,
            'member_quantity' => 0,
        ]);

        // The cap has to hold while the buyer is still adding names, not only
        // when the order is finally placed — by then the form is filled in and a
        // refusal discards it.
        $this->actingAs($user)
            ->post(route('checkout.seats'), ['adjust' => '+'.$ticket->getKey()])
            ->assertSessionHasErrors('participants');

        $this->assertSame(1, (int) Cart::query()->firstOrFail()->items()->sum('quantity'));
    }

    public function test_an_adjustment_cannot_reach_a_tariff_that_is_not_in_the_basket(): void
    {
        [$ticket, $user] = $this->seedConference(withUser: true);

        $this->actingAs($user)->post('/panier', [
            'ticket_type_id' => $ticket->getKey(),
            'quantity' => 2,
            'member_quantity' => 0,
        ]);

        // The sign and the tariff arrive in one posted field, so a hand-built
        // request names whatever tariff it likes. It has to be refused rather
        // than quietly ignored: a "+" that appears to work and changes nothing
        // is worse than one that is visibly refused.
        $this->actingAs($user)
            ->post(route('checkout.seats'), ['adjust' => '+'.($ticket->getKey() + 1000)])
            ->assertSessionHasErrors('participants');

        $this->assertSame(2, (int) Cart::query()->firstOrFail()->items()->sum('quantity'));
    }

    public function test_a_malformed_adjustment_is_refused(): void
    {
        [$ticket, $user] = $this->seedConference(withUser: true);

        $this->actingAs($user)->post('/panier', [
            'ticket_type_id' => $ticket->getKey(),
            'quantity' => 2,
            'member_quantity' => 0,
        ]);

        $this->actingAs($user)
            ->post(route('checkout.seats'), ['adjust' => 'quantity=9'])
            ->assertSessionHasErrors('adjust');

        $this->assertSame(2, (int) Cart::query()->firstOrFail()->items()->sum('quantity'));
    }

    // --- The same change without a reload ----------------------------------
    //
    // The script in design.js only repaints regions the server sends back, so
    // what matters here is that the JSON carries the same rendered markup the
    // full page would have produced. A JSON path that drifted from the redirect
    // path would mean the no-reload buyer is filling in a different form.

    public function test_a_seat_change_answers_json_with_the_regions_to_repaint(): void
    {
        [$ticket, $user] = $this->seedConference(withUser: true);

        $this->actingAs($user)->post('/panier', [
            'ticket_type_id' => $ticket->getKey(),
            'quantity' => 2,
            'member_quantity' => 0,
        ]);

        $response = $this->actingAs($user)
            ->postJson(route('checkout.seats'), [
                'adjust' => '+'.$ticket->getKey(),
                // The names already typed, which is what a real click sends.
                'participants' => [
                    ['full_name' => 'Ahmed Benali'],
                    ['full_name' => 'Sara Idrissi'],
                ],
            ])
            ->assertOk()
            ->assertJson(['ok' => true, 'count' => 3]);

        $payload = $response->json();

        // The extra seat arrives as a third name field...
        $this->assertStringContainsString('participants[2][full_name]', $payload['participants']);

        // ...the two that were typed are still in it...
        $this->assertStringContainsString('value="Ahmed Benali"', $payload['participants']);
        $this->assertStringContainsString('value="Sara Idrissi"', $payload['participants']);

        // ...and the total in the summary is the re-quoted one, not the old
        // figure carried over in the browser.
        $this->assertStringContainsString(
            Money::format($ticket->price_standard * 3, $ticket->currency),
            $payload['summary'],
        );

        // The stepper comes back as data so the script can correct which button
        // is available rather than counting up on its own.
        $this->assertSame(3, $payload['seats'][0]['quantity']);
        $this->assertTrue($payload['seats'][0]['can_add']);
        $this->assertTrue($payload['seats'][0]['can_remove']);
    }

    public function test_a_json_seat_change_reports_a_refusal_without_reloading(): void
    {
        config()->set('conference.capacity', 1);

        [$ticket, $user] = $this->seedConference(withUser: true);

        $this->placeOrder($this->verifiedUser(), quantity: 1, memberQuantity: 0)
            ->transitionTo(OrderStatus::Paid);

        $this->actingAs($user)->post('/panier', [
            'ticket_type_id' => $ticket->getKey(),
            'quantity' => 1,
            'member_quantity' => 0,
        ]);

        $payload = $this->actingAs($user)
            ->postJson(route('checkout.seats'), ['adjust' => '+'.$ticket->getKey()])
            ->assertOk()
            ->assertJson(['ok' => false])
            ->json();

        // A refusal has to read as a refusal with scripting on: `ok: false` plus
        // the reason, and the regions still present so the script can repaint
        // the disabled state rather than leaving a lit button that does nothing.
        $this->assertNotEmpty($payload['message']);
        $this->assertArrayHasKey('seats', $payload);
        $this->assertSame(1, $payload['seats'][0]['quantity']);
        $this->assertFalse($payload['seats'][0]['can_add']);
    }

    public function test_the_last_place_reports_itself_as_unavailable_to_the_script(): void
    {
        [$ticket, $user] = $this->seedConference(withUser: true);

        $this->actingAs($user)->post('/panier', [
            'ticket_type_id' => $ticket->getKey(),
            'quantity' => 1,
            'member_quantity' => 0,
        ]);

        $payload = $this->actingAs($user)
            ->postJson(route('checkout.seats'), ['adjust' => '-'.$ticket->getKey()])
            ->assertOk()
            ->assertJson(['ok' => false])
            ->json();

        $this->assertFalse($payload['seats'][0]['can_remove']);
    }

    // --- The payment ------------------------------------------------------

    public function test_the_checkout_hands_a_signed_payload_to_the_gateway(): void
    {
        [$ticket, $user] = $this->seedConference(withUser: true);

        $this->actingAs($user)->post('/panier', [
            'ticket_type_id' => $ticket->getKey(),
            'quantity' => 1,
            'member_quantity' => 0,
        ]);
        $this->actingAs($user)->post('/inscription', $this->checkoutPayload(participants: 1));

        $order = Order::query()->firstOrFail();

        $response = $this->actingAs($user)->get(route('checkout.pay', ['order' => $order->getKey()]));
        $response->assertOk();

        // Asserting the signature rather than just the presence of a form is
        // the point: a form with a wrong hash fails only at the bank, with no
        // indication of why.
        $content = (string) $response->getContent();
        $this->assertStringContainsString('name="HASH"', $content);
        $this->assertStringContainsString('name="oid"', $content);
        $this->assertStringContainsString($order->uuid, $content);

        // CMI reads a missing session lifetime as a zero-length session and
        // shows "Transaction expiré. Merci d'essayer à nouveau (99)" the moment
        // the payment page opens. `refreshtime` was never part of the contract.
        $this->assertStringContainsString('name="sessionTimeout" value="1800"', $content);
        $this->assertStringContainsString('name="callbackResponse"', $content);
        $this->assertStringContainsString('name="autoRedirect"', $content);
        $this->assertStringNotContainsString('name="refreshtime"', $content);

        $this->assertSame(OrderStatus::AwaitingPayment, $order->refresh()->status);
    }

    public function test_the_server_to_server_callback_settles_the_order_and_issues_an_invoice(): void
    {
        Storage::fake('local');
        Mail::fake();

        [$ticket, $user] = $this->seedConference(withUser: true);
        $order = $this->placeOrder($user, quantity: 2, memberQuantity: 0);

        $response = $this->post('/payment/cmi/callback', $this->signedCallback($order, approved: true));

        // CMI's protocol, not HTML. `ACTION=POSTAUTH` asks the gateway to
        // capture the PreAuth — the step the 2024 build configured but never
        // actually triggered, leaving authorised funds uncaptured.
        $response->assertOk();
        $this->assertSame('ACTION=POSTAUTH', $response->getContent());

        $order->refresh();

        $this->assertSame(OrderStatus::Paid, $order->status);
        $this->assertNotNull($order->paid_at);
        $this->assertNotNull($order->invoice_number);
        $this->assertNotNull($order->invoice_path);
        Storage::disk('local')->assertExists($order->invoice_path);

        $this->assertDatabaseHas('payments', [
            'order_id' => $order->getKey(),
            'status' => PaymentStatus::Authorised->value,
        ]);

        Mail::assertQueued(OrderStatusChanged::class);
    }

    public function test_a_callback_settles_when_the_oid_carries_a_per_attempt_suffix(): void
    {
        Storage::fake('local');
        Mail::fake();

        [$ticket, $user] = $this->seedConference(withUser: true);
        $order = $this->placeOrder($user, quantity: 1, memberQuantity: 0);

        // CMI rejects a reused `oid`, so the real driver submits
        // `<uuid>-<hex>`. The order is still keyed by the leading uuid, and a
        // callback that only matched the raw `oid` exactly would leave every
        // payment unsettled.
        $payload = $this->signedCallback($order, approved: true);
        unset($payload['HASH']);
        $payload['oid'] = $order->uuid.'-'.dechex(time());
        $payload['HASH'] = (new CmiHasher)->hash($payload, $this->storeKey());

        $this->post('/payment/cmi/callback', $payload)->assertOk();

        $this->assertSame(OrderStatus::Paid, $order->refresh()->status);
    }

    public function test_a_callback_with_a_bad_signature_is_rejected_and_settles_nothing(): void
    {
        [$ticket, $user] = $this->seedConference(withUser: true);
        $order = $this->placeOrder($user, quantity: 1, memberQuantity: 0);

        $payload = $this->signedCallback($order, approved: true);
        $payload['HASH'] = strrev($payload['HASH']);

        $response = $this->post('/payment/cmi/callback', $payload);

        $this->assertSame('FAILURE', $response->getContent());

        // The core fix. The 2024 callback compared hashes with `==` and, on a
        // mismatch, wrote to an unrelated table — so the order stayed unpaid
        // and a forged payload was never distinguished from a real one.
        $this->assertSame(OrderStatus::Pending, $order->refresh()->status);
        $this->assertDatabaseCount('payments', 0);
    }

    public function test_a_tampered_amount_cannot_settle_an_order(): void
    {
        [$ticket, $user] = $this->seedConference(withUser: true);
        $order = $this->placeOrder($user, quantity: 2, memberQuantity: 0);

        // A genuine CMI signature over a payload claiming a fraction of the
        // price. This is the attack the amount cross-check exists to stop:
        // without it, 100 MAD would buy a 7 500 MAD place.
        $this->post('/payment/cmi/callback', $this->signedCallback($order, approved: true, overrideAmount: 100));

        $this->assertNotSame(OrderStatus::Paid, $order->refresh()->status);
    }

    public function test_a_replayed_callback_does_not_capture_twice(): void
    {
        Storage::fake('local');
        Mail::fake();

        [$ticket, $user] = $this->seedConference(withUser: true);
        $order = $this->placeOrder($user, quantity: 1, memberQuantity: 0);

        $payload = $this->signedCallback($order, approved: true);

        $this->post('/payment/cmi/callback', $payload)->assertOk();
        $this->post('/payment/cmi/callback', $payload)->assertOk();

        // CMI's own documentation warns that a rejection can be followed by an
        // acceptance for the same order. The unique idempotency key is what
        // makes the second call a no-op rather than a second capture — and the
        // customer is told once, not twice.
        $this->assertSame(1, Payment::query()->where('order_id', $order->getKey())->count());
        Mail::assertQueued(OrderStatusChanged::class, 1);
    }

    public function test_a_declined_payment_marks_the_order_failed(): void
    {
        [$ticket, $user] = $this->seedConference(withUser: true);
        $order = $this->placeOrder($user, quantity: 1, memberQuantity: 0);

        $this->post('/payment/cmi/callback', $this->signedCallback($order, approved: false));

        $this->assertSame(OrderStatus::Failed, $order->refresh()->status);
        $this->assertDatabaseHas('payments', [
            'order_id' => $order->getKey(),
            'status' => PaymentStatus::Failed->value,
        ]);
    }

    public function test_the_browser_return_does_not_settle_on_its_own(): void
    {
        [$ticket, $user] = $this->seedConference(withUser: true);
        $order = $this->placeOrder($user, quantity: 1, memberQuantity: 0);

        // A forged return URL: correctly signed, but the customer can close the
        // tab before the real callback lands and the URL is trivially
        // reproducible. Only the server-to-server callback may settle.
        $this->actingAs($user)->get(
            route('payment.return', ['order' => $order->getKey()]),
            $this->signedCallback($order, approved: true),
        );

        $this->assertNotSame(OrderStatus::Paid, $order->refresh()->status);
    }

    public function test_a_pending_payment_is_only_successful_with_the_gateways_own_evidence(): void
    {
        [$ticket, $user] = $this->seedConference(withUser: true);
        $order = $this->placeOrder($user, quantity: 1, memberQuantity: 0);

        // A bare `pending` row is an unfinished checkout, not a payment.
        $bare = $this->recordPayment($order, ['status' => PaymentStatus::Pending->value]);
        $this->assertFalse($bare->isSuccessful());

        // The authorisation code and transaction id are the gateway's own proof
        // that the money moved, even when the status never advanced past it.
        $proven = $this->recordPayment($order, [
            'status' => PaymentStatus::Pending->value,
            'auth_code' => '397437',
            'gateway_transaction_id' => '26292O8XA12513855',
        ]);
        $this->assertTrue($proven->isSuccessful());

        $this->assertSame(1, Payment::query()->successful()->count());
    }

    public function test_an_authorised_order_reads_as_paid_even_before_the_callback_settles_it(): void
    {
        [$ticket, $user] = $this->seedConference(withUser: true);
        $order = $this->placeOrder($user, quantity: 1, memberQuantity: 0);

        // The browser return recorded the authorisation; the server-to-server
        // callback that would have settled the order never landed, so the stored
        // status is still an in-progress one.
        $this->recordPayment($order, [
            'auth_code' => '397437',
            'gateway_transaction_id' => '26292O8XA12513855',
        ]);

        $order->refresh();

        $this->assertNotSame(OrderStatus::Paid, $order->status);
        $this->assertSame(OrderStatus::Paid, $order->effectiveStatus());

        // A cancelled order stays cancelled: that is a deliberate terminal
        // state, not a callback that failed to arrive.
        $order->transitionTo(OrderStatus::Cancelled);
        $this->assertSame(OrderStatus::Cancelled, $order->refresh()->effectiveStatus());
    }

    public function test_the_reconcile_command_settles_a_stuck_authorised_order(): void
    {
        Storage::fake('local');
        Mail::fake();

        [$ticket, $user] = $this->seedConference(withUser: true);
        $order = $this->placeOrder($user, quantity: 1, memberQuantity: 0);

        $this->recordPayment($order, [
            'auth_code' => '397437',
            'gateway_transaction_id' => '26292O8XA12513855',
        ]);

        // A dry run reports but writes nothing: settling is a decision the
        // operator gets to look at first.
        $this->artisan('orders:reconcile')->assertSuccessful();
        $this->assertNotSame(OrderStatus::Paid, $order->refresh()->status);

        // The explicit run settles the order and issues the invoice.
        $this->artisan('orders:reconcile', ['--apply' => true])->assertSuccessful();

        $order->refresh();

        $this->assertSame(OrderStatus::Paid, $order->status);
        $this->assertNotNull($order->paid_at);
        $this->assertNotNull($order->invoice_number);
        Mail::assertQueued(OrderStatusChanged::class);
    }

    public function test_a_callback_for_an_unknown_order_is_refused(): void
    {
        $this->post('/payment/cmi/callback', $this->signedCallbackForUuid('00000000-0000-4000-8000-000000000000'));

        $this->assertDatabaseCount('orders', 0);
    }

    // --- Orders and authorisation ----------------------------------------

    public function test_a_visitor_cannot_read_or_cancel_someone_elses_order(): void
    {
        [$ticket, $owner] = $this->seedConference(withUser: true);
        $order = $this->placeOrder($owner, quantity: 1, memberQuantity: 0);
        $order->transitionTo(OrderStatus::Paid);

        $intruder = $this->verifiedUser();

        // `mescommandes.php` listed orders by session but took the id for the
        // invoice and the cancellation from `$_POST['id']` with no ownership
        // test, so any signed-in visitor could open and void anyone's order
        // and read their participants' names and phone numbers.
        //
        // A 404 rather than a 403: the order is looked up inside the buyer's
        // own relation, so "not yours" and "no such order" are the same
        // answer. A 403 would confirm the reference is real, which is enough
        // to turn the endpoint into an oracle for enumerating live orders.
        $this->actingAs($intruder)->get(route('orders.show', ['order' => $order->getKey()]))
            ->assertNotFound();

        $this->actingAs($intruder)->post(route('orders.cancel', ['order' => $order->getKey()]))
            ->assertNotFound();

        $this->actingAs($intruder)->post(route('orders.invoice', ['order' => $order->getKey()]))
            ->assertNotFound();

        $this->assertSame(OrderStatus::Paid, $order->refresh()->status);
    }

    public function test_a_paid_order_cannot_be_cancelled(): void
    {
        [$ticket, $user] = $this->seedConference(withUser: true);
        $order = $this->placeOrder($user, quantity: 1, memberQuantity: 0);
        $order->transitionTo(OrderStatus::Paid);

        $this->actingAs($user)->post(route('orders.cancel', ['order' => $order->getKey()]))
            ->assertSessionHasErrors('status');

        $this->assertSame(OrderStatus::Paid, $order->refresh()->status);
    }

    public function test_an_unpaid_order_can_be_cancelled(): void
    {
        [$ticket, $user] = $this->seedConference(withUser: true);
        $order = $this->placeOrder($user, quantity: 1, memberQuantity: 0);

        $this->actingAs($user)->post(route('orders.cancel', ['order' => $order->getKey()]))
            ->assertRedirect(route('orders.index'));

        $this->assertSame(OrderStatus::Cancelled, $order->refresh()->status);
    }

    public function test_the_invoice_is_only_issued_once_the_order_is_settled(): void
    {
        [$ticket, $user] = $this->seedConference(withUser: true);
        $order = $this->placeOrder($user, quantity: 1, memberQuantity: 0);

        $this->actingAs($user)->post(route('orders.invoice', ['order' => $order->getKey()]))
            ->assertSessionHasErrors('invoice');

        $this->assertNull($order->refresh()->invoice_path);
    }

    public function test_a_paid_order_serves_a_pdf_invoice(): void
    {
        Storage::fake('local');

        [$ticket, $user] = $this->seedConference(withUser: true);
        $order = $this->placeOrder($user, quantity: 1, memberQuantity: 0);
        $order->transitionTo(OrderStatus::Paid);
        app(InvoiceService::class)->generate($order);

        $response = $this->actingAs($user)
            ->post(route('orders.invoice', ['order' => $order->getKey()]));

        $response->assertOk();
        $this->assertStringContainsString(
            'application/pdf',
            (string) $response->headers->get('content-type')
        );
    }

    public function test_the_order_history_shows_only_the_visitors_own_orders(): void
    {
        [$ticket, $mine] = $this->seedConference(withUser: true);
        $mineOrder = $this->placeOrder($mine, quantity: 1, memberQuantity: 0);

        $otherOrder = $this->placeOrder($this->verifiedUser(), quantity: 1, memberQuantity: 0);

        $response = $this->actingAs($mine)->get(route('orders.index'));

        $response->assertOk();
        $response->assertSee($mineOrder->reference);
        $response->assertDontSee($otherOrder->reference);
    }

    public function test_the_basket_links_to_the_payment_history_only_for_a_buyer_who_has_orders(): void
    {
        [$ticket, $user] = $this->seedConference(withUser: true);

        // The label, not the URL: the header already links to the same route,
        // so a URL assertion would pass without this change.
        $label = __('order.cart.my_payments');

        // A visitor has no history to look at.
        $this->get(route('cart'))->assertOk()->assertDontSee($label);

        // Neither does a signed-in buyer who has not ordered yet.
        $this->actingAs($user)->get(route('cart'))->assertOk()->assertDontSee($label);

        // Once there is an order, the basket points at it.
        $this->placeOrder($user, quantity: 1, memberQuantity: 0);

        $this->actingAs($user)->get(route('cart'))->assertOk()->assertSee($label);
    }

    // --- The CMI hash itself ----------------------------------------------

    public function test_the_cmi_hash_excludes_hash_and_encoding_and_sorts_naturally(): void
    {
        $hasher = new CmiHasher;

        $canonical = $hasher->canonicalise([
            'item10' => 'ten',
            'item9' => 'nine',
            'HASH' => 'ignored',
            'encoding' => 'UTF-8',
        ], 'secret');

        // natcasesort, not ksort: "item9" must precede "item10", and "HASH"
        // must be excluded case-insensitively. Both are things a plausible
        // reimplementation gets wrong, and the failure is a signature the
        // gateway rejects with no explanation.
        $this->assertSame('nine|ten|secret', $canonical);
    }

    public function test_a_pipe_in_a_value_cannot_forge_a_field_boundary(): void
    {
        $hasher = new CmiHasher;

        // Escaping matters: an unescaped `|` in a name would let a caller
        // shift a value across a field boundary and produce a valid-looking
        // hash over a different transaction.
        $this->assertSame('a\|b|secret', $hasher->canonicalise(['x' => 'a|b'], 'secret'));
    }

    public function test_verification_rejects_a_wrong_key_and_a_missing_hash(): void
    {
        $hasher = new CmiHasher;
        $params = ['oid' => 'abc', 'amount' => '7500'];
        $hash = $hasher->hash($params, 'secret');

        $this->assertTrue($hasher->verify($params, 'secret', $hash));
        $this->assertFalse($hasher->verify($params, 'wrong-secret', $hash));
        $this->assertFalse($hasher->verify($params, 'secret', null));
        $this->assertFalse($hasher->verify($params, 'secret', ''));
    }

    public function test_the_real_gateway_refuses_to_run_without_credentials(): void
    {
        config()->set('cmi.merchant_id', null);
        config()->set('cmi.store_key', null);

        $this->expectException(RuntimeException::class);

        app(CmiGateway::class)->assertConfigured();
    }

    public function test_the_real_gateway_request_carries_a_session_timeout(): void
    {
        config()->set('cmi.merchant_id', 'TESTMERCHANT');
        config()->set('cmi.store_key', 'test-driver-key');

        [, $user] = $this->seedConference(withUser: true);
        $order = $this->placeOrder($user, quantity: 1, memberQuantity: 0);

        $fields = app(CmiGateway::class)
            ->redirectFor($order, 'https://example.test/ok', 'https://example.test/fail')['fields'];

        // CMI reads a missing session lifetime as an already-expired session
        // and turns the customer away with "Transaction expiré (99)" before a
        // card is ever entered. `refreshtime` was never part of the contract.
        $this->assertSame('1800', $fields['sessionTimeout']);
        $this->assertSame('true', $fields['callbackResponse']);
        $this->assertSame('true', $fields['autoRedirect']);
        $this->assertArrayNotHasKey('refreshtime', $fields);
        $this->assertArrayHasKey('HASH', $fields);
    }

    // --- Helpers ----------------------------------------------------------

    /** @return array{0: TicketType, 1: ?User} */
    private function seedConference(bool $withUser = false): array
    {
        Country::query()->create([
            'iso2' => 'MA',
            'iso3' => 'MAR',
            'phone_code' => '+212',
            'name_fr' => 'Maroc',
            'name_en' => 'Morocco',
            'name_ar' => 'المغرب',
            'is_active' => true,
        ]);

        $edition = Edition::query()->create([
            'year' => 2026,
            'code' => 'ARABCIA2026',
            'organiser' => 'ARABCIA',
            'host_institute' => 'IIA Maroc',
            'title' => ['fr' => 'ARABCIA 2026', 'en' => 'ARABCIA 2026', 'ar' => 'ARABCIA 2026'],
            'theme' => ['fr' => 'Thème', 'en' => 'Theme', 'ar' => 'الموضوع'],
            'introduction' => [
                'fr' => 'La conférence ARABCIA 2026.',
                'en' => 'The ARABCIA 2026 conference.',
                'ar' => 'مؤتمر ARABCIA 2026.',
            ],
            'city' => 'Rabat',
            'country_iso2' => 'MA',
            'venue_name' => 'Four Seasons Hotel Rabat',
            'contact_email' => 'contact@arabcia.test',
            'starts_on' => '2026-12-16',
            'ends_on' => '2026-12-17',
            'languages' => ['fr', 'en', 'ar'],
            'status' => EditionStatus::Published,
            'is_current' => true,
            'registration_open' => true,
        ]);

        $ticket = TicketType::factory()->create(['edition_id' => $edition->getKey()]);

        return [$ticket, $withUser ? $this->verifiedUser() : null];
    }

    private function verifiedUser(): User
    {
        return User::factory()->create([
            'totp_secret' => 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567',
            'totp_confirmed_at' => now(),
            'terms_accepted_at' => now(),
            'locale' => 'fr',
        ]);
    }

    private function grantMembership(User $user): void
    {
        Membership::query()->create([
            'user_id' => $user->getKey(),
            'status' => MembershipStatus::Active,
            'reference' => 'MEM-'.$user->getKey(),
        ]);
    }

    private function basket(TicketType $ticket, int $quantity, int $memberQuantity = 0): Cart
    {
        $cart = Cart::query()->create([
            'session_id' => 'test-session-'.uniqid(),
            'expires_at' => now()->addHour(),
        ]);

        $cart->items()->create([
            'ticket_type_id' => $ticket->getKey(),
            'quantity' => $quantity,
            'member_quantity' => $memberQuantity,
        ]);

        return $cart->fresh();
    }

    /** @return array<string, mixed> */
    private function checkoutPayload(int $participants): array
    {
        $list = [];

        for ($i = 0; $i < $participants; $i++) {
            $list[] = [
                'full_name' => 'Participant '.($i + 1),
                'job_title' => 'Auditeur',
                'email' => 'participant'.($i + 1).'@example.test',
                'phone' => '+21260000000'.($i + 1),
                'is_member' => false,
            ];
        }

        return [
            'first_name' => 'Ahmed',
            'last_name' => 'Benali',
            'organisation' => 'BNA',
            'email' => 'ahmed@example.test',
            'phone' => '+212600000001',
            'participants' => $list,
        ];
    }

    private function placeOrder(User $user, int $quantity, int $memberQuantity): Order
    {
        $ticket = TicketType::query()->firstOrFail();
        $cart = $this->basket($ticket, $quantity, $memberQuantity);
        $checkout = app(CheckoutService::class);

        $participants = [];
        for ($i = 0; $i < $quantity; $i++) {
            $participants[] = [
                'full_name' => 'Participant '.($i + 1),
                'job_title' => 'Auditeur',
                'is_member' => $memberQuantity > $i,
            ];
        }

        return $checkout->place(
            cart: $cart,
            user: $user,
            quote: $checkout->quote($cart, $user),
            participants: $participants,
            billing: [
                'first_name' => 'Ahmed',
                'last_name' => 'Benali',
                'email' => 'ahmed@example.test',
                'phone' => '+212600000001',
            ],
        );
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function recordPayment(Order $order, array $attributes = []): Payment
    {
        return Payment::query()->create(array_merge([
            'order_id' => $order->getKey(),
            'driver' => 'cmi',
            'idempotency_key' => 'test-'.bin2hex(random_bytes(8)),
            'status' => PaymentStatus::Pending->value,
            'amount' => $order->total,
            'currency' => $order->currency,
            'currency_numeric' => $order->currency_numeric,
        ], $attributes));
    }

    /** @return array<string, string> */
    private function signedCallback(Order $order, bool $approved, ?int $overrideAmount = null): array
    {
        $payload = app(TestGateway::class)->simulate($order, $approved);

        if ($overrideAmount !== null) {
            unset($payload['HASH']);
            $payload['amount'] = (string) $overrideAmount;
            $payload['HASH'] = (new CmiHasher)->hash($payload, $this->storeKey());
        }

        return $payload;
    }

    /** @return array<string, string> */
    private function signedCallbackForUuid(string $uuid): array
    {
        $payload = [
            'oid' => $uuid,
            'amount' => '7500',
            'ProcReturnCode' => '00',
            'TransId' => 'TESTPROBE',
        ];

        $payload['HASH'] = (new CmiHasher)->hash($payload, $this->storeKey());

        return $payload;
    }

    private function storeKey(): string
    {
        return (string) (config('cmi.store_key') ?: 'test-driver-key');
    }
}
