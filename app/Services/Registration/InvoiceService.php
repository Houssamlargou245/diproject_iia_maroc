<?php

declare(strict_types=1);

namespace App\Services\Registration;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Edition;
use App\Models\Order;
use App\Models\User;
use App\Support\Money;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InvoiceService
{
    /**
     * Generate (or regenerate) the PDF for a paid order.
     */
    public function generate(Order $order): string
    {
        if (blank((string) $order->invoice_number)) {
            $order->assignInvoiceNumber();
        }

        $order->loadMissing(['items', 'participants', 'edition', 'user', 'ticketType']);
        $locale = $this->invoiceLocale($order);
        
        $payload = $this->orderPayload($order);

        // Note: invoices.pdf should just include invoices.partials.sheet
        $pdf = Pdf::loadView('invoices.pdf', [
            'order' => $order,
            'payload' => $payload,
            'heading' => __('order.invoice.title', ['number' => $order->invoice_number], $locale),
            'badge' => $order->status->label($locale),
            'isProforma' => false,
        ]);

        $pdf->setPaper(
            (string) config('pdf.paper', 'a4'),
            (string) config('pdf.orientation', 'portrait'),
        );

        $filename = $this->filename($order);

        Storage::disk($this->disk())->put($filename, $pdf->output());

        $order->forceFill(['invoice_path' => $filename])->save();

        return $filename;
    }

    /**
     * Stream an existing invoice, generating it first if it is missing.
     */
    public function download(Order $order): StreamedResponse
    {
        $path = (string) $order->invoice_path;
        $disk = Storage::disk($this->disk());

        if ($path === '' || ! $disk->exists($path)) {
            $path = $this->generate($order);
        }

        return $disk->download($path, $this->downloadName($order));
    }

    /**
     * A proforma invoice for an order that has not been paid yet.
     */
    public function orderProforma(Order $order): StreamedResponse
    {
        $payload = $this->orderPayload($order);
        $locale = $this->invoiceLocale($order);

        // Note: invoices.proforma should just include invoices.partials.sheet
        $pdf = Pdf::loadView('invoices.proforma', [
            'order' => $order,
            'payload' => $payload,
            'heading' => __('order.invoice.proforma_title', [], $locale),
            'badge' => $order->status->label($locale),
            'isProforma' => true,
        ]);

        $pdf->setPaper(
            (string) config('pdf.paper', 'a4'),
            (string) config('pdf.orientation', 'portrait'),
        );

        $output = $pdf->output();

        return response()->streamDownload(
            static function () use ($output): void {
                echo $output;
            },
            $this->proformaName((string) $payload['reference']),
            ['Content-Type' => 'application/pdf'],
        );
    }

    /**
     * The same figures the PDF prints, as data.
     */
    public function orderPayload(Order $order): array
    {
        $order->loadMissing(['items', 'edition', 'user', 'participants']);

        $currency = (string) $order->currency;
        $latestPayment = method_exists($order, 'latestPaymentRecord') ? $order->latestPaymentRecord() : null;
        $locale = $this->invoiceLocale($order);
        
        // Safely get participant name
        $participant = $order->participants->first();
        $participantName = $participant?->name ?? trim(($participant->first_name ?? '') . ' ' . ($participant->last_name ?? ''));
        if (empty($participantName)) {
            $participantName = $order->user->displayName();
        }

                return [
            'edition' => $order->edition,
            'reference' => (string) $order->reference,
            
            // ADD THIS LINE BACK IN:
            'issuedOn' => $order->created_at ? $order->created_at->format('d/m/Y') : now()->format('d/m/Y'),
            
            'lines' => $order->items->map(function ($item) use ($currency, $locale) {
                $qty = $item->totalQuantity();
                $attributes = $item->getAttributes();
                
                $unitPrice = $attributes['unit_price'] ?? $attributes['price'] ?? 0;
                $lineTotal = $attributes['total'] ?? $attributes['subtotal'] ?? $attributes['amount'] ?? $attributes['line_total'] ?? 0;
                
                if ($unitPrice == 0 && $qty > 0) {
                    $unitPrice = $lineTotal / $qty;
                }
                
                return [
                    'label' => (string) $item->label,
                    'quantity' => $qty,
                    'unit_price' => Money::format($unitPrice, $currency, $locale),
                    'total' => $item->formattedLineTotal($currency),
                ];
            })->all(),
            'subtotal' => (int) $order->subtotal,
            'discount' => (int) $order->discount_total,
            'tax' => (int) $order->tax_total,
            'total' => (int) $order->total,
            'currency' => $currency,
            'billedTo' => $this->orderBillingLines($order),
            'invoiceLocale' => $locale,
            
            'participant_name' => $participantName,
            'payment_date' => $latestPayment ? ($latestPayment->settled_at ?? $latestPayment->created_at)->format('d/m/Y') : null,
            'transaction_reference' => $latestPayment ? ($latestPayment->gateway_transaction_id ?? $latestPayment->gateway_reference) : null,
        ];
    }

    /**
     * A proforma invoice for a basket, before any order exists.
     */
    public function cartProforma(Cart $cart, ?User $user): StreamedResponse
    {
        return $this->streamProforma($this->cartPayload($cart, $user), 'facture-provisoire.pdf');
    }

    /**
     * The basket's proforma figures, as data.
     */
    public function cartPayload(Cart $cart, ?User $user): array
    {
        $cart->loadMissing(['items.ticketType', 'user']);

        $currency = (string) ($cart->items->first()?->ticketType?->currency
            ?? config('conference.default_currency', 'MAD'));

        $estimate = (int) $cart->items->sum(fn (CartItem $item): int => $item->estimatedTotal());
        $locale = $this->cartLocale($user);

        return [
            'edition' => Edition::current(),
            'reference' => null,
            'issuedOn' => now()->format('d/m/Y'),
            'lines' => $cart->items->map(function (CartItem $item) use ($currency, $locale) {
                $qty = (int) $item->quantity;
                $attributes = $item->getAttributes();
                
                $unitPrice = $attributes['unit_price'] ?? $attributes['price'] ?? ($item->ticketType?->price ?? 0);
                
                return [
                    'label' => (string) ($item->ticketType?->name ?? __('order.ticket')),
                    'quantity' => $qty,
                    'unit_price' => Money::format($unitPrice, $currency, $locale),
                    'total' => $item->formattedEstimatedTotal(),
                ];
            })->all(),
            'subtotal' => $estimate,
            'discount' => 0,
            'tax' => 0,
            'total' => $estimate,
            'currency' => $currency,
            'billedTo' => $this->cartBillingLines($user),
            'invoiceLocale' => $locale,
            
            // Cart has no payment or participant yet
            'participant_name' => $user?->displayName() ?? '—',
            'payment_date' => null,
            'transaction_reference' => null,
        ];
    }

    private function streamProforma(array $payload, string $filename): StreamedResponse
    {
        $pdf = Pdf::loadView('invoices.proforma', [
            'payload' => $payload,
            'heading' => __('order.invoice.proforma_title', [], $payload['invoiceLocale']),
            'badge' => null,
            'isProforma' => true,
        ]);

        $pdf->setPaper(
            (string) config('pdf.paper', 'a4'),
            (string) config('pdf.orientation', 'portrait'),
        );

        $output = $pdf->output();

        return response()->streamDownload(
            static function () use ($output): void {
                echo $output;
            },
            $filename,
            ['Content-Type' => 'application/pdf'],
        );
    }

    private function orderBillingLines(Order $order): array
    {
        $billing = (array) $order->billing;

        $lines = array_filter([
            trim(((string) ($billing['first_name'] ?? '')).' '.((string) ($billing['last_name'] ?? ''))),
            (string) ($billing['organisation'] ?? ''),
            (string) ($billing['address'] ?? ''),
            (string) ($billing['city'] ?? ''),
            (string) ($billing['country_iso2'] ?? ''),
            (string) ($billing['email'] ?? ''),
            (string) ($billing['phone'] ?? ''),
        ]);

        return $lines === [] ? [__('order.invoice.billed_to_pending')] : array_values($lines);
    }

    private function cartBillingLines(?User $user): array
    {
        if ($user === null) {
            return [__('order.invoice.billed_to_pending')];
        }

        $lines = array_filter([
            $user->displayName(),
            (string) ($user->organisation ?? ''),
            (string) ($user->email ?? ''),
            (string) ($user->phone ?? ''),
        ]);

        return $lines === [] ? [__('order.invoice.billed_to_pending')] : array_values($lines);
    }

    private function cartLocale(?User $user): string
    {
        $locale = (string) ($user?->locale ?? app()->getLocale());
        return in_array($locale, ['fr', 'en', 'ar'], true) ? $locale : 'fr';
    }

    private function proformaName(string $reference): string
    {
        return sprintf('facture-provisoire-%s.pdf', Str::slug($reference));
    }

    private function filename(Order $order): string
    {
        return sprintf(
            'invoices/%s/%s-%s.pdf',
            $order->reference,
            Str::uuid()->toString(),
            Str::slug((string) $order->invoice_number),
        );
    }

    private function downloadName(Order $order): string
    {
        return sprintf(
            'facture-%s.pdf',
            Str::slug((string) ($order->invoice_number ?? $order->reference)),
        );
    }

    private function disk(): string
    {
        return 'local';
    }

    private function invoiceLocale(Order $order): string
    {
        $userLocale = (string) ($order->user?->locale ?? 'fr');
        return in_array($userLocale, ['fr', 'en', 'ar'], true) ? $userLocale : 'fr';
    }
}