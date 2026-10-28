<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\Locale;
use App\Models\Cart;
use App\Models\TicketType;
use App\Services\Registration\CheckoutService;
use App\Services\Registration\InvoiceService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * The basket.
 *
 * Replaces `addToCart.php` and `removeitem.php`, which had two defects worth
 * naming. The first rejected the insert when a cart row already existed, so a
 * single abandoned 'encours' row permanently blocked that customer from
 * buying again — the exact failure the 2024 build's own comments apologise for
 * without fixing. The second concatenated the product id straight into SQL.
 *
 * A cart here is a row that expires, holds no price, and holds at most one
 * line per ticket type. Prices resolve at checkout.
 */
class CartController extends Controller
{
    public function __construct(
        private readonly CheckoutService $checkout,
        private readonly InvoiceService $invoices,
    ) {}

    /**
     * Show the basket.
     */
    public function show(Request $request): View|RedirectResponse
    {
        $cart = $this->currentCart($request);

        if ($cart->items()->doesntExist()) {
            // return redirect()->route('pricing')->with('status', __('order.cart_empty'));
        }

        $cart->load(['items.ticketType']);

        // The estimate is indicative only. The authoritative figure is the
        // quote produced at checkout, which applies the member rate from the
        // server's own record rather than from what the visitor claimed.
        //
        // `hasOrders` gates the "My payments" entry point on the basket: it is
        // shown only to a signed-in buyer who already has something to look at,
        // so a first-time visitor is not sent to an empty order list.
        $user = $request->user();
        $payableOrder = $user?->orders()
            ->outstanding()
            ->whereDoesntHave('payments', function (Builder $query): void {
                $query->successful();
            })
            ->latest('id')
            ->first();

        return view('pages.cart', [
            'cart' => $cart,
            'locale' => Locale::parse(app()->getLocale()),
            'currentRoute' => 'cart',
            'hasOrders' => $user !== null && $user->orders()->exists(),
            'payableOrder' => $payableOrder,
            'estimatedTotal' => (int) $cart->items->sum(
                fn ($item): int => $item->estimatedTotal()
            ),
            'currency' => (string) ($cart->items->first()?->ticketType?->currency
                ?? config('conference.default_currency', 'MAD')),
        ]);
    }

    /**
     * Download the basket summary as a PDF.
     *
     * A delegate whose company pays by transfer needs something to hand over
     * before they commit, and the basket is the last screen where that is still
     * true — after the checkout the money is already moving. It is rendered as
     * a proforma, not as an invoice, for the reason given in InvoiceService.
     */
    public function proforma(Request $request): StreamedResponse|RedirectResponse
    {
        $cart = $this->currentCart($request);

        if ($cart->items()->doesntExist()) {
            //    return redirect()->route('pricing')->with('status', __('order.cart_empty'));
        }

        return $this->invoices->cartProforma($cart, $request->user());
    }

    /**
     * The same summary, laid out for the browser's own print dialog.
     *
     * A separate route from the PDF rather than a query flag on this one,
     * because the two are different documents for different moments: the PDF is
     * the file to forward, the print view is for someone who wants a copy in
     * front of them now. Both are built from InvoiceService::cartPayload() so
     * they cannot disagree over the total.
     */
    public function printable(Request $request): View|RedirectResponse
    {
        $cart = $this->currentCart($request);

        if ($cart->items()->doesntExist()) {
            //  return redirect()->route('pricing')->with('status', __('order.cart_empty'));
        }

        return view('pages.cart-print', [
            'payload' => $this->invoices->cartPayload($cart, $request->user()),
            'locale' => Locale::parse(app()->getLocale()),
            'currentRoute' => 'cart',
        ]);
    }

