@extends('layouts.app')

@section('title', __('account.orders'))

@section('content')
    <x-page-hero
        :title="__('account.orders')"
        :crumbs="[__('account.orders') => null]"
        image="assets/images/bg/price_bg.jpg" />

    <section class="ux-section">
        <div class="container">

            @if ($errors->any())
                <div class="ux-notice ux-notice--danger mb-4" role="alert">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $message)
                            <li>{{ $message }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif


            @if ($orders->isEmpty())
                {{-- A signed-in delegate with no order has not failed at
                     anything, so this is a starting point rather than an error:
                     it offers the pricing page instead of only stating the
                     absence. --}}
                <x-empty-state
                    :message="__('order.no_orders')"
                    icon="fa-regular fa-receipt">
                    <a href="{{ route('pricing') }}"
                       class="ux-btn ux-btn--primary mt-3"
                       data-ux-magnetic="0.16">
                        <span>@lang('pricing.register')</span>
                    </a>
                </x-empty-state>
            @else
                <div class="ux-card ux-card--edge ux-radius-xl p-4 ux-reveal">

                    {{-- Each line is the reference, not a row number: it is
                         what the delegate quotes to the organiser and what the
                         invoice download is filed under. --}}
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <caption class="visually-hidden">@lang('account.orders')</caption>
                            <thead>
                                <tr>
                                    <th scope="col">@lang('order.reference')</th>
                                    <th scope="col" class="d-none d-md-table-cell">@lang('order.placed_on')</th>
                                    <th scope="col">@lang('order.status')</th>
                                    <th scope="col" class="text-end">@lang('order.amount')</th>
                                    <th scope="col" class="text-end">
                                        <span class="visually-hidden">@lang('action.view')</span>
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($orders as $order)
                                    {{-- The reference cell is the link, not just
                                         a button: on this page the reference is
                                         the thing being looked for. --}}
                                    <tr>
                                        <th scope="row" class="fw-normal">
                                            <a href="{{ route('orders.show', ['order' => $order->id]) }}"
                                               class="font-monospace"
                                               dir="ltr">
                                                {{ $order->reference }}
                                            </a>
                                            {{-- The date column is dropped below
                                                 md, where the table cannot afford
                                                 it, so it is repeated here rather
                                                 than lost on a phone. --}}
                                            <span class="d-md-none ux-ink-soft small d-block">
                                                {{ $order->created_at->translatedFormat('d M Y') }}
                                            </span>
                                        </th>
                                        <td class="d-none d-md-table-cell">
                                            {{ $order->created_at->translatedFormat('d M Y') }}
                                        </td>
                                        <td>
                                            @php($statusColour = $order->effectiveStatus()->colour())
                                            <span @class([
                                                'ux-tag',
                                                'ux-tag--solid' => $statusColour === 'danger',
                                                'ux-tag--gold' => $statusColour === 'warning',
                                                'ux-tag--cyan' => $statusColour === 'info',
                                            ])>
                                                {{ $order->effectiveStatus()->label($currentLocale->value) }}
                                            </span>
                                        </td>
                                        <td class="text-end" dir="ltr">
                                            {{ \App\Support\Money::format($order->total, $order->currency, $currentLocale->value) }}
                                        </td>
                                        <td class="text-end">
                                            <a href="{{ route('orders.show', ['order' => $order->id]) }}"
                                               class="ux-btn ux-btn--ghost ux-btn--sm">
                                                <span>@lang('action.view')</span>
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                {{ $orders->links() }}
            @endif
        </div>
    </section>
@endsection