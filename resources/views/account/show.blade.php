{{--
    The dashboard: what a delegate sees immediately after signing in.

    Built in the same vocabulary as the profile page (`d-page`, `d-stats`,
    `d-panel`, `d-panel__row`, `d-tag`, `d-btn`) rather than the older
    `app-shell` / `app-card` classes this page used to carry, so the two
    account pages read as one design and a change to the design system lands on
    both at once.

    Three bands, in the order they are needed rather than the order they are
    stored:

        1. Who you are. Identity and the two states that gate everything else.
        2. What is still missing. A three-step checklist, because
           `User::canRegister()` is an `&&` of three conditions and a delegate
           blocked at the pricing page is otherwise told only *that* they are
           blocked, never *which* condition failed.
        3. What you have. The registrations.

    The checklist earns its space: `canRegister()` requires a confirmed
    authenticator *and* an accepted terms record, and before the terms route
    existed nothing in the application ever set `terms_accepted_at`. A delegate
    who confirmed their authenticator was therefore redirected from pricing to
    an account page with no way to finish — the gate was a dead end. Step three
    closes it from the one page the redirect already lands on.
--}}
@extends('layouts.app')

@section('title', __('account.title'))
@section('description', __('account.lede'))

@section('content')
<div class="d-page d-page--account">

    @php
    $accountLocale = in_array($user->locale, ['fr', 'en', 'ar'], true)
    ? $user->locale
    : app()->getLocale();
    @endphp




    <section class="d-section d-section--alt" aria-labelledby="checklist-heading">
        <div class="container">
            <div class="row g-4 g-xl-5 align-items-start">

                {{-- -----------------------------------------------------------------
                    Band 2 — the checklist. Left, because it is the thing to do.
                ----------------------------------------------------------------- --}}
                <div class="col-lg-7 ux-reveal ux-reveal-left">
                    <div class="d-panel">
                        <h2 id="checklist-heading" class="d-panel__title d-panel__title--lede">
                            @lang('account.checklist_title')
                        </h2>

                        <p class="d-form-panel__lede">@lang('account.checklist_lede')</p>

                        @if ($canOrder)
                        {{-- Reached state gets a positive statement rather
                                 than a greyed-out list: three struck-through rows
                                 read as a to-do list somebody abandoned. --}}
                        <p class="d-notice d-notice--success mb-0">
                            <i class="fas fa-check-circle" aria-hidden="true"></i>
                            <span>@lang('account.checklist_done')</span>
                        </p>
                        @else
                        <ol class="d-steps">
                            {{-- Step 1: the address. There is no link here —
                                     the only way to confirm is the email already
                                     in their inbox, and a link to "resend" would
                                     be a feature, not a fix. The row says so. --}}
                            <li class="d-steps__row{{ $user->hasVerifiedEmail() ? ' is-done' : ' is-todo' }}">
                                <span class="d-steps__marker" aria-hidden="true">
                                    <i class="fas {{ $user->hasVerifiedEmail() ? 'fa-check' : 'fa-envelope' }}"></i>
                                </span>

                                <div class="d-steps__body">
                                    <p class="d-steps__title">@lang('account.step_email_title')</p>
                                    <p class="d-steps__text">@lang('account.step_email_text')</p>
                                </div>

                                <span class="d-tag d-tag--{{ $user->hasVerifiedEmail() ? 'success' : 'todo' }}">
                                    @if ($user->hasVerifiedEmail())
                                    @lang('account.step_done')
                                    @else
                                    @lang('account.step_pending')
                                    @endif
                                </span>
                            </li>

                            {{-- Step 2: the authenticator. The one row with a
                                     button: `totp.setup` is a real screen with a
                                     QR code, so the delegate can finish it here
                                     without losing this page. --}}
                            <li class="d-steps__row{{ $user->hasConfirmedTotp() ? ' is-done' : ' is-todo' }}">
                                <span class="d-steps__marker" aria-hidden="true">
                                    <i class="fas {{ $user->hasConfirmedTotp() ? 'fa-check' : 'fa-shield-alt' }}"></i>
                                </span>

                                <div class="d-steps__body">
                                    <p class="d-steps__title">@lang('account.step_totp_title')</p>
                                    <p class="d-steps__text">@lang('account.step_totp_text')</p>
                                </div>

                                @if ($user->hasConfirmedTotp())
                                <span class="d-tag d-tag--success">@lang('account.step_done')</span>
                                @else
                                <a href="{{ route('totp.setup') }}" class="d-btn d-btn--primary d-btn--sm">
                                    <span>@lang('account.step_action')</span>
                                </a>
                                @endif
                            </li>

                            {{-- Step 3: the terms. A checkbox and a submit, not
                                     a link: acceptance is an act with a timestamp,
                                     and a link that silently recorded consent on
                                     the way to the document would record it for
                                     everyone who went to look. --}}
                            <li class="d-steps__row{{ $termsAccepted ? ' is-done' : ' is-todo' }}">
                                <span class="d-steps__marker" aria-hidden="true">
                                    <i class="fas {{ $termsAccepted ? 'fa-check' : 'fa-file-signature' }}"></i>
                                </span>

                                <div class="d-steps__body">
                                    <p class="d-steps__title">@lang('account.step_terms_title')</p>
                                    <p class="d-steps__text">@lang('account.step_terms_text')</p>

                                    @if ($termsAccepted)
                                    <p class="d-steps__stamp">
                                        <i class="fas fa-clock" aria-hidden="true"></i>
                                        <span>@lang('account.terms_accepted_on', [
                                            'date' => $user->terms_accepted_at->translatedFormat('d M Y'),
                                            ])</span>
                                    </p>
                                    @else
                                    <form method="POST" action="{{ route('account.terms') }}"
                                        class="d-steps__form">
                                        @csrf
                                        <input type="hidden" name="accept" value="1">

                                        <label class="d-check">
                                            <input type="checkbox" name="confirm" value="1" required
                                                class="form-check-input">
                                            <span>@lang('account.terms_label')</span>
                                        </label>

                                        <button type="submit" class="d-btn d-btn--primary d-btn--sm">
                                            <span>@lang('account.step_action')</span>
                                        </button>
                                    </form>
                                    @endif
                                </div>

                                @if ($termsAccepted)
                                <span class="d-tag d-tag--success">@lang('account.step_done')</span>
                                @endif
                            </li>
                        </ol>
                        @endif
                    </div>
                </div>

                {{-- -----------------------------------------------------------------
                    Band 2b — shortcuts and the profile itself. Right, and narrow:
                    this is the part a delegate reads once and acts on.
                ----------------------------------------------------------------- --}}
                <div class="col-lg-5 ux-reveal ux-reveal-right">
                    <div class="d-stack">

                        <div class="d-panel d-panel--quiet">
                            <h3 class="d-panel__title">@lang('account.actions_title')</h3>

                            <ul class="d-panel__list">
                                <li class="d-panel__row">
                                    <span class="d-panel__key">@lang('account.profile')</span>
                                    <a href="{{ route('account.edit') }}" class="d-panel__link">
                                        @lang('action.edit')
                                    </a>
                                </li>

                                <li class="d-panel__row">
                                    <span class="d-panel__key">@lang('account.orders')</span>
                                    <a href="{{ route('orders.index') }}" class="d-panel__link">
                                        @lang('action.view')
                                    </a>
                                </li>

                                <li class="d-panel__row">
                                    <span class="d-panel__key">@lang('nav.pricing')</span>
                                    <a href="{{ route('pricing') }}" class="d-panel__link">
                                        @lang('pricing.register')
                                    </a>
                                </li>

                                <li class="d-panel__row">
                                    <span class="d-panel__key">@lang('account.totp_label')</span>

                                    @if ($user->hasConfirmedTotp())
                                    <span class="d-tag d-tag--success">@lang('account.totp_enrolled')</span>
                                    @else
                                    <a href="{{ route('totp.setup') }}" class="d-panel__link">
                                        @lang('account.totp_not_enrolled')
                                    </a>
                                    @endif
                                </li>
                            </ul>
                        </div>

                        {{-- Why the two pages are separate, restated where it is
                             actually useful: a delegate who came here to fix a
                             phone number is on the wrong page for that. --}}
                        <div class="d-panel">
                            <h3 class="d-panel__title">@lang('account.profile')</h3>

                            <p class="d-form-panel__lede">@lang('account.profile_lede')</p>

                            <dl class="d-defs">
                                <div class="d-defs__row">
                                    <dt>@lang('register.organisation')</dt>
                                    <dd>{{ $user->organisation ?: '—' }}</dd>
                                </div>

                                <div class="d-defs__row">
                                    <dt>@lang('register.job_title')</dt>
                                    <dd>{{ $user->job_title ?: '—' }}</dd>
                                </div>

                                <div class="d-defs__row">
                                    <dt>@lang('register.city')</dt>
                                    <dd>{{ $user->city ?: '—' }}</dd>
                                </div>

                                <div class="d-defs__row">
                                    <dt>@lang('register.country')</dt>
                                    <dd>{{ $user->country?->name() ?: '—' }}</dd>
                                </div>

                                <div class="d-defs__row">
                                    <dt>@lang('register.language')</dt>
                                    {{-- The language's own name, from the same map the
                                         profile form uses, rather than the reader's
                                         rendering of it: someone who cannot read the
                                         current one is here to change it. --}}
                                    <dd>
                                        <span lang="{{ $accountLocale }}">
                                            {{ ['fr' => 'Français', 'en' => 'English', 'ar' => 'العربية'][$accountLocale] }}
                                        </span>
                                    </dd>
                                </div>
                            </dl>

                            <a href="{{ route('account.edit') }}" class="d-btn d-btn--ghost d-btn--block mt-4">
                                <span>@lang('account.edit_title')</span>
                            </a>
                        </div>

                                                    <a href="{{ route('account.edit') }}" class="d-btn d-btn--ghost d-btn--block mt-4">
                                <span>@lang('account.edit_title')</span>
                            </a>
                        </div>

                        {{-- ========================================== --}}
                        {{-- ADD THE PROMO CODE PANEL HERE --}}
                        {{-- ========================================== --}}
                        @if ($user->promo_code)
                        <div class="d-panel">
                            <h3 class="d-panel__title">@lang('account.promo_title')</h3>
                            
                            <p class="d-form-panel__lede">@lang('account.promo_lede')</p>
                            
                            <div class="d-flex align-items-center gap-2 mt-3">
                                <code class="flex-grow-1 px-3 py-2 rounded border font-monospace text-center" 
                                      style="font-size: 1.1rem; letter-spacing: 0.12em; background-color: var(--bs-tertiary-bg);">
                                    {{ $user->promo_code }}
                                </code>

                                <button type="button" 
                                        class="d-btn d-btn--quiet d-btn--sm" 
                                        data-promo-code="{{ $user->promo_code }}"
                                        onclick="
                                            navigator.clipboard.writeText(this.dataset.promoCode);
                                            const label = this.querySelector('span');
                                            const original = label.textContent;
                                            label.textContent = @js(__('account.promo_copied'));
                                            setTimeout(() => { label.textContent = original; }, 1500);
                                        ">
                                    <i class="fas fa-copy" aria-hidden="true"></i>
                                    <span>@lang('account.promo_copy')</span>
                                </button>
                            </div>
                        </div>
                        @endif
                        {{-- ========================================== --}}

                        {{-- Signing out is its own form at the foot of the page
                             rather than a fourth shortcut row: it is destructive
                             to the session, and a link among three harmless ones
                             reads as equally harmless. --}}
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="d-btn d-btn--danger-ghost d-btn--block">
                                <i class="fas fa-sign-out-alt" aria-hidden="true"></i>
                                <span>@lang('action.logout')</span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ---------------------------------------------------------------------
        Band 3 — the registrations. Last and full width: this is the reason the
        page exists, but it is also the band that grows, and a list that
        reflows above the fixed bands would push the checklist off the screen
        every time an order was added.
    --------------------------------------------------------------------- --}}
    <section class="d-section" aria-labelledby="orders-heading">
        <div class="container">
            <div class="d-head d-head--row">
                <div>
                    <h2 id="orders-heading" class="d-head__title">@lang('account.orders')</h2>
                    <p class="d-head__lede">@lang('account.orders_lede')</p>
                </div>

                <a href="{{ route('pricing') }}" class="d-btn d-btn--primary">
                    <span>@lang('pricing.register')</span>
                    <i class="fas fa-arrow-right d-btn__arrow" aria-hidden="true"></i>
                </a>
            </div>

            @if ($orders->isEmpty())
            {{-- `order.no_orders` rather than the generic `state.empty`:
                     "no information is available yet" is a fallback for a whole
                     page, not for a section that is legitimately empty on an
                     account that registered an hour ago. --}}
            <div class="d-empty">
                <span class="d-empty__icon" aria-hidden="true">
                    <i class="fas fa-ticket-alt"></i>
                </span>
                <p class="d-empty__text">@lang('order.no_orders')</p>
                <a href="{{ route('pricing') }}" class="d-btn d-btn--primary">
                    <span>@lang('pricing.register')</span>
                    <i class="fas fa-arrow-right d-btn__arrow" aria-hidden="true"></i>
                </a>
            </div>
            @else
            <ul class="d-orders">
                @foreach ($orders as $order)
                @php
                /* An `order_items` row has no single `quantity`
                column — a seat is either a member seat or a
                standard one, and both can be on the same line —
                so the count is the model's own method rather than
                a `sum()` over a column that does not exist.
                Eager loaded in the controller, so this is not a
                query per row. */
                $places = $order->items->sum(
                fn ($item): int => $item->member_quantity + $item->standard_quantity
                );
                @endphp

                {{-- `total` is the snapshot column written once at
                             checkout, and `reference` is the code quoted in the
                             email and printed on the invoice — neither is read
                             from a relation, so the row cannot drift from what
                             was actually charged. --}}
                <li class="d-order ux-reveal">
                    <div class="d-order__id">
                        <p dir="ltr" class="d-order__ref">
                            {{ $order->reference ?? ('#'.$order->getKey()) }}
                        </p>
                        <p class="d-order__meta">
                            {{ $order->created_at->translatedFormat('d M Y') }}
                            <span class="d-order__dot" aria-hidden="true">·</span>
                            <span>
                                {{ $places > 0
                                            ? trans_choice('account.order_places', $places, ['count' => $places])
                                            : __('account.order_no_places') }}
                            </span>
                        </p>
                    </div>

                    <div class="d-order__side">
                        <span class="d-tag d-tag--{{ $order->status->colour() }}">
                            {{ $order->status->label($currentLocale->value) }}
                        </span>

                        {{-- dir="ltr": an amount beside a currency code
                                     must not be bidi-reordered. --}}
                        <p dir="ltr" class="d-order__amount">
                            {{ \App\Support\Money::format($order->total, $order->currency, $currentLocale->value) }}
                        </p>

                        <a href="{{ route('orders.show', ['order' => $order->id]) }}"
                            class="d-btn d-btn--quiet d-btn--sm">
                            <span>@lang('action.view')</span>
                        </a>
                    </div>
                </li>
                @endforeach
            </ul>

            <p class="d-orders__foot">
                <a href="{{ route('orders.index') }}" class="d-btn d-btn--ghost">
                    <span>@lang('account.orders')</span>
                    <i class="fas fa-arrow-right d-btn__arrow" aria-hidden="true"></i>
                </a>
            </p>
            @endif
        </div>
    </section>

</div>
@endsection