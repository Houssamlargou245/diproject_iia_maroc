@extends('layouts.admin')

@section('title', __('admin.orders.show.title').' '.$order->reference)

@section('heading', $order->reference)

@section('content')

    <div class="row g-4">

        {{-- --- The facts --------------------------------------------------- --}}
        <div class="col-lg-7">

            <div class="admin-card mb-4">
                <div class="admin-card__head">
                    <h2 class="admin-card__title">@lang('admin.orders.show.buyer')</h2>
                    <span class="admin-pill admin-pill--{{ $order->status->colour() }}">
                        {{ $order->status->label($locale->value) }}
                    </span>
                </div>
                <div class="admin-card__body">
                    <dl class="row mb-0">
                        <dt class="col-sm-4 admin-meta">@lang('admin.orders.show.reference')</dt>
                        <dd class="col-sm-8 admin-num">{{ $order->reference }}</dd>

                        <dt class="col-sm-4 admin-meta">@lang('admin.orders.show.placed_on')</dt>
                        <dd class="col-sm-8 admin-num">
                            {{ $order->created_at->translatedFormat('d/m/Y H:i') }}
                        </dd>

                        {{-- Absent rather than shown empty: an order with no
                             invoice number yet has not failed to generate one,
                             it simply has not been invoiced, and a blank cell
                             reads as a bug. --}}
                        @if ($order->invoice_number)
                            <dt class="col-sm-4 admin-meta">@lang('admin.orders.show.invoice')</dt>
                            <dd class="col-sm-8 admin-num">{{ $order->invoice_number }}</dd>
                        @endif

                        <dt class="col-sm-4 admin-meta">@lang('admin.orders.show.contact')</dt>
                        <dd class="col-sm-8">
                            @if ($order->user)
                                {{ $order->user->email }}
                                @if ($order->user->phone)
                                    <br><span class="admin-num">{{ $order->user->phone }}</span>
                                @endif
                            @else
                                {{-- A guest order has no account; the billing
                                     block captured the contact details instead,
                                     and those are shown on the panel below. --}}
                                {{ __('admin.orders.index.anonymous') }}
                            @endif
                        </dd>
                    </dl>
                </div>
            </div>

            <div class="admin-card mb-4">
                <div class="admin-card__head">
                    <h2 class="admin-card__title">@lang('admin.orders.show.items')</h2>
                </div>
                <div class="admin-card__body admin-card__body--flush">
                    <div class="admin-table-wrap">
                        <table class="admin-table">
                            <caption class="visually-hidden">@lang('admin.orders.show.items')</caption>
                            <thead>
                                <tr>
                                    <th scope="col">@lang('order.ticket')</th>
                                    <th scope="col">@lang('order.quantity')</th>
                                    <th scope="col">@lang('order.member_places')</th>
                                    <th scope="col">@lang('admin.recent_orders.total')</th>
                                </tr>
                            </thead>
                            <tbody>
                                {{-- `items` is the priced line, and it carries
                                     its own `label` snapshot and the two unit
                                     prices actually charged. Reading the tariff's
                                     current price instead would restate an old
                                     order at today's rate, which is exactly the
                                     mistake that makes a reconciliation
                                     disagree with the bank. --}}
                                @foreach ($order->items as $item)
                                    <tr>
                                        <td>{{ $item->label }}</td>
                                        <td class="admin-num">{{ $item->totalQuantity() }}</td>
                                        <td class="admin-num">{{ $item->member_quantity }}</td>
                                        <td class="admin-num fw-bold">{{ $item->formattedLineTotal($order->currency) }}</td>
                                    </tr>
                                @endforeach

                                <tr>
                                    <td colspan="3" class="text-end fw-bold">@lang('admin.recent_orders.total')</td>
                                    <td class="admin-num fw-bold">{{ $order->formattedTotal() }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="admin-card mb-4">
                <div class="admin-card__head">
                    <h2 class="admin-card__title">@lang('admin.orders.show.participants')</h2>
                </div>
                <div class="admin-card__body admin-card__body--flush">
                    @if ($order->participants->isEmpty())
                        <p class="admin-empty">@lang('admin.sales.none')</p>
                    @else
                        <div class="admin-table-wrap">
                            <table class="admin-table">
                                <caption class="visually-hidden">@lang('admin.orders.show.participants')</caption>
                                <thead>
                                    <tr>
                                        <th scope="col">@lang('admin.participants.name')</th>
                                        <th scope="col">@lang('admin.participants.job_title')</th>
                                        <th scope="col">@lang('admin.participants.badge')</th>
                                        <th scope="col">@lang('admin.participants.status')</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($order->participants as $participant)
                                        <tr>
                                            <td>{{ $participant->full_name }}</td>
                                            <td>{{ $participant->job_title ?: '—' }}</td>
                                            <td>{{ $participant->badgeLabel() }}</td>
                                            <td>
                                                @if ($participant->checked_in)
                                                    <span class="admin-pill admin-pill--success">
                                                        @lang('admin.participants.checked_in')
                                                    </span>
                                                @else
                                                    <span class="admin-pill admin-pill--muted">
                                                        @lang('admin.participants.not_checked_in')
                                                    </span>
                                                @endif
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

        {{-- --- The action panel --------------------------------------------- --}}
        <div class="col-lg-5">

            <div class="admin-card mb-4">
                <div class="admin-card__head">
                    <h2 class="admin-card__title">@lang('admin.orders.show.payments')</h2>
                </div>
                <div class="admin-card__body">
                    {{-- Every attempt is listed, failed ones included: a failed
                         attempt carries the gateway's reason, and that is the
                         only place it survives once the operator has left the
                         return page. The pill is coloured by whether the
                         attempt actually took the money, so a `pending` row
                         that nevertheless carries an authorisation code and a
                         transaction id reads green — that is the callback that
                         never landed, not an unfinished checkout. --}}
                    @if ($order->payments->isEmpty())
                        <p class="admin-meta mb-0">@lang('admin.orders.show.none')</p>
                    @else
                        <ul class="list-unstyled mb-0">
                            @foreach ($order->payments as $payment)
                                <li class="py-2 border-bottom" style="border-color: rgba(255,255,255,.06) !important;">
                                    {{-- The gateway reference is the join key to
                                         a CMI settlement report, so it is shown
                                         verbatim and monospaced rather than
                                         truncated: an operator comparing this
                                         screen against a bank file needs it
                                         character for character. --}}
                                    <div class="d-flex justify-content-between gap-3">
                                        <span class="admin-pill admin-pill--{{ $payment->isSuccessful() ? 'success' : ($payment->status === \App\Enums\PaymentStatus::Failed ? 'danger' : 'muted') }}">
                                            @lang("admin.stages.payment_status.{$payment->status->value}")
                                        </span>
                                        <span class="admin-num fw-bold">{{ $payment->formattedAmount() }}</span>
                                    </div>

                                    <div class="admin-meta mt-1">
                                        {{ $payment->driver }}
                                        @if ($payment->cardLabel())
                                            &middot; {{ $payment->cardLabel() }}
                                        @endif
                                    </div>

                                    @if ($payment->gateway_reference)
                                        <div class="admin-num admin-meta">{{ $payment->gateway_reference }}</div>
                                    @endif

                                    {{-- A failed attempt carries the reason, and
                                         it is the only place that reason exists
                                         once the operator has left the payment
                                         return page. --}}
                                    @if ($payment->error_message)
                                        <div class="ux-notice ux-notice--danger mt-2" role="note">
                                            {{ $payment->error_message }}
                                        </div>
                                    @endif

                                    @if ($payment->settled_at)
                                        <div class="admin-num admin-meta mt-1">
                                            {{ $payment->settled_at->translatedFormat('d/m/Y H:i') }}
                                        </div>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>

            {{-- --- Status change ----------------------------------------------
                 Only the legal next states are offered, taken from
                 `$allowed` (which comes from the enum). A terminal order gets
                 an explanation rather than an empty select, because an operator
                 looking at a cancelled order needs to know that is deliberate
                 and not a page that failed to load. --}}
            <div class="admin-card mb-4">
                <div class="admin-card__head">
                    <h2 class="admin-card__title">@lang('admin.orders.change_status')</h2>
                </div>
                <div class="admin-card__body">
                    @if ($allowed === [])
                        <p class="admin-meta mb-0">
                            @lang('admin.orders.illegal_transition', [
                                'from' => $order->status->label($locale->value),
                                'to' => '—',
                            ])
                        </p>
                    @else
                        <form method="POST"
                              action="{{ route('admin.orders.status', $order) }}"
                              class="d-flex flex-column gap-3">
                            @csrf

                            <div class="admin-field">
                                <label for="status">@lang('admin.orders.change_status')</label>
                                <select name="status" id="status" class="admin-select" required>
                                    @foreach ($allowed as $option)
                                        <option value="{{ $option->value }}">{{ $option->label($locale->value) }}</option>
                                    @endforeach
                                </select>
                                @error('status')
                                    <span class="ux-notice ux-notice--danger" role="alert">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="admin-field">
                                <label for="notes">@lang('admin.orders.notes')</label>
                                <textarea name="notes" id="notes" class="admin-textarea"
                                          placeholder="{{ __('admin.orders.show.no_notes') }}"></textarea>
                                @error('notes')
                                    <span class="ux-notice ux-notice--danger" role="alert">{{ $message }}</span>
                                @enderror
                            </div>

                            <button type="submit" class="btn btn-primary align-self-start">
                                @lang('admin.orders.change_status')
                            </button>
                        </form>
                    @endif
                </div>
            </div>

            {{-- --- History ----------------------------------------------------- --}}
            <div class="admin-card">
                <div class="admin-card__head">
                    <h2 class="admin-card__title">@lang('admin.orders.show.timeline')</h2>
                </div>
                <div class="admin-card__body">
                    @if ($order->notes)
                        {{-- `whitespace-pre-wrap` keeps the operator's own line
                             breaks, and `overflow-wrap` stops a long reference
                             widening the panel on a narrow desk screen. --}}
                        <div class="admin-prose">{{ $order->notes }}</div>
                    @else
                        <p class="admin-meta mb-0">@lang('admin.orders.show.no_notes')</p>
                    @endif
                </div>
            </div>

        </div>
    </div>

@endsection