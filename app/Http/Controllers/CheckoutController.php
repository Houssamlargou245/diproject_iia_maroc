<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\Locale;
use App\Enums\OrderStatus;
use App\Http\Controllers\Concerns\ResolvesOwnedOrder;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Edition;
use App\Models\Order;
use App\Models\TicketType;
use App\Models\User;
use App\Services\Payment\PaymentGateway;
use App\Services\Registration\CheckoutService;
use App\Services\Registration\Quote;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use RuntimeException;

/**
 * The checkout: names, then payment.
 *
 * Replaces `inscription.php` and `process_form.php`. The legacy pair had the
 * form in one file and the database writes in another, passed between them
 * through hidden fields â€” which is why a customer could edit a posted `id` and
 * read another person's order, and why a failure between the order insert and
 * the participant inserts left a paid order with nobody on it.
 *
 * Here the buyer names the people the tickets are for, sees the authoritative
 * total, and is sent to the gateway. The order is written once, in a
 * transaction, by CheckoutService.
 */
class CheckoutController extends Controller
{
    use ResolvesOwnedOrder;

    /**
     * Most places one tariff may hold in a single basket.
     *
     * Matches the basket rule in `CartController`, so a buyer cannot add places
     * here that the cart page would refuse to store.
     */
    private const MAX_PLACES_PER_LINE = 20;

    public function __construct(
        private readonly CheckoutService $checkout,
    ) {}

    /**
     * Show the checkout for the current basket.
     */
    public function create(Request $request): View|RedirectResponse
    {
        $cart = $this->currentCart($request);

        if ($cart->items()->doesntExist()) {
            return redirect()->route('pricing')->with('status', __('order.cart_empty'));
        }

        $cart->load(['items.ticketType']);

        // Not signed in, or not yet enrolled: the buyer is sent where they can
        // fix it, with a reason, rather than bounced to a bare 403.
        if ($request->user() === null) {
            return redirect()->route('login')->with('status', __('order.sign_in_to_checkout'));
        }

        if (! $request->user()->canRegister()) {
            return redirect()->route('totp.setup')
                ->with('status', __('order.enroll_to_checkout'));
        }

        $quote = $this->quoteFor($request, $cart);

        return view('pages.checkout', [
            'cart' => $cart,
            'quote' => $quote,
            'locale' => Locale::parse(app()->getLocale()),
            'currentRoute' => 'checkout',
            'user' => $request->user(),
            'maxPlacesPerLine' => self::MAX_PLACES_PER_LINE,
            'seatRows' => $this->seatRows($cart, $quote),
            'participantValues' => old('participants', []),
        ]);
    }

    /**
     * One row per basket line: the tariff, its seats, and whether each end of the
     * stepper is available.
     *
     * Built here rather than in the view so the full page and the JSON that
     * replaces a part of it cannot disagree about which button is disabled. A "+"
     * that is lit on the page and then refused on click is exactly the failure
     * this avoids, and the availability depends on things the browser cannot see:
     * the edition's remaining capacity, and the buyer's own membership.
     *
     * @return array<int, array{
     *     ticket_type_id: int,
     *     name: string,
     *     quantity: int,
     *     member_quantity: int,
     *     can_add: bool,
     *     can_remove: bool
     * }>
     */
    private function seatRows(Cart $cart, Quote $quote): array
    {
        $remaining = $this->checkout->remainingSeats($cart);

        return $cart->items->map(fn (CartItem $item): array => [
            'ticket_type_id' => (int) $item->ticket_type_id,
            'name' => (string) ($item->ticketType?->name ?? ''),
            'quantity' => $item->quantity,
            'member_quantity' => $item->member_quantity,
            'can_add' => $item->quantity < self::MAX_PLACES_PER_LINE && $remaining > 0,
            'can_remove' => $item->quantity > 1,
        ])->all();
    }

