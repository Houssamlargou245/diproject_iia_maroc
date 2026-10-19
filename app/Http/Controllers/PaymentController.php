<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Exceptions\PaymentException;
use App\Http\Controllers\Concerns\ResolvesOwnedOrder;
use App\Models\Order;
use App\Services\Payment\PaymentGateway;
use App\Services\Payment\PaymentSettlementService;
use App\Services\Payment\TestGateway;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

/**
 * The payment endpoints.
 *
 * Three separate requests arrive here and they are not interchangeable:
 *
 *   - `callback`  CMI, server to server. Authoritative. The only path that
 *                 settles an order. No session and no CSRF token: it answers
 *                 with CMI's own protocol words, not HTML.
 *   - `return`    CMI, through the customer's browser. A courtesy. It records
 *                 what it can but never settles: the customer can close the
 *                 tab before it fires and the URL is trivially forgeable.
 *   - `cancel`    The customer gave up. Nothing is settled, nothing guessed.
 *
 * The 2024 build had `Ok-Fail.php` settling from the browser return and
 * `callback.php` writing to a table nobody populated, so a paid order never
 * became paid. Getting this division right is the single most important
 * correctness fix in the port.
 */
class PaymentController extends Controller
{
    use ResolvesOwnedOrder;

    public function __construct(
        private readonly PaymentSettlementService $settlement,
    ) {}

    /**
     * The CMI server-to-server callback.
     *
     * Excluded from CSRF verification in bootstrap/app.php because there is no
     * session: CMI is a third-party server, not a browser, and the signature
     * is what authenticates the request.
     */
    public function callback(Request $request): Response
    {
        $payload = $request->post();

        try {
            $result = $this->gateway()->verify($payload);
        } catch (PaymentException $e) {
            // CMI's protocol has one answer for "we could not process this":
            // FAILURE, meaning the merchant settles by hand. Anything else
            // risks a silent double charge.
            Log::warning('cmi.callback.rejected', [
                'reason' => $e->getMessage(),
                'oid' => $payload['oid'] ?? null,
                'ip' => $request->ip(),
            ]);

            return $this->cmiProtocol('FAILURE');
        }

        // Logged on every signed callback so a decline can be diagnosed from
        // the log instead of guessed at: `ProcReturnCode` is the gateway's
        // verdict, `mdStatus` the 3-D Secure outcome, and `ErrMsg` the human
        // wording ("Autorisation de paiement non permise (57)" and friends).
        // None of these are secrets.
        Log::info('cmi.callback.verified', [
            'oid' => $payload['oid'] ?? null,
            'return_code' => $payload['ProcReturnCode'] ?? null,
            'error' => $payload['ErrMsg'] ?? null,
            'md_status' => $payload['mdStatus'] ?? null,
            'response' => $payload['Response'] ?? null,
            'amount' => $payload['amount'] ?? null,
            'currency' => $payload['currency'] ?? null,
            'successful' => $result->isSuccessful(),
        ]);

        try {
            $this->settlement->settle(
                payload: $payload,
                result: $result,
                authoritative: true,
            );
        } catch (PaymentException $e) {
            Log::warning('cmi.callback.unsettleable', [
                'reason' => $e->getMessage(),
                'oid' => $payload['oid'] ?? null,
            ]);

            return $this->cmiProtocol('FAILURE');
        }

        // `ACTION=POSTAUTH` asks CMI to capture the authorised amount, but only
        // a PreAuth needs capturing: on an immediate sale (`Auth`) the funds are
        // already captured and POSTAUTH would ask the gateway to capture twice.
        // The reply is therefore driven by the configured transaction type.
        if ($result->isSuccessful() && $this->usesPreAuth()) {
            return $this->cmiProtocol('ACTION=POSTAUTH');
        }

        return $this->cmiProtocol('APPROVED');
    }

    /**
     * The customer's browser coming back.
     *
     * Records what it can and then shows the truth. If the authoritative
     * callback has not landed yet the page says so rather than claiming
     * success: the gap is usually a few hundred milliseconds, and telling a
     * paying customer "we have not received it yet" is both honest and far
     * better than the 2024 behaviour of silently redirecting to the home page
     * as though nothing had happened.
     */
    public function return(Request $request): View|RedirectResponse
    {
        // Resolved through the buyer's own orders: the URI is
        // `{locale?}/paiement/retour/{order}`, so a typed `Order $order`
        // argument is handed the locale instead of the id.
        $order = $this->findOrder($request);

        $payload = $request->post();

        if ($payload !== []) {
            try {
                $result = $this->gateway()->verify($payload);

                // authoritative: false — recorded, but it does not settle.
                $this->settlement->settle($payload, $result, authoritative: false);
            } catch (PaymentException) {
                // A bad signature on the return URL is not worth alarming the
                // customer over; the callback is authoritative regardless.
                Log::info('cmi.return.invalid_signature', [
                    'order' => $order->reference,
                ]);
            }
        }

        $order->refresh();

        if ($order->status->isSettled()) {
            return redirect()->route('orders.show', ['order' => $order->getKey()])
                ->with('status', __('order.payment.confirmed'));
        }

        return view('pages.payment.pending', [
            'order' => $order,
            'currentRoute' => 'orders',
        ]);
    }

    /**
     * The customer abandoned the payment.
     *
     * The order is left awaiting payment rather than cancelled. A customer
     * interrupted at the 3D Secure step is still a customer, and the 2024
     * build's habit of leaving such rows as permanently 'encours' is what
     * blocked people from ever re-registering.
     */
    public function cancel(Request $request): View|RedirectResponse
    {
        $order = $this->findOrder($request);

        return view('pages.payment.cancelled', [
            'order' => $order,
            'currentRoute' => 'orders',
        ]);
    }

    /**
     * The local rehearsal page served by the test driver.
     *
     * Refused outright unless the test driver is the configured one, so this
     * can never become a way to settle an order in production.
     */
    public function testGatewayPage(Request $request, string $uuid): View
    {
        abort_unless(config('cmi.driver') === 'test', 404);

        // Looked up through the buyer's own orders rather than by uuid alone.
        // The uuid is a bearer secret printed into a URL, so without the
        // ownership scope it would be enough to open someone else's rehearsal
        // page — and this page is wired to settle orders.
        $order = $request->user()->orders()
            ->where('uuid', $uuid)
            ->firstOrFail();

        return view('pages.payment.test-gateway', [
            'order' => $order,
            'approved' => app(TestGateway::class)->simulate($order, true),
            'declined' => app(TestGateway::class)->simulate($order, false),
            'callbackUrl' => url('/payment/cmi/callback'),
            'currentRoute' => 'checkout',
        ]);
    }

    // --- Helpers ---------------------------------------------------------

    private function gateway(): PaymentGateway
    {
        return app(PaymentGateway::class);
    }

    /**
     * Whether the gateway was asked for a pre-authorisation.
     *
     * A pre-authorisation has to be captured with `ACTION=POSTAUTH`; an
     * immediate sale (`Auth`) is already captured, so answering POSTAUTH there
     * would attempt a second capture.
     */
    private function usesPreAuth(): bool
    {
        return strcasecmp((string) config('cmi.trans_type', 'PreAuth'), 'PreAuth') === 0;
    }

    /**
     * CMI's callback protocol is a bare string body, not HTML.
     */
    private function cmiProtocol(string $action): Response
    {
        return response($action, 200)
            ->header('Content-Type', 'text/plain; charset=UTF-8');
    }
}
