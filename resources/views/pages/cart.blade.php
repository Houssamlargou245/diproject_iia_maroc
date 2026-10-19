{{--
    The basket.

    Two columns on a desktop, one on a phone, and the summary stays first in the
    DOM on a phone by being the sticky column: the total and the call to action
    are the only two things a delegate needs on a screen they are scrolling with
    one thumb.

    The lines are tiles rather than a table. A table is the right semantic for a
    spreadsheet and the wrong one for three rows on a phone: a five-column table
    scrolls sideways on a narrow screen, and the quantity control — the only thing
    a delegate actually interacts with — ends up off-screen. Each line is its own
    card so it reflows instead of scrolling.

    Each line also keeps its own form rather than sharing one for the page, so a
    validation error on one line cannot silently discard edits made to another.

    The summary panel carries the printable and downloadable document as well as
    the checkout button, because the basket is the last screen on which a company
    delegate still needs a document to take to their finance department — after
    the checkout the money is already moving.
--}}
@extends('layouts.app')

@section('title', __('order.cart.title'))
@section('description', __('order.cart.subtitle'))

@section('content')

{{-- `$currentEdition`, not `$edition`: AppServiceProvider shares it under
     that name on every view, and CartController does not pass an `$edition` of
     its own. `Edition::current()` returns null when nothing is published, so
     every use is null-safe — an unpublished edition renders the page without
     the year band rather than a 500. --}}
@php
    $places = (int) $cart->items->sum('quantity');
    $registrationOpen = (bool) ($currentEdition?->registration_open ?? false);
@endphp