    /**
     * Add a quantity of a ticket to the basket.
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'ticket_type_id' => ['required', 'integer', Rule::exists('ticket_types', 'id')],
            'quantity' => ['required', 'integer', 'min:1', 'max:20'],
            'member_quantity' => ['required', 'integer', 'min:0', 'max:20'],
        ], [], [
            'ticket_type_id' => __('register.ticket'),
            'quantity' => __('order.quantity'),
            'member_quantity' => __('order.member_places'),
        ]);

        if ($data['member_quantity'] > $data['quantity']) {
            return back()->withErrors([
                'member_quantity' => __('order.member_places_exceed'),
            ]);
        }

        $ticket = TicketType::query()
            ->active()
            ->whereKey($data['ticket_type_id'])
            ->first();

        if ($ticket === null) {
            // Either withdrawn or outside its sales window. Both are a
            // refusal rather than a silent no-op, so a stale pricing page
            // cannot produce a basket that fails later at checkout.
            return back()->withErrors([
                'ticket_type_id' => __('pricing.closed'),
            ]);
        }

        $cart = $this->currentCart($request);

        // A cart prices in a single currency, so adding a tariff in a different
        // one is the buyer changing their mind about the currency rather than
        // building a second order: the other currency's lines are dropped. That
        // is also what the pricing page's currency toggle implies. Without it, a
        // MAD-then-USD basket would only be refused later at checkout, after the
        // buyer has already named every participant.
        $cart->items()
            ->whereHas('ticketType', fn ($query) => $query->where('currency', '!=', $ticket->currency))
            ->delete();

        $line = $cart->items()->where('ticket_type_id', $ticket->getKey())->first();

        if ($line === null) {
            // A new line, rather than a second row for the same ticket: two
            // rows for one tariff would be summed at checkout into a quantity
            // the buyer never chose.
            $cart->items()->create([
                'ticket_type_id' => $ticket->getKey(),
                'quantity' => $data['quantity'],
                'member_quantity' => $data['member_quantity'],
            ]);
        } else {
            $line->update([
                'quantity' => $data['quantity'],
                'member_quantity' => $data['member_quantity'],
            ]);
        }

        $this->attachUser($request, $cart);

        return redirect()->route('cart')->with('status', __('order.cart_updated'));
    }

    /**
     * Change a line's quantity, including down to zero to remove it.
     */
    public function update(Request $request): RedirectResponse
    {
        // Read by name from the route, not as typed arguments. The URI is
        // `{locale?}/panier/ligne/{cart}/{ticketType}`, and action arguments are
        // filled positionally — so on `/fr/panier/ligne/5/7` the slots arrive as
        // ['fr', '5', '7'] and `$cart` would be handed 'fr'. Under
        // `strict_types` that is a TypeError, which is why editing or removing a
        // basket line worked in Arabic and threw in French and English.
        [$cart, $ticketType] = $this->cartRouteIds($request);

        $owned = $this->ownedCart($request, $cart);

        $line = $owned->items()->where('ticket_type_id', $ticketType)->first();

        if ($line === null) {
            return redirect()->route('cart')->with('status', __('order.cart_empty'));
        }

        $data = $request->validate([
            'quantity' => ['required', 'integer', 'min:0', 'max:20'],
            'member_quantity' => ['required', 'integer', 'min:0', 'max:20'],
        ], [], [
            'quantity' => __('order.quantity'),
            'member_quantity' => __('order.member_places'),
        ]);

        // A quantity of zero removes the line, so one form handles both
        // "change" and "remove" without a second endpoint.
        if ($data['quantity'] < 1) {
            $line->delete();

            return redirect()->route('cart');
        }

        if ($data['member_quantity'] > $data['quantity']) {
            return back()->withErrors([
                'member_quantity' => __('order.member_places_exceed'),
            ]);
        }

        $line->update($data);

        return redirect()->route('cart');
    }

    /**
     * Remove a line.
     */
    public function destroy(Request $request): RedirectResponse
    {
        [$cart, $ticketType] = $this->cartRouteIds($request);

        $this->ownedCart($request, $cart)
            ->items()
            ->where('ticket_type_id', $ticketType)
            ->delete();

        return redirect()->route('cart');
    }

    /**
     * Empty the basket.
     */
    public function clear(Request $request): RedirectResponse
    {
        $this->currentCart($request)->items()->delete();

        return redirect()->route('pricing')->with('status', __('order.cart_emptied'));
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
     * A cart is only ever modified through a cart this session owns.
     *
     * Scoped in SQL rather than fetched and compared, so a visitor cannot
     * empty somebody else's basket by guessing an id — which is exactly what
     * `removeitem.php` did, taking `$_POST['id']` with no ownership test.
     */
    /**
     * The cart id and ticket id this route is pointing at.
     *
     * Taken by name because `{locale?}` leads the path — see update(). Both are
     * cast explicitly: the route hands over strings, and the caller wants ints
     * to compare against a primary key.
     *
     * @return array{0: int, 1: int}
     */
    private function cartRouteIds(Request $request): array
    {
        $cart = $request->route('cart');
        $ticketType = $request->route('ticketType');

        abort_if($cart === null || $ticketType === null, 404);

        return [(int) $cart, (int) $ticketType];
    }

    private function ownedCart(Request $request, int $cartId): Cart
    {
        $cart = Cart::query()
            ->forSession($request->session()->getId())
            ->whereKey($cartId)
            ->first();

        abort_if($cart === null, 404);

        return $cart;
    }

    /**
     * Claim the cart for the user once they are signed in.
     *
     * Done on add rather than at checkout so a basket survives the login that
     * the checkout itself requires. Without it, an anonymous visitor fills a
     * basket, signs in as the checkout demands, and arrives back at an empty
     * page with no explanation.
     */
    private function attachUser(Request $request, Cart $cart): void
    {
        $user = $request->user();

        if ($user !== null && $cart->user_id !== $user->getKey()) {
            $cart->forceFill(['user_id' => $user->getKey()])->save();
        }
    }
}
