@extends('layouts.app')

@section('title', __('order.title'))

@push('body-class', 'ux-printable')

{{-- The invoice-sheet styles are the same ones the standalone print pages use
     (see resources/views/invoices/partials/sheet-styles.blade.php), scoped under
     `.invoice-doc` so they cannot leak into the site layout. --}}
@push('head')
    @include('invoices.partials.sheet-styles')
@endpush

@section('content')
    @php
        $latestPayment = $order->latestPaymentRecord();
    @endphp

    <div class="invoice-doc invoice-doc--embedded">

        {{-- Screen-only, pre-print status: before payment the order can still be
             settled, and a delegate whose payment failed needs to see what was
             refused. Both vanish from the printed sheet. --}}
        @if ($isProforma)
            <div class="ux-notice ux-notice--warning ux-print-hide mb-3">
                <p class="mb-0 small">@lang('order.unpaid_notice')</p>
            </div>
        @endif

        @if ($latestPayment)
            <div class="ux-notice ux-notice--warning ux-print-hide mb-3">
                <p class="mb-0 small">
                    <strong>@lang('order.payment.last_attempt'):</strong>
                    <span dir="ltr">
                        {{ $latestPayment->formattedAmount() }} — {{ $latestPayment->status->value }}
                        @if ($latestPayment->cardLabel() !== '—')
                            <br>{{ $latestPayment->cardLabel() }}
                        @endif
                    </span>
                </p>
            </div>
        @endif

        {{-- The toolbar is the page's only chrome: the document below is the
             same sheet the browser and the PDF render. --}}
        <div class="toolbar ux-print-hide">
            @if ($order->isPayable())
                <a href="{{ route('checkout.pay', ['order' => $order->id]) }}" class="btn">
                    @lang('order.confirm_and_pay')
                </a>
            @endif

            <!-- @if ($order->status->isSettled())
                <form method="POST"
                      action="{{ route('orders.invoice', ['order' => $order->id]) }}"
                      class="m-0">
                    @csrf
                    <button type="submit" class="btn btn--ghost">@lang('order.download_invoice')</button>
                </form>
            @endif -->

            @if ($order->status->isSettled())
    <button type="button" class="btn btn--ghost" onclick="window.print()">
    @lang('order.download_invoice')
</button>
@endif

            <button type="button" class="btn btn--ghost" onclick="window.print()">
                @lang('order.print')
            </button>

            @unless ($order->status->isSettled() || $order->status->isFinal())
                <form method="POST"
                      action="{{ route('orders.cancel', ['order' => $order->id]) }}"
                      class="m-0"
                      onsubmit="return confirm(@js(__('order.cancel.confirm')));">
                    @csrf
                    <button type="submit" class="btn btn--ghost">@lang('order.cancel.action')</button>
                </form>
            @endunless
        </div>

        @include('invoices.partials.sheet', [
            'payload' => $payload,
            'heading' => $heading,
            'badge' => $badge,
            'isProforma' => $isProforma,
        ])

    </div>
@endsection