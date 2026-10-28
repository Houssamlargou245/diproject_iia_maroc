<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Mail\OrderStatusChanged;
use App\Models\Country;
use App\Models\Edition;
use App\Models\Order;
use App\Models\TicketType;
use App\Models\User;
use App\Services\Payment\CmiGateway;
use App\Services\Payment\CmiHasher;
use App\Services\Payment\SettlementPlan;
use App\Support\Money;
use DOMDocument;
use DOMElement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * The CMI driver, from the basket to a paid order.
 *
 * {@see RegistrationFlowTest} walks the same path on the offline test driver.
 * This file runs it on {@see CmiGateway} itself, with
 * CMI's side played from the form the browser would post: the hand-off is
 * read out of the rendered page and checked the way the gateway checks it,
 * then answered the way the gateway answers — every request field echoed
 * back, the verdict added, the whole re-signed with the store key.
 *
 * The credentials are throwaway values and stray HTTP requests are refused, so
 * nothing here can reach CMI even when real keys sit in `.env`.
 */
class CmiPaymentEndToEndTest extends TestCase
{
    use RefreshDatabase;

    private const STORE_KEY = 'e2e-store-key';

    private const GATEWAY_URL = 'https://testpayment.cmi.co.ma/fim/est3Dgate';

    protected function setUp(): void
    {
        parent::setUp();

        Edition::forgetCurrent();
        Http::preventStrayRequests();
        Mail::fake();
        Storage::fake('local');

        config()->set([
            'cmi.driver' => 'cmi',
            'cmi.gateway_url' => self::GATEWAY_URL,
            'cmi.merchant_id' => '600000000',
            'cmi.store_key' => self::STORE_KEY,
            'cmi.trans_type' => 'PreAuth',
        ]);
    }