    /**
     * Add or remove one place on one basket line, then return to the checkout.
     *
     * The participant count is not a free choice — it is the number of seats the
     * basket holds, and `store()` refuses a form whose rows do not match it. So
     * "add a participant" here means "buy another place": the line's quantity
     * changes, the quote is redone on the way back, and the extra name field
     * appears because there is now a seat for it. That keeps the count, the
     * basket and the invoice from ever disagreeing.
     *
     * It is a redirect rather than a same-page mutation on purpose. The price of
     * a place is not derivable on the client — the member rate depends on an
     * active Membership record — so a "+" that updated the total locally would
     * be a guess. Re-rendering also brings back the authoritative quote, the
     * capacity headroom, and whatever the delegate had already typed.
     *
     * Reached from the checkout form itself, with `formaction` on the buttons, so
     * a buyer who has filled in two names and realises they are bringing a third
     * does not lose the two.
     *
     * Answers in two shapes. A plain form post gets the redirect below, which is
     * the whole-page path that works with scripting off. A request that accepts
     * JSON gets the same decision plus the three regions it has to repaint, and
     * design.js swaps them in place — no reload, and the same server-rendered
     * markup either way, so the no-reload path cannot render a different form
     * from the reload one.
     */
    public function seats(Request $request): RedirectResponse|JsonResponse
    {
        $cart = $this->currentCart($request);

        if ($cart->items()->doesntExist()) {
            return redirect()->route('pricing')->with('status', __('order.cart_empty'));
        }

        $user = $request->user();

        if ($user === null) {
            return redirect()->route('login');
        }

        $data = $request->validate([
            'adjust' => ['required', 'string', 'regex:/^[+-][0-9]+$/'],
        ], [], [
            'adjust' => __('order.participants'),
        ]);

        // The sign and the tariff are carried in one field rather than as a
        // `direction` plus a per-line select, so a tampered form cannot aim a
        // "+" at a line that is not the one the button belongs to.
        preg_match('/^([+-])([0-9]+)$/', $data['adjust'], $matches);

        $adding = $matches[1] === '+';
        $line = $cart->items()
            ->where('ticket_type_id', (int) $matches[2])
            ->first();

        if ($line === null) {
            return $this->seatOutcome($request, $cart->fresh(), $user, __('order.cart_line_missing'));
        }

        $quantity = $adding
            ? min(self::MAX_PLACES_PER_LINE, $line->quantity + 1)
            : max(1, $line->quantity - 1);

        if ($quantity === $line->quantity) {
            return $this->seatOutcome($request, $cart->fresh(), $user, $adding
                ? __('order.max_places_reached', ['max' => self::MAX_PLACES_PER_LINE])
                : __('order.min_one_place'));
        }

        if ($adding) {
            try {
                $this->checkout->assertSeatCanBeAdded($cart);
            } catch (RuntimeException $e) {
                return $this->seatOutcome($request, $cart->fresh(), $user, $e->getMessage());
            }
        }

        $line->update([
            'quantity' => $quantity,
            // Fewer places than member places would otherwise price the member
            // count above the seats, and the summary would show a member rate
            // for more people than are on the order.
            'member_quantity' => min($line->member_quantity, $quantity),
        ]);

        return $this->seatOutcome($request, $cart->fresh(), $user, null, $adding
            ? __('order.participant_added')
            : __('order.participant_removed'));
    }

    /**
     * The result of a seat change, in whichever shape the caller can use.
     *
     * The message travels either way — a refusal has to read the same to a buyer
     * whether their browser reloaded or not, and "the button did nothing" is not
     * a refusal anyone can act on.
     */
    private function seatOutcome(
        Request $request,
        Cart $cart,
        User $user,
        ?string $error,
        ?string $status = null,
    ): RedirectResponse|JsonResponse {
        if (! $request->expectsJson()) {
            return back()
                ->withInput()
                ->with('status', $status)
                ->withErrors($error === null ? [] : ['participants' => $error]);
        }

        $cart->load(['items.ticketType']);
        $quote = $this->quoteFor($request, $cart);
        $locale = Locale::parse(app()->getLocale());

        return response()->json([
            'ok' => $error === null,
            'message' => $error ?? $status,
            'count' => $quote->participantCount,
            'seats' => $this->seatRows($cart, $quote),
            // The values that were in the request rather than the flashed input,
            // so the repainted fields still hold what the buyer typed.
            'participants' => view('pages.checkout.participants', [
                'quote' => $quote,
                'participantValues' => $request->input('participants', []),
            ])->render(),
            'summary' => view('pages.checkout.summary', [
                'quote' => $quote,
                'currentLocale' => $locale,
            ])->render(),
        ]);
    }

