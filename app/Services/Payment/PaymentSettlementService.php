<?php

declare(strict_types=1);

namespace App\Services\Payment;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Exceptions\PaymentException;
use App\Jobs\IssueInvoiceAndNotify;
use App\Mail\OrderStatusChanged;
use App\Models\Order;
use App\Models\Payment;
use App\Support\Money;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Applies a verified gateway result to an order.
 *
 * This is the class the 2024 build most conspicuously lacked. It had a
 * `callback.php` that wrote to `iia_condidat` — a table the conference flow
 * never populated — so a customer could pay 7 500 MAD and the order stayed
 * 'encours' forever, invisible to the organiser and blocking the customer from
 * trying again.
 *
 * Three properties make the difference:
 *
 *  1. **Idempotency.** The gateway may call back more than once; CMI's own
 *     documentation says a rejection can be followed by an acceptance for the
 *     same order. A unique `idempotency_key` makes the second call a no-op
 *     rather than a second capture.
 *  2. **The state machine is enforced.** `transitionTo()` refuses an illegal
 *     move, so a late callback cannot flip a cancelled order back to paid.
 *  3. **Only a verified payload gets here.** The controller verifies the
 *     signature before calling; this class never sees unverified input.
 */
class PaymentSettlementService
{
    public function __construct(
        private readonly SettlementPlan $plan,
    ) {}

    /**
     * Record a verified result against its order.
     *
     * @param  array<string, mixed>  $payload
     *
     * @throws PaymentException when the payload names no order we hold
     */
    public function settle(array $payload, VerifiedPayment $result, bool $authoritative, ?Order $order = null): Order
    {
        $order ??= $this->resolveOrder($payload, $result);

        if ($order === null) {
            // A correctly signed callback for an order we do not have. Logged
            // and rejected: it is either a replay from a deleted order or a
            // probe, and inventing an order to attach it to would be worse
            // than recording nothing.
            Log::warning('cmi.callback.unresolvable', [
                'oid' => $payload['oid'] ?? null,
                'return_code' => $result->returnCode,
            ]);

            throw PaymentException::unresolvableOrder(
                is_string($payload['oid'] ?? null) ? $payload['oid'] : null
            );
        }

        $idempotencyKey = $this->idempotencyKey($order, $result);

        // Loaded here rather than left to be lazy-loaded further down. The
        // notification reads `$order->user`, and a lazy load inside a
        // notification is a query issued while the gateway is waiting for our
        // response — and under `preventLazyLoading` it is an outright failure,
        // so the buyer would be marked paid and never told.
        $order->loadMissing(['user', 'edition']);

        // A duplicate callback must not capture twice.
        if (Payment::query()->where('idempotency_key', $idempotencyKey)->exists()) {
            Log::info('cmi.callback.duplicate', [
                'order' => $order->reference,
                'idempotency_key' => $idempotencyKey,
            ]);

            return $order->refresh();
        }

        return DB::transaction(function () use ($order, $payload, $result, $authoritative, $idempotencyKey): Order {
            $payment = $this->recordPayment($order, $payload, $result, $idempotencyKey);

            if (! $result->isSuccessful()) {
                $this->markFailed($order);

                return $order->refresh();
            }

            // Amount cross-check, here rather than inside the driver.
            //
            // A signature proves the payload came from the gateway; it does
            // not prove the gateway processed the amount *we* asked for. If the
            // signed amount is not the one this order was sent out to be
            // charged, the transaction is not one we may settle — and
            // accepting it would let a customer pay 100 against a 7 500 place.
            //
            // The comparison runs in the settlement currency, because that is
            // the figure CMI signs: a store contracted to MAD-only settles a
            // 750 USD order by charging 7 500 MAD, and reading that `amount`
            // back as USD (or against `$order->total`) rejects a payment that
            // was processed correctly.
            //
            // Living in the settlement service rather than in CmiGateway is
            // deliberate: a check that only the production driver performs is
            // a check that the local rehearsal never exercises, which is how a
            // test driver ends up validating a path the real gateway never
            // takes.
            if (isset($payload['amount'])) {
                $settlement = $this->plan->forOrder($order);
                $claimed = Money::parse((string) $payload['amount'], $settlement['currency']) ?? 0;

                if ($claimed !== $settlement['minor']) {
                    throw PaymentException::amountMismatch($settlement['minor'], $claimed);
                }
            }

            // A non-authoritative result (the browser return) is recorded but
            // does not settle. The server-to-server callback is the only thing
            // that can mark an order paid, because the customer can close the
            // browser before it arrives and a return URL can be forged.
            if (! $authoritative) {
                $this->markAwaitingPayment($order);

                return $order->refresh();
            }

            $this->markPaid($order, $payment);

            return $order->refresh();
        });
    }