    protected function tearDown(): void
    {
        Edition::forgetCurrent();

        parent::tearDown();
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function transactionTypes(): array
    {
        return [
            'pre-authorisation is captured' => ['PreAuth', 'ACTION=POSTAUTH'],
            'immediate sale is not captured twice' => ['Auth', 'APPROVED'],
        ];
    }

    #[DataProvider('transactionTypes')]
    public function test_an_approved_payment_settles_the_order_and_issues_the_invoice(string $transactionType, string $expectedReply): void
    {
        config()->set('cmi.trans_type', $transactionType);

        [$user, $order, $request] = $this->checkOut();

        $this->assertSame($transactionType, $request['TranType']);

        $callback = $this->cmiAnswers($request, approved: true);

        $this->post('/payment/cmi/callback', $callback)
            ->assertOk()
            ->assertContent($expectedReply);

        $order->refresh();

        $this->assertSame(OrderStatus::Paid, $order->status);
        $this->assertNotNull($order->paid_at);
        $this->assertNotNull($order->invoice_number);
        Storage::disk('local')->assertExists($order->invoice_path);

        $this->assertDatabaseHas('payments', [
            'order_id' => $order->getKey(),
            'driver' => 'cmi',
            'status' => PaymentStatus::Authorised->value,
            'gateway_transaction_id' => $callback['TransId'],
            'auth_code' => $callback['AuthCode'],
            'return_code' => '00',
        ]);

        Mail::assertSent(OrderStatusChanged::class);

        // The browser lands on okUrl after the callback and is shown the paid
        // order, not the "not received yet" page.
        $this->actingAs($user)
            ->post($request['okUrl'], $callback)
            ->assertRedirect(route('orders.show', ['order' => $order->getKey()]));

        $this->assertDatabaseCount('payments', 1);
    }

    public function test_a_declined_payment_fails_the_order_without_an_invoice(): void
    {
        [$user, $order, $request] = $this->checkOut();

        $this->post('/payment/cmi/callback', $this->cmiAnswers($request, approved: false))
            ->assertOk()
            ->assertContent('APPROVED');

        $order->refresh();

        $this->assertSame(OrderStatus::Failed, $order->status);
        $this->assertNull($order->invoice_number);

        $this->assertDatabaseHas('payments', [
            'order_id' => $order->getKey(),
            'status' => PaymentStatus::Failed->value,
            'return_code' => '51',
        ]);

        $this->actingAs($user)
            ->post($request['failUrl'], $this->cmiAnswers($request, approved: false))
            ->assertOk();
    }

    public function test_a_callback_altered_after_signing_settles_nothing(): void
    {
        [, $order, $request] = $this->checkOut();

        $callback = $this->cmiAnswers($request, approved: true);
        $callback['amount'] = '1.00';

        $this->post('/payment/cmi/callback', $callback)
            ->assertOk()
            ->assertContent('FAILURE');

        $this->assertSame(OrderStatus::AwaitingPayment, $order->refresh()->status);
        $this->assertDatabaseCount('payments', 0);
    }

    /**
     * Basket, checkout and the hand-off page, as a signed-in buyer.
     *
     * Returns the fields of the form the browser would post to CMI, after
     * checking them the way the gateway does on receipt.
     *
     * @return array{User, Order, array<string, string>}
     */
    private function checkOut(): array
    {
        $country = Country::query()->create([
            'iso2' => 'MA',
            'iso3' => 'MAR',
            'phone_code' => '+212',
            'name_fr' => 'Maroc',
            'name_en' => 'Morocco',
            'name_ar' => 'المغرب',
            'is_active' => true,
        ]);

        $edition = Edition::factory()->current()->create(['contact_email' => 'contact@arabcia.test']);
        $ticket = TicketType::factory()->create(['edition_id' => $edition->getKey()]);

        $user = User::factory()->create([
            'totp_secret' => 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567',
            'totp_confirmed_at' => now(),
            'terms_accepted_at' => now(),
            'locale' => 'fr',
        ]);

        $this->actingAs($user)->post('/panier', [
            'ticket_type_id' => $ticket->getKey(),
            'quantity' => 1,
            'member_quantity' => 0,
        ]);

        $this->actingAs($user)->post('/inscription', [
            'first_name' => 'Ahmed',
            'last_name' => 'Benali',
            'organisation' => 'BNA',
            'address' => '12 Avenue Ibn Sina',
            'city' => 'Rabat',
            'country_id' => $country->getKey(),
            'email' => 'ahmed@example.test',
            'phone' => '+212600000001',
            'participants' => [[
                'full_name' => 'Participant 1',
                'job_title' => 'Auditeur',
                'email' => 'participant1@example.test',
                'phone' => '+212600000002',
                'is_member' => false,
            ]],
        ]);

        $order = Order::query()->firstOrFail();

        $page = $this->actingAs($user)->get(route('checkout.pay', ['order' => $order->getKey()]));
        $page->assertOk();

        [$action, $fields] = $this->gatewayForm((string) $page->getContent());

        $this->assertSame(self::GATEWAY_URL, $action);
        $this->assertSame('600000000', $fields['clientid']);
        $this->assertStringStartsWith($order->uuid.'-', $fields['oid']);
        $this->assertSame(url('/payment/cmi/callback'), $fields['callbackUrl']);
        $this->assertSame('MA', $fields['BillToCountry']);

        $settlement = app(SettlementPlan::class)->forOrder($order);
        $this->assertSame(Money::decimal($settlement['minor'], $settlement['currency']), $fields['amount']);
        $this->assertSame((string) Money::numericCode($settlement['currency']), $fields['currency']);

        // What CMI does first: recompute the signature over exactly what
        // arrived. A form that fails here fails at the bank with no reason.
        $this->assertTrue(
            app(CmiHasher::class)->verify($fields, self::STORE_KEY, $fields['HASH']),
            'The hand-off form does not carry a valid CMI signature.',
        );

        $this->assertSame(OrderStatus::AwaitingPayment, $order->refresh()->status);

        return [$user, $order, $fields];
    }

    /**
     * The gateway form as the browser would submit it.
     *
     * @return array{string, array<string, string>}
     */
    private function gatewayForm(string $html): array
    {
        $document = new DOMDocument;
        @$document->loadHTML($html);

        $form = $document->getElementById('gateway-form');
        $this->assertInstanceOf(DOMElement::class, $form, 'The hand-off page has no gateway form.');

        $fields = [];

        foreach ($form->getElementsByTagName('input') as $input) {
            $fields[$input->getAttribute('name')] = $input->getAttribute('value');
        }

        return [$form->getAttribute('action'), $fields];
    }

    /**
     * CMI's answer to a hand-off: the request echoed back with the verdict,
     * re-signed with the store key.
     *
     * @param  array<string, string>  $request
     * @return array<string, string>
     */
    private function cmiAnswers(array $request, bool $approved): array
    {
        $payload = array_merge(
            array_diff_key($request, ['HASH' => true]),
            $approved
                ? [
                    'ProcReturnCode' => '00',
                    'Response' => 'Approved',
                    'mdStatus' => '1',
                    'AuthCode' => 'P12345',
                    'TransId' => '26300ABCD1234',
                    'maskedCreditCard' => '5200 00** **** 1111',
                ]
                : [
                    'ProcReturnCode' => '51',
                    'Response' => 'Declined',
                    'mdStatus' => '1',
                    'ErrMsg' => 'Fonds insuffisants (51)',
                    'TransId' => '26300ABCD5678',
                ],
        );

        $payload['HASH'] = app(CmiHasher::class)->hash($payload, self::STORE_KEY);

        return $payload;
    }
}