    /**
     * Create the order and hand the customer to the gateway.
     */
    public function store(Request $request): RedirectResponse
    {
        $cart = $this->currentCart($request);

        if ($cart->items()->doesntExist()) {
            return redirect()->route('pricing')->with('status', __('order.cart_empty'));
        }

        $user = $request->user();

        if ($user === null) {
            return redirect()->route('login');
        }

        if (! $user->canRegister()) {
            return redirect()->route('totp.setup')
                ->with('status', __('order.enroll_to_checkout'));
        }

        $quote = $this->quoteFor($request, $cart);

        $data = $this->validated($request, $quote->participantCount);

        try {
            $order = $this->checkout->place(
                cart: $cart,
                user: $user,
                quote: $quote,
                participants: $data['participants'],
                billing: [
                    'first_name' => $data['first_name'],
                    'last_name' => $data['last_name'],
                    'organisation' => $data['organisation'] ?? null,
                    'address' => $data['address'] ?? null,
                    'city' => $data['city'] ?? null,
                    'country_iso2' => $data['country_iso2'] ?? null,
                    'email' => $data['email'],
                    'phone' => $data['phone'],
                ],
                ip: $request->ip(),
            );
        } catch (RuntimeException $e) {
            // A capacity or consistency refusal is the customer's answer, not
            // a server fault: send them back with the reason rather than a 500.
            return back()->withInput()->withErrors(['participants' => $e->getMessage()]);
        }

        // The delegate is shown the invoice before any money moves.
        //
        // This used to redirect straight to `checkout.pay`, which handed the
        // order to CMI immediately. That made the invoice unreachable before
        // payment: there was no screen to look at what had just been bought,
        // check the seats, or print. The order already exists and is unpaid at
        // this point, so `orders.show` renders the full document — status "non
        // payée", the priced seats, the total — with the gateway one click
        // behind its own confirm button.
        return redirect()->route('orders.show', ['order' => $order->getKey()]);
    }

    /**
     * Admin-only rehearsal: place a 1 MAD order and hand it to the gateway.
     *
     * Exists so the CMI round trip — redirect, callback, settlement, invoice —
     * can be exercised against a real order without charging a real tariff. The
     * buyer is fixed by config (`conference.payment_test_user`) and every other
     * account gets a 403, so the cheap amount cannot leak to a real delegate.
     *
     * Independent of the basket on purpose: it builds its own single-line quote
     * from the first active tariff and a throwaway (unsaved) cart, so running
     * the test never empties the tester's real basket. The line still points at
     * a real ticket type because `order_items.ticket_type_id` is a foreign key.
     */
    public function testPayment(Request $request): RedirectResponse
    {
        return $this->raiseTestOrder($request, 'MAD');
    }

    /**
     * The same rehearsal in EUR (0,09 €, about 1 MAD).
     *
     * Exercises the international-card path and the gateway's display of a
     * non-MAD amount without a real tariff, for roughly the same token cost as
     * the dirham test.
     */
    public function testPaymentEur(Request $request): RedirectResponse
    {
        return $this->raiseTestOrder($request, 'EUR');
    }

    /**
     * Build and place a token order in the given currency, then send the tester
     * to the gateway. Shared by the MAD and EUR rehearsals.
     */
    private function raiseTestOrder(Request $request, string $currency): RedirectResponse
    {
        $user = $request->user();

        abort_unless($user !== null && $this->isPaymentTester($user), 403);

        $ticket = TicketType::query()
            ->where('edition_id', Edition::current()?->getKey())
            ->active()
            ->orderBy('sort_order')
            ->first()
            ?? TicketType::query()->orderBy('id')->first();

        abort_if($ticket === null, 422, 'No ticket type is available to build a test order from.');

        $currency = strtoupper($currency);

        $amountKey = $currency === 'EUR'
            ? 'conference.payment_test_amount_eur'
            : 'conference.payment_test_amount';

        $amount = max(1, (int) config($amountKey, $currency === 'EUR' ? 9 : 100));

        $quote = new Quote(
            lines: [[
                'ticket_type_id' => (int) $ticket->getKey(),
                'label' => $ticket->getRawOriginal('name'),
                'unit_price_member' => $amount,
                'unit_price_standard' => $amount,
                'member_quantity' => 0,
                'standard_quantity' => 1,
                'line_total' => $amount,
            ]],
            subtotal: $amount,
            total: $amount,
            currency: $currency,
            memberCount: 0,
            standardCount: 1,
            participantCount: 1,
            ticketType: $ticket,
        );

        try {
            $order = $this->checkout->place(
                cart: new Cart,
                user: $user,
                quote: $quote,
                participants: [[
                    'full_name' => $user->name ?: 'Test Admin',
                    'email' => $user->email,
                ]],
                billing: [
                    'first_name' => $user->first_name ?? 'Test',
                    'last_name' => $user->last_name ?? 'Admin',
                    'email' => $user->email,
                    'phone' => $user->phone,
                ],
                ip: $request->ip(),
            );
        } catch (RuntimeException $e) {
            return redirect()->route('pricing')->with('status', $e->getMessage());
        }

        return redirect()->route('checkout.pay', ['order' => $order->getKey()]);
    }

