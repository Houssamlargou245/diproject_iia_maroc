<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Services\Payment\PaymentSettlementService;
use Illuminate\Console\Command;

/**
 * Settle orders that were authorised but never marked paid.
 *
 * When CMI accepts an authorisation but the server-to-server callback never
 * reaches us, the gateway shows the buyer a "vérifiez l'état de votre commande
 * avec votre marchand" receipt and the order keeps reading `awaiting_payment`
 * even though the money has moved. The browser return may have recorded the
 * attempt, so the order carries a payment with the gateway's own authorisation
 * code and transaction id — that is the evidence this command acts on.
 *
 * It is a dry run unless `--apply` is given, because marking an order paid is
 * a decision that should be looked at before it is taken, not a background
 * side effect of a typo.
 */
class ReconcilePayments extends Command
{
    protected $signature = 'orders:reconcile
                            {--apply : Write the change; without it the command only reports what it finds}';

    protected $description = 'Settle orders that were authorised but never marked paid (the callback never landed)';

    public function handle(PaymentSettlementService $settlement): int
    {
        $orders = Order::query()
            ->whereIn('status', [
                OrderStatus::Pending,
                OrderStatus::AwaitingPayment,
                OrderStatus::Failed,
            ])
            ->whereHas('payments', fn ($query) => $query->successful())
            ->with(['payments', 'user', 'edition'])
            ->orderBy('id')
            ->get();

        if ($orders->isEmpty()) {
            $this->info('Nothing to reconcile.');

            return self::SUCCESS;
        }

        $apply = (bool) $this->option('apply');

        foreach ($orders as $order) {
            $payment = $order->payments->first(
                fn ($payment): bool => $payment->isSuccessful()
            );

            if ($payment === null) {
                continue;
            }

            if (! $apply) {
                $this->line(sprintf(
                    '%s  %s  %s',
                    $order->reference,
                    $order->status->value,
                    $payment->gateway_reference ?? '—',
                ));

                continue;
            }

            $settled = $settlement->reconcile($order, $payment);

            $this->line(sprintf(
                '%s  %s',
                $order->reference,
                $settled ? 'paid' : 'skipped',
            ));
        }

        if (! $apply) {
            $this->warn(sprintf(
                '%d order(s) would be settled. Re-run with --apply to write the change.',
                $orders->count(),
            ));
        }

        return self::SUCCESS;
    }
}