<div class="d-page d-page--cart">



    {{-- ---------------------------------------------------------------------
        Success and error notices, above everything rather than inside the
        summary: a delegate who just emptied their basket should not have to
        scroll to the bottom of the page to find out it worked.
    --------------------------------------------------------------------- --}}
    @if (session('status') || $errors->any())
        <div class="container d-notices">

            @if ($errors->any())
                <div class="d-notice d-notice--danger" role="alert">
                    <i class="fas fa-exclamation-triangle" aria-hidden="true"></i>
                    <ul>
                        @foreach ($errors->all() as $message)
                            <li>{{ $message }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>
    @endif

    <section class="d-section" aria-labelledby="cart-lines-heading">
        <div class="container">
            <div class="d-cart">

                {{-- -----------------------------------------------------------------
                    The lines.
                ----------------------------------------------------------------- --}}
                <div class="d-cart__lines" ux-reveal>

                    <h2 id="cart-lines-heading" class="d-cart__heading">
                        @lang('order.summary')
                    </h2>

                    <ul class="d-cart__list list-unstyled" data-ux-stagger="70">
                        @foreach ($cart->items as $item)
                            <li class="ux-reveal">
                                <article class="d-cart-line">

                                    <div class="d-cart-line__id">
                                        <span class="d-cart-line__badge" aria-hidden="true">
                                            <i class="fas fa-ticket-alt"></i>
                                        </span>

                                        <div>
                                            <h3 class="d-cart-line__title">
                                                {{ $item->ticketType?->name ?? __('order.ticket') }}
                                            </h3>

                                            @if ($item->ticketType?->description)
                                                <p class="d-cart-line__desc">
                                                    {{ $item->ticketType->description }}
                                                </p>
                                            @endif

                                            @if ($item->member_quantity > 0)
                                                <p class="d-cart-line__meta">
                                                    <i class="fas fa-id-badge" aria-hidden="true"></i>
                                                    <span>
                                                        {{ $item->member_quantity === 1
                                                            ? __('order.cart.member_places', ['count' => 1])
                                                            : __('order.cart.member_places_plural', ['count' => $item->member_quantity]) }}
                                                    </span>
                                                </p>
                                            @endif
                                        </div>
                                    </div>

                                    <div class="d-cart-line__controls">
                                        <form method="POST"
                                              action="{{ route('cart.update', ['cart' => $cart->id, 'ticketType' => $item->ticket_type_id]) }}"
                                              class="d-cart-line__qty">
                                            @csrf

                                            <label class="d-cart-line__qty-label"
                                                   for="qty-{{ $item->id }}">
                                                @lang('order.quantity')
                                            </label>

                                            {{-- A stepper rather than a bare number input: a
                                                 quantity is the one field on this page a
                                                 delegate edits on a phone, and typing a
                                                 digit into a 5rem box is a worse way to
                                                 change 3 into 4 than tapping a button.
                                                 The input stays a real number input —
                                                 hidden from the tab order rather than
                                                 removed, so the value is still submitted
                                                 and still announced. --}}
                                            <div class="d-stepper">
                                                <button type="button"
                                                        class="d-stepper__btn"
                                                        data-stepper-down
                                                        aria-label="{{ __('action.decrease') }}"
                                                        @disabled($item->quantity <= 1)>
                                                    <i class="fas fa-minus" aria-hidden="true"></i>
                                                </button>

                                                <input type="number" id="qty-{{ $item->id }}"
                                                       name="quantity"
                                                       value="{{ $item->quantity }}"
                                                       min="0" max="20"
                                                       data-stepper-input
                                                       class="d-stepper__input"
                                                       aria-label="{{ __('order.quantity') }}">

                                                <button type="button"
                                                        class="d-stepper__btn"
                                                        data-stepper-up
                                                        aria-label="{{ __('action.increase') }}">
                                                    <i class="fas fa-plus" aria-hidden="true"></i>
                                                </button>
                                            </div>

                                            <input type="hidden" name="member_quantity"
                                                   value="{{ $item->member_quantity }}">

                                            <button type="submit" class="d-btn d-btn--quiet">
                                                <span>@lang('action.update')</span>
                                            </button>
                                        </form>

                                        <div class="d-cart-line__price">
                                            {{-- dir="ltr": an amount beside a currency code
                                                 must not be bidi-reordered on the Arabic
                                                 page. --}}
                                            <p class="d-cart-line__total" dir="ltr">
                                                {{ $item->formattedEstimatedTotal() }}
                                            </p>

                                            <form method="POST"
                                                  action="{{ route('cart.destroy', ['cart' => $cart->id, 'ticketType' => $item->ticket_type_id]) }}">
                                                @csrf
                                                <button type="submit"
                                                        class="d-btn d-btn--danger">
                                                    <i class="fas fa-trash-alt" aria-hidden="true"></i>
                                                    <span>@lang('action.remove')</span>
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </article>
                            </li>
                        @endforeach
                    </ul>

                    {{-- The estimate is explicitly labelled as one. The authoritative
                         figure is the quote at checkout, because that is where the
                         member rate is applied from the server's own membership record
                         rather than from what the visitor claimed. --}}
                    <p class="d-cart__note">
                        <i class="fas fa-info-circle" aria-hidden="true"></i>
                        <span>@lang('order.cart.estimate_note')</span>
                    </p>

                    <div class="d-cart__actions">
                        <a href="{{ route('pricing') }}" class="d-btn d-btn--ghost">
                            <i class="fas fa-arrow-left" aria-hidden="true"></i>
                            <span>@lang('order.cart.continue')</span>
                        </a>

                        <form method="POST" action="{{ route('cart.clear') }}">
                            @csrf
                            <button type="submit" class="d-btn d-btn--danger-ghost">
                                <i class="fas fa-broom" aria-hidden="true"></i>
                                <span>@lang('order.cart.clear')</span>
                            </button>
                        </form>
                    </div>
                </div>

                {{-- -----------------------------------------------------------------
                    The summary.

                    On a desktop it is a sticky column: a delegate comparing two
                    tariffs can change a quantity and watch the total move without
                    losing their place.
                ----------------------------------------------------------------- --}}
                <aside class="d-cart__aside" ux-reveal>

                    <div class="d-cart-summary">
                        <h2 class="d-cart-summary__title">@lang('order.summary')</h2>

                        <p class="d-cart-summary__note">@lang('order.cart.summary_note')</p>

                        <p class="d-cart-summary__total" dir="ltr">
                            {{ \App\Support\Money::format($estimatedTotal, $currency) }}
                        </p>

                        <p class="d-cart-summary__label">@lang('order.cart.estimate')</p>

                        {{-- Both paths lead to the same place. Signed out, the checkout
                             sends the visitor to sign in and keeps the basket, so they
                             come back to it. --}}
                        <a href="{{ auth()->check() ? route('checkout') : route('login') }}"
                           class="d-btn d-btn--primary d-btn--block">
                            <span>@lang('order.cart.checkout')</span>
                            <i class="fas fa-arrow-right d-btn__arrow" aria-hidden="true"></i>
                        </a>

                        {{-- Payment rehearsal, visible only to the one account named in
                             config('conference.payment_test_user'). It raises a real 1 MAD
                             order and jumps straight to the gateway, so the CMI round trip
                             can be tested without a delegate ever seeing the button. --}}
                        @if (auth()->check() && auth()->user()->email
                            && strcasecmp(auth()->user()->email, (string) config('conference.payment_test_user', 'admin@arabcia.test')) === 0)
                            <form method="POST" action="{{ route('checkout.test') }}" class="mt-3">
                                @csrf
                                <button type="submit" class="d-btn d-btn--quiet d-btn--block">
                                    <i class="fas fa-vial" aria-hidden="true"></i>
                                    <span>Test paiement 1 DH</span>
                                </button>
                            </form>

                            {{-- The same rehearsal in euros (0,09 € ≈ 1 MAD), to exercise
                                 the international-card path and the gateway's display of a
                                 non-MAD amount. --}}
                            <form method="POST" action="{{ route('checkout.test.eur') }}" class="mt-3">
                                @csrf
                                <button type="submit" class="d-btn d-btn--quiet d-btn--block">
                                    <i class="fas fa-vial" aria-hidden="true"></i>
                                    <span>Test paiement 0,09 €</span>
                                </button>
                            </form>
                        @endif

                        @guest
                            <p class="d-cart-summary__hint">
                                <i class="fas fa-lock" aria-hidden="true"></i>
                                <span>@lang('order.cart.sign_in_note')</span>
                            </p>
                        @endguest

                        {{-- -----------------------------------------------------------------
                            The document.

                            Both are GET and neither changes anything, so they are links
                            rather than a form. The PDF is the file to forward; the print
                            view is for someone who wants a copy now.
                        ----------------------------------------------------------------- --}}
                        <div class="d-cart-invoice">
                            <p class="d-cart-invoice__title">
                                <i class="fas fa-file-invoice" aria-hidden="true"></i>
                                @lang('order.cart.invoice')
                            </p>

                            <p class="d-cart-invoice__lede">
                                @lang('order.cart.invoice_lede')
                            </p>

                            <div class="d-cart-invoice__actions">
                                <a href="{{ route('cart.proforma') }}" class="d-btn d-btn--quiet">
                                    <i class="fas fa-download" aria-hidden="true"></i>
                                    <span>@lang('order.cart.invoice_download')</span>
                                </a>

                                <a href="{{ route('cart.printable') }}"
                                   class="d-btn d-btn--quiet"
                                   target="_blank"
                                   rel="noopener">
                                    <i class="fas fa-print" aria-hidden="true"></i>
                                    <span>@lang('order.cart.invoice_print')</span>
                                </a>
                            </div>

                            <p class="d-cart-invoice__hint">
                                <i class="fas fa-exclamation-circle" aria-hidden="true"></i>
                                <span>@lang('order.cart.invoice_hint')</span>
                            </p>
                        </div>
                    </div>
                </aside>
            </div>
        </div>
    </section>

</div>

@endsection