    /**
     * Hand the order to the configured gateway.
     *
     * A GET that produces a redirect to a payment page, not a state change:
     * no money moves and no row is written, so a reload is harmless. The order
     * already exists from the previous step.
     */
    public function pay(Request $request): View|RedirectResponse
    {
        // Resolved through the buyer's own orders rather than bound from the
        // route: the URI is `{locale?}/inscription/payer/{order}`, so a typed
        // `Order $order` argument is handed the locale. The ownership test here
        // was commented out, which left nothing behind to catch it.
        $order = $this->findOrder($request);

        if (! $order->isPayable()) {
            return redirect()->route('orders.show', ['order' => $order->getKey()])
                ->with('status', __('order.not_payable'));
        }

        if ($order->status === OrderStatus::Pending) {
            $order->transitionTo(OrderStatus::AwaitingPayment);
        }

        $redirect = app(PaymentGateway::class)->redirectFor(
            $order,
            route('payment.return', ['order' => $order->getKey()]),
            route('payment.cancel', ['order' => $order->getKey()]),
        );

        // A self-submitting form rather than a Location header, because that
        // is what the gateway's own integration expects and because some
        // corporate proxies drop 302s to external hosts.
        return view('pages.checkout.redirect', [
            'order' => $order,
            'redirect' => $redirect,
        ]);
    }

    // --- Helpers ---------------------------------------------------------

    private function currentCart(Request $request): Cart
    {
        return Cart::forSession(
            $request->session()->getId(),
            $request->user(),
        );
    }

    /**
     * Whether this account is the one configured to rehearse a payment.
     *
     * Case-insensitive because an email is, and compared against config rather
     * than a hardcoded address so the tester can be changed without a deploy.
     */
    private function isPaymentTester(User $user): bool
    {
        $email = (string) config('conference.payment_test_user', 'admin@arabcia.test');

        return $email !== '' && strcasecmp((string) $user->email, $email) === 0;
    }

    /**
     * A basket that cannot be quoted â€” empty, or holding two currencies â€” is a
     * validation failure on the basket rather than a 500.
     *
     * Thrown rather than redirected from here, because the redirect has to
     * happen in the caller: a redirect returned from a method typed to produce
     * a Quote is how a broken basket would end up being rendered as a checkout
     * page with no quote attached.
     */
    private function quoteFor(Request $request, Cart $cart): Quote
    {
        try {
            return $this->checkout->quote($cart, $request->user());
        } catch (RuntimeException $e) {
            throw ValidationException::withMessages(['cart' => $e->getMessage()]);
        }
    }

    /**
     * Validate the billing block and exactly one entry per place.
     *
     * @return array<string, mixed>
     */
    private function validated(Request $request, int $placeCount): array
    {
        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:80'],
            'last_name' => ['required', 'string', 'max:80'],
            'organisation' => ['nullable', 'string', 'max:190'],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:120'],
            'country_iso2' => ['nullable', 'string', 'size:2'],
            'email' => ['required', 'email:filter', 'max:190'],
            'phone' => ['required', 'string', 'max:32'],
            'participants' => ['required', 'array', 'min:1', 'max:20'],
            'participants.*.full_name' => ['required', 'string', 'max:190'],
            'participants.*.job_title' => ['nullable', 'string', 'max:160'],
            'participants.*.email' => ['nullable', 'email:filter', 'max:190'],
            'participants.*.phone' => ['nullable', 'string', 'max:32'],
            'participants.*.is_member' => ['nullable', 'boolean'],
            'participants.*.badge_name' => ['nullable', 'string', 'max:48'],
        ], [], [
            'first_name' => __('register.first_name'),
            'last_name' => __('register.last_name'),
            'organisation' => __('register.organisation'),
            'address' => __('order.address'),
            'city' => __('register.city'),
            'country_iso2' => __('register.country'),
            'email' => __('register.email'),
            'phone' => __('register.phone'),
            'participants' => __('order.participants'),
            'participants.*.full_name' => __('order.participant_name'),
            'participants.*.job_title' => __('register.job_title'),
            'participants.*.email' => __('register.email'),
            'participants.*.phone' => __('register.phone'),
        ]);

        // Checked here as well as in the service. The service is the
        // authority â€” it runs inside the transaction â€” but failing here with a
        // field-level error is kinder than a generic message, and it stops a
        // tampered form from reaching the database at all.
        if (count($data['participants']) !== $placeCount) {
            throw ValidationException::withMessages([
                'participants' => __('order.participant_count_mismatch', [
                    'expected' => $placeCount,
                ]),
            ]);
        }

        return $data;
    }
}
