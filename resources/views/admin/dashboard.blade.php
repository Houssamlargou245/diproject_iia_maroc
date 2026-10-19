@extends('layouts.admin')

@section('title', __('admin.title'))

@section('heading', __('admin.nav.dashboard'))

@section('content')

    @if ($edition === null)
        {{-- No edition is a setup state, not an error. The alternative — six
             cards of zeros — reads as "nothing has happened yet" and sends an
             operator looking for data that does not exist, when what is missing
             is the thing they are here to manage. --}}
        <div class="admin-card">
            <div class="admin-card__body">
                <h2 class="admin-card__title">@lang('admin.no_edition.title')</h2>
                <p class="admin-meta mb-0">@lang('admin.no_edition.text')</p>
            </div>
        </div>
    @else

        {{-- --- What needs a decision -------------------------------------
             First, because it is the only block on this page that is a to-do
             list. An operator who opens the dashboard between two other tasks
             needs to know whether anything is waiting; the money is context,
             the queues are work. --}}
        <div class="row g-3 mb-4">
            @foreach ($queues as $queue)
                @if ($queue['count'] > 0)
                    <div class="col-md-4">
                        {{-- The whole card is the link, and the count is in the
                             link's text, so it is announced with the thing it
                             counts rather than as a bare numeral. --}}
                        <a href="{{ route($queue['route']) }}" class="admin-stat">
                            <span class="admin-stat__value">{{ $queue['count'] }}</span>
                            <span class="admin-stat__label">@lang($queue['key'])</span>
                        </a>
                    </div>
                @endif
            @endforeach
        </div>

        {{-- --- The headline figures ---------------------------------------- --}}
        <div class="row g-3 mb-4">
            <div class="col-sm-6 col-xl-4">
                {{-- Takings first: it is the figure the dashboard exists to
                     report, and it is the one that cannot be reconstructed from
                     any other page. `dir="ltr"` is applied by `.admin-num` for
                     the same reason it is on the pricing cards — a currency
                     figure next to Latin digits is reordered under RTL. --}}
                <div class="admin-stat">
                    <span class="admin-stat__value">{{ \App\Support\Money::format($revenue, $currency) }}</span>
                    <span class="admin-stat__label">@lang('admin.revenue')</span>
                </div>
            </div>

            <div class="col-sm-6 col-xl-4">
                <a href="{{ route('admin.orders.index') }}" class="admin-stat">
                    <span class="admin-stat__value">{{ $stats['orders'] }}</span>
                    <span class="admin-stat__label">@lang('admin.stats.orders')</span>
                    <span class="admin-stat__hint">
                        @lang('admin.stats.paid_orders') : {{ $stats['paidOrders'] }}
                    </span>
                </a>
            </div>

            <div class="col-sm-6 col-xl-4">
                <a href="{{ route('admin.participants.index') }}" class="admin-stat">
                    <span class="admin-stat__value">{{ $stats['participants'] }}</span>
                    <span class="admin-stat__label">@lang('admin.stats.participants')</span>
                    <span class="admin-stat__hint">
                        @lang('admin.stats.checked_in') : {{ $stats['checkedIn'] }}
                    </span>
                </a>
            </div>

            <div class="col-sm-6 col-xl-4">
                {{-- Places sold is the conversion figure: registrations can
                     exist without attendees attached, so this is not the same
                     number as the card above and is shown as its own thing
                     rather than derived from it. --}}
                <div class="admin-stat">
                    <span class="admin-stat__value">{{ $salesByType->sum('total') }}</span>
                    <span class="admin-stat__label">@lang('admin.sales.title')</span>
                </div>
            </div>

            <div class="col-sm-6 col-xl-4">
                {{-- The paid rate is the health of the funnel: registrations can
                     pile up unpaid, and the raw count does not show it. --}}
                <div class="admin-stat">
                    <span class="admin-stat__value">{{ $paidRate }}%</span>
                    <span class="admin-stat__label">@lang('admin.stats.paid_rate')</span>
                    <span class="admin-stat__hint">
                        @lang('admin.stats.paid_rate_hint', ['paid' => $stats['paidOrders'], 'total' => $stats['orders']])
                    </span>
                </div>
            </div>

            <div class="col-sm-6 col-xl-4">
                <div class="admin-stat">
                    <span class="admin-stat__value">{{ \App\Support\Money::format($averageOrder, $currency) }}</span>
                    <span class="admin-stat__label">@lang('admin.stats.average_order')</span>
                </div>
            </div>
        </div>

        {{-- --- Registrations over time, and the member split ---------------- --}}
        <div class="row g-4 mb-4">

            {{-- The run of registrations, cumulative. Drawn here rather than by
                 a charting library: two polylines in a stretched viewBox are a
                 static image of the data, and a dependency for decoration is
                 not worth its maintenance. --}}
            <div class="col-xl-8">
                <div class="admin-card h-100">
                    <div class="admin-card__head">
                        <h2 class="admin-card__title">@lang('admin.trend.title')</h2>
                        <div class="admin-chart__legend">
                            <span class="admin-chart__key admin-chart__key--orders">@lang('admin.trend.legend_orders')</span>
                            <span class="admin-chart__key admin-chart__key--paid">@lang('admin.trend.legend_paid')</span>
                        </div>
                    </div>

                    <div class="admin-card__body">
                        @if ($trend['totalOrders'] === 0)
                            <p class="admin-empty">@lang('admin.trend.empty')</p>
                        @else
                            @php
                                // The polyline is drawn in a 0–100 box and
                                // stretched to the panel width, so the geometry
                                // is worked out here rather than in JavaScript.
                                // 92 units of headroom keeps the peak clear of
                                // the top edge; x is spread evenly across the
                                // series so unevenly spaced days still read as a
                                // continuous run.
                                $days = $trend['days'];
                                $count = count($days);
                                $max = max(1, $trend['totalOrders']);
                                $coord = function (int $i, int $value) use ($count, $max): string {
                                    $x = $count <= 1 ? 50 : ($i / ($count - 1)) * 100;
                                    $y = 100 - ($value / $max) * 92;

                                    return round($x, 2).','.round($y, 2);
                                };
                                $ordersPoints = [];
                                $paidPoints = [];
                                foreach ($days as $i => $day) {
                                    $ordersPoints[] = $coord($i, $day['ordersCum']);
                                    $paidPoints[] = $coord($i, $day['paidCum']);
                                }
                                $areaPoints = '0,100 '.implode(' ', $ordersPoints).' 100,100';
                            @endphp

                            <div class="admin-chart">
                                <svg viewBox="0 0 100 100" preserveAspectRatio="none" role="img"
                                     aria-label="@lang('admin.trend.total', ['count' => $trend['totalOrders']])">
                                    <polygon class="admin-chart__area" points="{{ $areaPoints }}" />
                                    <polyline class="admin-chart__line admin-chart__line--orders"
                                              points="{{ implode(' ', $ordersPoints) }}"
                                              vector-effect="non-scaling-stroke" />
                                    <polyline class="admin-chart__line admin-chart__line--paid"
                                              points="{{ implode(' ', $paidPoints) }}"
                                              vector-effect="non-scaling-stroke" />
                                </svg>

                                <div class="admin-chart__axis">
                                    <span>{{ $days[0]['label'] }}</span>
                                    <span class="admin-num">@lang('admin.trend.total', ['count' => $trend['totalOrders']])</span>
                                    <span>{{ $days[$count - 1]['label'] }}</span>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- The member split. Two shares of the same total, so the bars are
                 percentages of the whole rather than of the larger side. --}}
            <div class="col-xl-4">
                <div class="admin-card h-100">
                    <div class="admin-card__head">
                        <h2 class="admin-card__title">@lang('admin.members.title')</h2>
                    </div>

                    <div class="admin-card__body">
                        @if ($memberSplit->total === 0)
                            <p class="admin-empty">@lang('admin.members.none')</p>
                        @else
                            <div class="admin-split">
                                <div class="admin-split__row">
                                    <span class="admin-split__label">@lang('admin.members.member')</span>
                                    <span class="admin-split__value admin-num">{{ $memberSplit->member }}</span>
                                </div>
                                <div class="admin-split__track">
                                    <span class="admin-split__fill admin-split__fill--member"
                                          style="inline-size: {{ $memberSplit->percentMember }}%"></span>
                                </div>

                                <div class="admin-split__row">
                                    <span class="admin-split__label">@lang('admin.members.standard')</span>
                                    <span class="admin-split__value admin-num">{{ $memberSplit->standard }}</span>
                                </div>
                                <div class="admin-split__track">
                                    <span class="admin-split__fill admin-split__fill--standard"
                                          style="inline-size: {{ 100 - $memberSplit->percentMember }}%"></span>
                                </div>
                            </div>

                            <p class="admin-meta admin-split__summary">
                                @lang('admin.members.summary', ['member' => $memberSplit->member, 'standard' => $memberSplit->standard])
                            </p>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4 mb-4">

            {{-- --- Places sold per tariff ----------------------------------- --}}
            <div class="col-xl-7">
                <div class="admin-card h-100">
                    <div class="admin-card__head">
                        <h2 class="admin-card__title">@lang('admin.sales.title')</h2>
                    </div>

                    <div class="admin-card__body admin-card__body--flush">
                        @if ($salesByType->isEmpty())
                            <p class="admin-empty">@lang('admin.sales.none')</p>
                        @else
                            <div class="admin-table-wrap">
                                <table class="admin-table">
                                    <caption class="visually-hidden">@lang('admin.sales.title')</caption>
                                    <thead>
                                        <tr>
                                            <th scope="col">@lang('admin.sales.tariff')</th>
                                            <th scope="col">@lang('admin.sales.member')</th>
                                            <th scope="col">@lang('admin.sales.standard')</th>
                                            <th scope="col">@lang('admin.sales.total')</th>
                                            <th scope="col">@lang('admin.sales.revenue')</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($salesByType as $row)
                                            <tr>
                                                {{-- The tariff name is the cast
                                                     TranslatedString, so it
                                                     renders in the operator's
                                                     language rather than in
                                                     whichever locale happened
                                                     to be stored first. --}}
                                                <td>{{ $row->type->name }}</td>
                                                <td class="admin-num">{{ $row->member }}</td>
                                                <td class="admin-num">{{ $row->standard }}</td>
                                                <td class="admin-num fw-bold">{{ $row->total }}</td>
                                                <td class="admin-num">
                                                    {{ $row->type->formatAmount($row->revenue) }}
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- --- Registrations by status ------------------------------------ --}}
            <div class="col-xl-5">
                <div class="admin-card h-100">
                    <div class="admin-card__head">
                        <h2 class="admin-card__title">@lang('admin.orders_by_status')</h2>
                    </div>

                    <div class="admin-card__body admin-card__body--flush">
                        {{-- Every state in the enum is listed, including the ones
                             with no orders. "Is anything stuck?" is answered by
                             seeing Awaiting Payment at zero; a breakdown that
                             omits the empty states cannot answer it. Each row
                             links to the filtered list, so this panel is a set
                             of shortcuts rather than a dead summary. --}}
                        <ul class="list-unstyled mb-0">
                            @foreach ($ordersByStatus as $value => $count)
                                @php
                                    $enum = \App\Enums\OrderStatus::from($value);
                                @endphp
                                <li>
                                    <a href="{{ route('admin.orders.index', ['status' => $value]) }}"
                                       class="d-flex align-items-center gap-3 px-3 py-2 text-decoration-none"
                                       style="color: inherit;">
                                        <span class="admin-pill admin-pill--{{ $enum->colour() }}">
                                            {{ $enum->label($locale->value) }}
                                        </span>
                                        <span class="ms-auto admin-num fw-bold">{{ $count }}</span>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        {{-- --- Capacity, and where buyers come from ------------------------- --}}
        <div class="row g-4 mb-4">

            {{-- How full each tariff is. max_quantity of zero means uncapped, so
                 that row reports a sold count and no gauge rather than a bar
                 pinned at a meaningless 0%. --}}
            <div class="col-xl-7">
                <div class="admin-card h-100">
                    <div class="admin-card__head">
                        <h2 class="admin-card__title">@lang('admin.capacity.title')</h2>
                    </div>

                    <div class="admin-card__body">
                        @if ($capacity->isEmpty())
                            <p class="admin-empty">@lang('admin.capacity.empty')</p>
                        @else
                            <ul class="admin-gauge__list" role="list">
                                @foreach ($capacity as $row)
                                    <li class="admin-gauge">
                                        <div class="admin-gauge__head">
                                            <span class="admin-gauge__name">{{ $row->type->name }}</span>
                                            <span class="admin-gauge__count admin-num">
                                                @if ($row->unlimited)
                                                    @lang('admin.capacity.sold', ['sold' => $row->sold])
                                                @else
                                                    @lang('admin.capacity.sold_of', ['sold' => $row->sold, 'max' => $row->max])
                                                @endif
                                            </span>
                                        </div>

                                        @unless ($row->unlimited)
                                            <div class="admin-gauge__track"
                                                 role="progressbar"
                                                 aria-valuemin="0"
                                                 aria-valuemax="{{ $row->max }}"
                                                 aria-valuenow="{{ $row->sold }}"
                                                 aria-label="{{ $row->type->name }}">
                                                <span class="admin-gauge__fill @if ($row->full) admin-gauge__fill--full @endif"
                                                      style="inline-size: {{ $row->percent }}%"></span>
                                            </div>
                                        @endunless

                                        <p class="admin-gauge__meta">
                                            @if ($row->unlimited)
                                                @lang('admin.capacity.unlimited')
                                            @elseif ($row->full)
                                                @lang('admin.capacity.full')
                                            @else
                                                @lang('admin.capacity.remaining', ['count' => $row->remaining])
                                            @endif
                                        </p>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Where the buyers come from, by paid registration. The bars are
                 proportional to the busiest country so the list reads as a
                 ranking rather than eight near-full rows. --}}
            <div class="col-xl-5">
                <div class="admin-card h-100">
                    <div class="admin-card__head">
                        <h2 class="admin-card__title">@lang('admin.countries.title')</h2>
                    </div>

                    <div class="admin-card__body admin-card__body--flush">
                        @if ($countries->isEmpty())
                            <p class="admin-empty">@lang('admin.countries.empty')</p>
                        @else
                            @php $countryMax = max(1, (int) $countries->max('registrations')); @endphp
                            <ul class="admin-country" role="list">
                                @foreach ($countries as $country)
                                    <li class="admin-country__row">
                                        <span class="admin-country__name">{{ $country->name }}</span>
                                        <span class="admin-country__bar" aria-hidden="true">
                                            <span class="admin-country__bar-fill"
                                                  style="inline-size: {{ (int) round($country->registrations / $countryMax * 100) }}%"></span>
                                        </span>
                                        <span class="admin-country__value admin-num">{{ $country->registrations }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- --- Latest registrations ------------------------------------------ --}}
        <div class="admin-card">
            <div class="admin-card__head">
                <h2 class="admin-card__title">@lang('admin.recent_orders.title')</h2>
                <a href="{{ route('admin.orders.index') }}" class="btn btn-sm btn-outline-light">
                    @lang('admin.recent_orders.all')
                </a>
            </div>

            <div class="admin-card__body admin-card__body--flush">
                @if ($recentOrders->isEmpty())
                    <p class="admin-empty">@lang('admin.recent_orders.empty')</p>
                @else
                    <div class="admin-table-wrap">
                        <table class="admin-table">
                            <caption class="visually-hidden">@lang('admin.recent_orders.title')</caption>
                            <thead>
                                <tr>
                                    <th scope="col">@lang('admin.recent_orders.reference')</th>
                                    <th scope="col">@lang('admin.recent_orders.buyer')</th>
                                    <th scope="col">@lang('admin.recent_orders.total')</th>
                                    <th scope="col">@lang('admin.recent_orders.status')</th>
                                    <th scope="col">@lang('admin.recent_orders.placed_on')</th>
                                    <th scope="col"><span class="visually-hidden">@lang('admin.recent_orders.view')</span></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($recentOrders as $order)
                                    <tr>
                                        {{-- The reference links, rather than the
                                             row: a row-sized link makes the
                                             click target ambiguous about
                                             which cell is the link, and the
                                             browser status bar then shows the
                                             wrong URL for the cell under the
                                             cursor. --}}
                                        <td class="admin-num">
                                            <a href="{{ route('admin.orders.show', $order) }}"
                                               class="text-decoration-none">
                                                {{ $order->reference }}
                                            </a>
                                        </td>
                                        <td>{{ $order->user?->displayName() ?? __('admin.orders.index.anonymous') }}</td>
                                        <td class="admin-num">{{ $order->formattedTotal() }}</td>
                                        <td>
                                            <span class="admin-pill admin-pill--{{ $order->status->colour() }}">
                                                {{ $order->status->label($locale->value) }}
                                            </span>
                                        </td>
                                        <td class="admin-num">
                                            {{ $order->created_at->translatedFormat('d/m/Y H:i') }}
                                        </td>
                                        <td>
                                            <a href="{{ route('admin.orders.show', $order) }}"
                                               class="btn btn-sm btn-outline-light">
                                                @lang('admin.recent_orders.view')
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>

    @endif

@endsection