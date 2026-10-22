<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Mail\OrderStatusChanged;
use App\Models\Order;
use App\Services\Registration\InvoiceService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * The paperwork of a settled payment: the invoice PDF, then the email.
 *
 * Both used to be produced inside the gateway callback, before it could
 * answer CMI. Rendering the invoice with dompdf takes seconds and the gateway
 * waits only a few for our reply, so a payment authorised in full was often
 * answered too late — CMI then shows "il est nécessaire de vérifier l'état de
 * votre commande avec votre marchand" while the money has left the customer's
 * account.
 *
 * Nothing here is needed to acknowledge the gateway: the payment row and the
 * order's `paid` state are already written by the time this runs, and it is
 * dispatched after the transaction commits, so it can never render an invoice
 * for a payment that rolled back.
 */
final class IssueInvoiceAndNotify implements ShouldQueue, ShouldQueueAfterCommit
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(private int $orderId) {}

    public function handle(InvoiceService $invoices): void
    {
        $order = Order::query()->find($this->orderId);

        if ($order === null) {
            return;
        }

        $this->issueInvoice($order, $invoices);
        $this->notify($order);
    }

    /**
     * A PDF that will not render must not take the notification with it: the
     * buyer still has to be told the order is paid and where to download the
     * invoice once the renderer is fixed.
     */
    private function issueInvoice(Order $order, InvoiceService $invoices): void
    {
        try {
            $invoices->generate($order);
        } catch (Throwable $e) {
            report($e);

            Log::error('invoice.generation_failed', [
                'order' => $order->reference,
                'reason' => $e::class,
            ]);
        }
    }

    /**
     * Sent here rather than queued from the settlement so that the mail can
     * carry the invoice that was just rendered.
     */
    private function notify(Order $order): void
    {
        try {
            Mail::to($order->user?->email)->sendNow(new OrderStatusChanged($order, 'paid'));
        } catch (Throwable $e) {
            report($e);

            Log::error('order.notification_failed', [
                'order' => $order->reference,
                'event' => 'paid',
            ]);
        }
    }
}
