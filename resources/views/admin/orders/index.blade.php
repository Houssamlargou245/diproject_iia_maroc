@extends('layouts.admin')

@section('title', __('admin.orders.index.title'))

@section('heading', __('admin.orders.index.title'))

@section('content')

    <p class="admin-meta mb-4">@lang('admin.orders.index.lede')</p>

    {{-- The filter is a GET form rather than a row of links so it survives a
         reload, and it submits with Enter from the field without a button being
         pressed. Its action is the current URL with no query string, which keeps
         it correct if the locale prefix ever changes. --}}
    <form method="GET" action="{{ url()->current() }}" class="admin-form mb-4">
        <div class="admin-field">
            <label for="status">@lang('admin.orders.index.filter')</label>
            <select name="status" id="status" class="admin-select">
                <option value="">@lang('admin.orders.index.all')</option>
                @foreach ($statuses as $option)
                    {{-- `$option` is the enum case; the value is the backing
                         string so the URL filter is stable across renames of
                         the PHP constant. --}}
                    <option value="{{ $option->value }}" @selected($status === $option)>
                        {{ $option->label($locale->value) }}
                    </option>
                @endforeach
            </select>
        </div>

        <button type="submit" class="btn btn-primary">@lang('admin.orders.index.filter')</button>

        @if ($status !== null)
            {{-- A way back to the unfiltered list. Without it, choosing a status
                 that returns nothing is a dead end. --}}
            <a href="{{ route('admin.orders.index') }}" class="btn btn-outline-light">
                @lang('admin.orders.index.all')
            </a>
        @endif
    </form>

    <div class="admin-card">
        <div class="admin-card__body admin-card__body--flush">
            @if ($orders->isEmpty())
                <p class="admin-empty">@lang('admin.orders.index.empty')</p>
            @else
                <div class="admin-table-wrap">
                    <table class="admin-table">
                        <caption class="visually-hidden">@lang('admin.orders.index.title')</caption>
                        <thead>
                            <tr>
                                <th scope="col">@lang('admin.recent_orders.reference')</th>
                                <th scope="col">@lang('admin.recent_orders.buyer')</th>
                                <th scope="col">@lang('admin.recent_orders.placed_on')</th>
                                <th scope="col">@lang('admin.recent_orders.total')</th>
                                <th scope="col">@lang('admin.recent_orders.status')</th>
                                <th scope="col"><span class="visually-hidden">@lang('admin.recent_orders.view')</span></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($orders as $order)
                                <tr>
                                    <td class="admin-num">
                                        <a href="{{ route('admin.orders.show', $order) }}" class="text-decoration-none">
                                            {{ $order->reference }}
                                        </a>
                                    </td>
                                    <td>{{ $order->user?->displayName() ?? __('admin.orders.index.anonymous') }}</td>
                                    <td class="admin-num">{{ $order->created_at->translatedFormat('d/m/Y H:i') }}</td>
                                    <td class="admin-num">{{ $order->formattedTotal() }}</td>
                                    <td>
                                        <span class="admin-pill admin-pill--{{ $order->status->colour() }}">
                                            {{ $order->status->label($locale->value) }}
                                        </span>
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

                {{-- Bootstrap's pager, registered as the framework default in
                     AppServiceProvider. Both layouts load `bootstrap.min.css`
                     and no Tailwind build, so the framework's default pager
                     rendered a wall of unstyled classes. `withQueryString()` in
                     the controller keeps the status filter attached. --}}
                @if ($orders->hasPages())
                    <div class="p-3 border-top" style="border-color: rgba(255,255,255,.07) !important;">
                        {{ $orders->links() }}
                    </div>
                @endif
            @endif
        </div>
    </div>

@endsection