    /**
     * Settle an order from a payment that already took the money.
     *
     * The gateway path never needs this; it exists for the order whose
     * authorisation was accepted but whose server-to-server callback never
     * landed. The payment row carries the gateway's own proof — an
     * authorisation code and a transaction id — while the order still reads
     * `awaiting_payment`, and the buyer is left staring at an in-progress row
     * for money that has already left their account.
     *
     * This is the only place besides the authoritative callback that may mark
     * an order paid, and it runs only from an explicit operator command (`php
     * artisan orders:reconcile --apply`), never from a request. Returns whether
     * anything changed; a non-successful payment, an already-paid order or a
     * terminal one is left untouched.
     */
    public function reconcile(Order $order, Payment $payment): bool
    {
        if (! $payment->isSuccessful() || $order->status === OrderStatus::Paid) {
            return false;
        }

        if (! $order->status->canTransitionTo(OrderStatus::Paid)) {
            return false;
        }

        $order->loadMissing(['user', 'edition']);

        DB::transaction(function () use ($order, $payment): void {
            $this->markPaid($order, $payment);
        });

        return true;
    }

    /**
     * A stable key for "this gateway response for this order".
     *
     * Built from the transaction id where there is one, so a genuine second
     * authorisation of the same order is still recorded; falling back to a
     * constant means a re-sent callback for a failed attempt is collapsed,
     * which is the case that actually happens.
     */
    private function idempotencyKey(Order $order, VerifiedPayment $result): string
    {
        $basis = ($result->transactionId !== null && $result->transactionId !== '')
            ? $result->transactionId
            : 'no-transaction-id';

        return substr(
            hash('sha256', $order->uuid.'|'.$basis.'|'.($result->returnCode ?? '')),
            0,
            64
        );
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function recordPayment(Order $order, array $payload, VerifiedPayment $result, string $idempotencyKey): Payment
    {
        return Payment::query()->create([
            'order_id' => $order->getKey(),
            'driver' => (string) config('cmi.driver', 'test'),
            'gateway_transaction_id' => $result->transactionId,
            'gateway_reference' => $result->reference,
            'idempotency_key' => $idempotencyKey,
            'status' => $result->status,
            'amount' => $result->amount ?? $order->total,
            'currency' => $order->currency,
            'currency_numeric' => $order->currency_numeric,
            'masked_pan' => $result->maskedPan,
            'card_brand' => $result->cardBrand,
            'card_issuer' => $result->cardIssuer,
            'auth_code' => $result->authCode,
            'return_code' => $result->returnCode,
            'error_message' => $result->errorMessage,
            // The full payload is kept for dispute evidence. It holds no card
            // number: CMI returns only the masked form.
            'raw_payload' => $payload,
            'authorised_at' => $result->isSuccessful() ? now() : null,
            'failed_at' => $result->isSuccessful() ? null : now(),
        ]);
    }

    private function markPaid(Order $order, Payment $payment): void
    {
        // Already settled by an earlier callback: do not re-run the side
        // effects, which would send a second confirmation email.
        if ($order->status === OrderStatus::Paid) {
            return;
        }

        $order->transitionTo(OrderStatus::Paid);

        $payment->forceFill([
            'status' => PaymentStatus::Authorised,
            'captured_at' => now(),
        ])->save();

        // The payment is settled above; its paperwork is not. Rendering the
        // invoice with dompdf takes seconds and the gateway waits only a few
        // for our answer — replying late is what sends a paying customer to
        // CMI's "vérifiez l'état de votre commande" page while the money has
        // already been taken. The mail rides along in the same job so that it
        // can carry the invoice that was just rendered.
        IssueInvoiceAndNotify::dispatch($order->getKey());
    }

    private function markAwaitingPayment(Order $order): void
    {
        if ($order->status === OrderStatus::Pending) {
            $order->transitionTo(OrderStatus::AwaitingPayment);
        }
    }

    private function markFailed(Order $order): void
    {
        if ($order->status->canTransitionTo(OrderStatus::Failed)) {
            $order->transitionTo(OrderStatus::Failed);
        }

        $this->notifyFailed($order);
    }

    private function notifyFailed(Order $order): void
    {
        // Queued so a slow mail server cannot hold the gateway's callback
        // open. A failure to notify is an operator problem, not a payment
        // problem, so it is logged and swallowed.
        try {
            Mail::to($order->user?->email)
                ->queue(new OrderStatusChanged($order, 'failed'));
        } catch (Throwable $e) {
            report($e);

            Log::error('order.notification_failed', [
                'order' => $order->reference,
                'event' => 'failed',
            ]);
        }
    }

    /**
     * Find the order a verified result refers to.
     *
     * Resolved from the gateway's own reference rather than from a route
     * parameter, so a callback that names an order we do not hold is
     * recognised as such instead of being attached to whatever order the URL
     * happened to carry.
     *
     * @param  array<string, mixed>  $payload
     */
    private function resolveOrder(array $payload, VerifiedPayment $result): ?Order
    {
        // The result's own reference first, then the payload's `oid`. The
        // fallback matters: a driver that leaves the reference unset on a
        // failure would otherwise make a declined payment unattributable, and
        // the order would sit in `pending` looking abandoned.
        $reference = $result->reference
            ?? (is_string($payload['oid'] ?? null) ? $payload['oid'] : null);

        if ($reference === null || $reference === '') {
            return null;
        }

        // CMI's `oid` carries a per-attempt suffix (`<uuid>-<hex>`) so a retry
        // after a declined attempt is not refused as a duplicate `oid`. Only
        // the leading uuid identifies the order, so both forms are matched.
        return Order::query()
            ->whereIn('uuid', [$reference, substr($reference, 0, 36)])
            ->first();
    }
}
