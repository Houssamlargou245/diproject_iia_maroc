<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\Locale;
use App\Enums\OrderStatus;
use App\Http\Controllers\Concerns\ResolvesOwnedOrder;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Country;
use App\Models\Edition;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\TicketType;
use App\Models\User;
use App\Services\Payment\PaymentGateway;
use App\Services\Registration\CheckoutService;
use App\Services\Registration\Quote;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
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

    private const PENDING_CHECKOUT_ORDER_SESSION_KEY = 'checkout.pending_order_id';

    public function __construct(
        private readonly CheckoutService $checkout,
    ) {}

    /**
     * Show the checkout for the current basket.
     */
    public function create(Request $request): View|RedirectResponse
    {
        // Not signed in, or not yet enrolled: the buyer is sent where they can
        // fix it, with a reason, rather than bounced to a bare 403.
        if ($request->user() === null) {
            return redirect()->route('login')->with('status', __('order.sign_in_to_checkout'));
        }

        if (! $request->user()->canRegister()) {
            return redirect()->route('totp.setup')
                ->with('status', __('order.enroll_to_checkout'));
        }

        $cart = $this->currentCart($request);
        $orderId = $request->session()->get(self::PENDING_CHECKOUT_ORDER_SESSION_KEY);
        $pendingOrder = $orderId === null
            ? null
            : $request->user()->orders()
                ->whereKey($orderId)
                ->with(['items.ticketType', 'participants'])
                ->first();
        $resumeOrder = null;

        if ($cart->items()->doesntExist()) {
            if ($pendingOrder !== null && $pendingOrder->isPayable()) {
                $restoredCartItems = collect();

                foreach ($pendingOrder->items as $orderItem) {
                    $cartItem = new CartItem([
                        'cart_id' => $cart->getKey(),
                        'ticket_type_id' => $orderItem->ticket_type_id,
                        'quantity' => $orderItem->totalQuantity(),
                        'member_quantity' => $orderItem->member_quantity,
                    ]);

                    $cartItem->setRelation('ticketType', $orderItem->ticketType);
                    $restoredCartItems->push($cartItem);
                }

                $cart->setRelation('items', $restoredCartItems);
                $resumeOrder = $pendingOrder;
            } else {
                $request->session()->forget(self::PENDING_CHECKOUT_ORDER_SESSION_KEY);

                return redirect()->route('pricing')->with('status', __('order.cart_empty'));
            }
        }

        if ($resumeOrder === null) {
            $cart->load(['items.ticketType']);
        }

        $quote = $resumeOrder === null
            ? $this->quoteFor($request, $cart)
            : $this->quoteForOrder($resumeOrder);
        $checkoutValues = $resumeOrder === null ? [] : [
            ...($resumeOrder->billing ?? []),
            'country_id' => Country::query()
                ->where('iso2', $resumeOrder->billing['country_iso2'] ?? '')
                ->value('id'),
        ];
        $participantValues = $resumeOrder === null
            ? old('participants', [])
            : $resumeOrder->participants->map(fn ($participant): array => [
                'full_name' => $participant->full_name,
                'job_title' => $participant->job_title,
                'email' => $participant->email,
                'phone' => $participant->phone,
                'is_member' => $participant->is_member,
            ])->all();
        $seatRows = $this->seatRows($cart, $quote);

        if ($resumeOrder !== null) {
            $seatRows = array_map(fn (array $seat): array => array_replace($seat, [
                'can_add' => false,
                'can_remove' => false,
            ]), $seatRows);
        }

        return view('pages.checkout', [
            'cart' => $cart,
            'quote' => $quote,
            'locale' => Locale::parse(app()->getLocale()),
            'currentRoute' => 'checkout',
            'user' => $request->user(),
            'maxPlacesPerLine' => self::MAX_PLACES_PER_LINE,
            'seatRows' => $seatRows,
            'participantValues' => $participantValues,
            'checkoutValues' => $checkoutValues,
            'resumeOrder' => $resumeOrder,
            // The billing country, which the issuing bank reads to judge the
            // payer. Eager loaded: the select renders every row's name, and a
            // lazy relation here is one query per country in the list.
            'countries' => Country::query()
                ->where('is_active', true)
                ->orderBy('name_'.app()->getLocale())
                ->get(),
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
            $orderId = $request->session()->get(self::PENDING_CHECKOUT_ORDER_SESSION_KEY);
            $pendingOrder = $orderId === null
                ? null
                : $request->user()?->orders()->whereKey($orderId)->first();

            if ($pendingOrder !== null && $pendingOrder->isPayable()) {
                return redirect()->route('checkout');
            }

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
                    // Address, city and country are what the issuing bank
                    // reads to decide whether the payer is who they say they
                    // are. They are required, not optional, because a payment
                    // sent with an empty country is a foreign payment with
                    // nothing to verify, and the bank declines it.
                    'address' => $data['address'],
                    'city' => $data['city'],
                    'country_iso2' => $data['country_iso2'],
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

        $request->session()->put(self::PENDING_CHECKOUT_ORDER_SESSION_KEY, $order->getKey());

        // The order is now committed, so hand it to the existing ownership-
        // checked payment action. That action builds the signed gateway form
        // and remains the single path used for both first attempts and retries.
        return redirect()->route('checkout.pay', ['order' => $order->getKey()]);
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
     * The same rehearsal in EUR (15,00 €, about 166,67 MAD).
     *
     * Exercises the international-card path and the gateway's display of a
     * non-MAD amount without a real tariff.
     */
    public function testPaymentEur(Request $request): RedirectResponse
    {
        return $this->raiseTestOrder($request, 'EUR');
    }

    /**
     * The same rehearsal at 0,50 MAD — the smallest amount the rehearsal can
     * raise, to push a sub-unit figure through the gateway and the settlement
     * plan end to end.
     */
    public function testPaymentHalf(Request $request): RedirectResponse
    {
        return $this->raiseTestOrder($request, 'MAD', 'conference.payment_test_amount_half');
    }

    /**
     * Build and place a token order in the given currency, then send the tester
     * to the gateway. Shared by the MAD, EUR and half-dirham rehearsals.
     */
    private function raiseTestOrder(Request $request, string $currency, ?string $amountKey = null): RedirectResponse
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

        $amountKey ??= $currency === 'EUR'
            ? 'conference.payment_test_amount_eur'
            : 'conference.payment_test_amount';

        $amount = max(1, (int) config($amountKey, $currency === 'EUR' ? 1500 : 100));

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
                    'organisation' => $user->organisation,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    // A real checkout always carries these and the gateway
                    // forwards them to the issuer for 3-D Secure; sending an
                    // empty country is one way to have the authorization
                    // refused, so the rehearsal mirrors a real order.
                    'address' => $user->city ?: 'Casablanca',
                    'city' => $user->city ?: 'Casablanca',
                    'country_iso2' => $user->country?->iso2 ?: 'MA',
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
     * Rebuild the checkout display from the order's frozen prices after the
     * browser returns from the gateway.
     */
    private function quoteForOrder(Order $order): Quote
    {
        $lines = $order->items->map(fn (OrderItem $item): array => [
            'ticket_type_id' => (int) $item->ticket_type_id,
            'label' => $item->label,
            'unit_price_member' => $item->unit_price_member,
            'unit_price_standard' => $item->unit_price_standard,
            'member_quantity' => $item->member_quantity,
            'standard_quantity' => $item->standard_quantity,
            'line_total' => $item->line_total,
        ])->all();

        return new Quote(
            lines: $lines,
            subtotal: $order->subtotal,
            total: $order->total,
            currency: $order->currency,
            memberCount: $order->member_count,
            standardCount: $order->standard_count,
            participantCount: $order->participants->count(),
            ticketType: $order->items->first()?->ticketType,
        );
    }

    /**
     * Validate the billing block and exactly one entry per place.
     *
     * @return array<string, mixed>
     */
    private function validated(Request $request, int $placeCount): array
    {
        // The phone reaches the issuing bank as `tel`, and the bank matches it
        // against the cardholder's own number. Separators are dropped here so
        // a buyer who types "+212 6 12 34 56 78" is not refused for the
        // spaces; the `+` is kept because it is what tells an international
        // number from a national one.
        $request->merge(['phone' => $this->compactPhone((string) $request->input('phone'))]);

        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:80'],
            'last_name' => ['required', 'string', 'max:80'],
            'organisation' => ['nullable', 'string', 'max:190'],
            'address' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:120'],
            'country_id' => ['required', 'integer', Rule::exists('countries', 'id')->where('is_active', true)],
            'email' => ['required', 'email:filter', 'max:190'],
            'phone' => ['required', 'string', 'max:32', 'regex:/^\+[1-9]\d{7,14}$/'],
            'participants' => ['required', 'array', 'min:1', 'max:20'],
            'participants.*.full_name' => ['required', 'string', 'max:190'],
            'participants.*.job_title' => ['nullable', 'string', 'max:160'],
            'participants.*.email' => ['nullable', 'email:filter', 'max:190'],
            'participants.*.phone' => ['nullable', 'string', 'max:32'],
            'participants.*.is_member' => ['nullable', 'boolean'],
            'participants.*.badge_name' => ['nullable', 'string', 'max:48'],
        ], [
            'phone.regex' => __('order.phone_international'),
        ], [
            'first_name' => __('register.first_name'),
            'last_name' => __('register.last_name'),
            'organisation' => __('register.organisation'),
            'address' => __('order.address'),
            'city' => __('register.city'),
            'country_id' => __('register.country'),
            'email' => __('register.email'),
            'phone' => __('register.phone'),
            'participants' => __('order.participants'),
            'participants.*.full_name' => __('order.participant_name'),
            'participants.*.job_title' => __('register.job_title'),
            'participants.*.email' => __('register.email'),
            'participants.*.phone' => __('register.phone'),
        ]);

        // The country the buyer states, not the one on their profile: a
        // delegate who has moved, or who pays with a card issued abroad, must
        // be able to say where they actually are. The gateway is given the ISO
        // code because that is what it forwards to the issuing bank.
        $data['country_iso2'] = Country::query()
            ->whereKey($data['country_id'])
            ->value('iso2');

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

    /**
     * A phone number with its separators removed.
     *
     * The buyer may type "+212 6 12 34 56 78" or "+212.6.12.34.56.78"; the
     * issuing bank is given one form. The `+` is kept because it is what
     * tells an international number from a national one, and a national number
     * is refused rather than guessed at: a wrong country code on a payment is
     * a payment the bank cannot reconcile with the cardholder.
     */
    private function compactPhone(string $phone): string
    {
        $trimmed = trim($phone);
        $hasPlus = str_starts_with($trimmed, '+');
        $digits = preg_replace('/\D+/', '', $trimmed) ?? '';

        return $hasPlus ? '+'.$digits : $digits;
    }
}
