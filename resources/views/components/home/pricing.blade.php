{{--
    What it costs.

    Four cards rather than two, because the two that matter are a cross: a
    ticket type fixes the currency, and every type has a member rate and a
    standard rate. Rendering one card per type and hiding the rate the visitor
    is not eligible for was the previous behaviour, and it made the band
    answer a question nobody asked — "what if I am not a member?" — while
    hiding the one they did.

    Nothing here decides who is a member. The cards are a price list; the rate
    actually charged is resolved server-side at checkout from the Membership
    record, exactly as before.

    The figure and the currency are two elements rather than one formatted
    string so the currency can sit smaller and lighter. They are produced by
    `amountParts()` and not by splitting `formatAmount()`'s output, because
    the thousands separator is a narrow no-break space and cutting on a plain
    space silently breaks the number in French.
--}}
@props(['ticketTypes', 'edition', 'isMember' => false])

@php
    /* ---- Online images (Unsplash CDN). Every one has a CSS fallback, so a dead link never breaks the layout. ---- */
    $imgBanner  = 'https://images.unsplash.com/photo-1551882547-ff40c63fe5fa?auto=format&fit=crop&w=1800&q=80'; // hotel, palms & pool at dusk
    $imgAccess  = 'https://images.unsplash.com/photo-1613327986042-63d4425a1a5d?auto=format&fit=crop&w=1800&q=60'; // soft purple/white abstract
    $imgTariffs = 'https://images.unsplash.com/photo-1583339522870-0d9f28cef33f?auto=format&fit=crop&w=1800&q=60'; // lavender draped textile
    $imgPaths   = 'https://images.unsplash.com/photo-1629196914168-3a2652305f9f?auto=format&fit=crop&w=1800&q=60'; // lavender watercolour

    $hasReset    = \Illuminate\Support\Facades\Route::has('password.request');
    $accessIcons = ['fa-users', 'fa-microphone', 'fa-file', 'fa-users'];
    $trustIcons  = ['fa-shield-halved', 'fa-file-lines', 'fa-headset'];
    $madPerUsd   = (float) config('brand.mad_per_usd', 10); // 7 500 MAD -> 750 USD, 8 500 MAD -> 850 USD
@endphp
@php
    $localCurrency = (string) config('conference.default_currency');

    // One card per (type, rate) pair. The audience line is the only thing that
    // differs between the two cards of a type.
    $tariffs = [];

    foreach ($ticketTypes as $type) {
        foreach ([true, false] as $member) {
            $parts = $type->amountParts($type->priceFor($member));

            $tariffs[] = [
                'amount' => $parts['amount'],
                'currency' => $parts['currency'],
                'kind' => $type->currency === $localCurrency
                    ? __('home.landing.inscription.ttc')
                    : __('home.landing.inscription.fx'),
                'audience' => $member
                    ? __('home.landing.inscription.members')
                    : __('home.landing.inscription.non_members'),
                'code' => $type->code,
            ];
        }
    }

    // One call to action per currency on sale, so the two buttons under the
    // grid take the visitor to the rate they actually intend to pay in.
    $currencies = $ticketTypes->pluck('currency')->filter()->unique()->values();
@endphp

@if ($tariffs !== [])
     <section class="reg-section reg-tariffs reg-bgimg" style="--reg-bg:url('{{ $imgTariffs }}')" aria-labelledby="pricing-heading">
        <div class="container">
            <header class="reg-head">
                <p class="reg-eyebrow">{{ __('pricing.tariffs.eyebrow') }}</p>
                <h2 class="reg-title" id="pricing-heading">
                    {{ __('pricing.tariffs.title_a') }}
                    <span class="reg-title__accent">{{ __('pricing.tariffs.title_b') }}</span>
                </h2>
            </header>

            @unless ($registrationOpen)
                <p class="reg-notice" role="status"><i class="fas fa-lock" aria-hidden="true"></i> {{ __('pricing.closed') }}</p>
            @endunless

            @auth
                @unless ($canOrder)
                    <p class="reg-notice" role="status">
                        <i class="fas fa-circle-exclamation" aria-hidden="true"></i>
                        @if (auth()->user()->hasConfirmedTotp())
                            {{ __('register.terms_required') }}
                        @else
                            <a href="{{ route('totp.setup') }}">{{ __('totp.title') }}</a>
                        @endif
                    </p>
                @endunless
            @endauth

            @if ($errors->hasAny(['quantity', 'member_quantity', 'ticket_type_id']))
                <p class="reg-notice reg-notice--danger" role="alert">
                    <i class="fas fa-circle-exclamation" aria-hidden="true"></i>
                    {{ $errors->first('member_quantity') ?: ($errors->first('quantity') ?: $errors->first('ticket_type_id')) }}
                </p>
            @endif

            @if ($ticketTypes->isEmpty())
                <x-empty-state :message="__('state.empty')" icon="fas fa-tags" />
            @else
                @php
                    // Two currencies are on sale. MAD is the local default; USD is
                    // the alternative. Both are real tariffs, so the toggle only
                    // swaps which one a card shows and submits — the figure shown
                    // is always the figure that travels with the order.
                    $typesByCurrency = $ticketTypes->keyBy(fn ($t) => strtoupper((string) $t->currency));
                    $displayTypes = $typesByCurrency->sortBy(fn ($t, $code) => $code === 'MAD' ? 0 : 1);
                    $defaultCurrency = $displayTypes->keys()->first();
                @endphp

                @if ($displayTypes->count() > 1)
                    <div class="reg-currency" role="radiogroup" aria-label="{{ __('pricing.tariffs.currency_label') }}">
                        @foreach ($displayTypes as $code => $type)
                            <label class="reg-currency__opt">
                                <input type="radio" name="currency_pref" value="{{ $code }}"
                                    data-currency-radio @checked($code === $defaultCurrency)>
                                <span>{{ $code }}</span>
                            </label>
                        @endforeach
                    </div>
                @endif

                <div class="reg-prices">
                    {{-- First card = member rate, second = non-member rate. Each card
                         carries one body per currency; the server still decides which
                         rate is really charged. --}}
                    @foreach ([true, false] as $memberCard)
                        @php
                            // One entry per currency on sale: the figure, its code,
                            // and the "or X" line quoting the other currency from
                            // its own tariff rather than a converted rate.
                            $panels = [];
                            foreach ($displayTypes as $code => $t) {
                                $parts = $t->amountParts($t->priceFor($memberCard));

                                $alt = null;
                                foreach ($displayTypes as $otherCode => $other) {
                                    if ($otherCode !== $code) {
                                        $otherParts = $other->amountParts($other->priceFor($memberCard));
                                        $alt = $otherParts['amount'].' '.$otherParts['currency'];
                                        break;
                                    }
                                }

                                $panels[$code] = [
                                    'type' => $t,
                                    'figure' => $parts['amount'],
                                    'currency' => $parts['currency'],
                                    'alt' => $alt,
                                ];
                            }
                        @endphp

                            <article @class(['reg-price', 'reg-price--member' => $memberCard])>
                                <header class="reg-price__head">
                                    <i class="fas {{ $memberCard ? 'fa-user-group' : 'fa-users' }}" aria-hidden="true"></i>
                                    <h3>{{ $memberCard ? __('pricing.tariffs.member') : __('pricing.tariffs.standard') }}</h3>
                                </header>

                                @if ($memberCard)
                                    <p class="reg-price__badge"><i class="fas fa-star" aria-hidden="true"></i> {{ __('pricing.tariffs.member_badge') }}</p>
                                @endif

                                @foreach ($panels as $code => $panel)
                                <div class="reg-price__body" data-currency-panel="{{ $code }}"
                                    @unless ($code === $defaultCurrency) hidden @endunless>
                                    <p class="reg-price__amount" dir="ltr">
                                        <span class="reg-price__figure">{{ $panel['figure'] }}</span>
                                        <span class="reg-price__currency">{{ $panel['currency'] }}</span>
                                    </p>

                                    @if ($panel['alt'])
                                        <p class="reg-price__alt" dir="ltr">{{ __('pricing.tariffs.or') }} {{ $panel['alt'] }}</p>
                                    @endif

                                    @if (! $registrationOpen)
                                        <button type="button" class="reg-btn reg-btn--off" disabled><span>{{ __('pricing.closed') }}</span></button>

                                    @elseif (! auth()->check())
                                        <a href="/tarifs" class="reg-btn {{ $memberCard ? 'reg-btn--solid' : 'reg-btn--outline' }}">
                                            <span>{{ __('pricing.tariffs.cta') }}</span><i class="fas fa-chevron-right" aria-hidden="true"></i>
                                        </a>

                                    @elseif (! $canOrder)
                                        <a href="{{ auth()->user()->hasConfirmedTotp() ? route('account') : route('totp.setup') }}"
                                           class="reg-btn {{ $memberCard ? 'reg-btn--solid' : 'reg-btn--outline' }}">
                                            <span>{{ __(auth()->user()->hasConfirmedTotp() ? 'register.terms_required' : 'totp.title') }}</span>
                                        </a>

                                    @elseif ($memberCard && ! $isMember)
                                        <a href="{{ route('contact') }}" class="reg-btn reg-btn--outline">
                                            <span>{{ __('pricing.tariffs.member_only') }}</span><i class="fas fa-chevron-right" aria-hidden="true"></i>
                                        </a>

                                    @else
                                        <form method="POST" action="{{ route('cart.store') }}">
                                            @csrf
                                            <input type="hidden" name="ticket_type_id" value="{{ $panel['type']->id }}">
                                            <input type="hidden" name="quantity" value="1">
                                            <input type="hidden" name="member_quantity" value="{{ $memberCard ? 1 : 0 }}">
                                            <button type="submit" class="reg-btn {{ $memberCard ? 'reg-btn--solid' : 'reg-btn--outline' }}">
                                                <span>{{ __('pricing.tariffs.cta') }}</span><i class="fas fa-chevron-right" aria-hidden="true"></i>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                                @endforeach
                            </article>
                        @endforeach
                    </div>

                    @if ($displayTypes->count() > 1)
                    <p class="reg-currency__hint">{{ __('pricing.tariffs.currency_hint') }}</p>
                    @endif
            @endif

            <p class="reg-note">{{ __('pricing.tariffs.note') }}</p>

            <aside class="reg-member" role="note">
                <span class="reg-member__icon" aria-hidden="true"><i class="fas fa-users"></i></span>

                <div class="reg-member__body">
                    <h2 class="reg-member__title">{{ __('pricing.membership.title') }}</h2>
                    <p class="reg-member__text">{{ __('pricing.membership.text') }}<br>{{ __('pricing.membership.text_2') }}</p>
                </div>

                <a href="https://www.iiamaroc.org/adherez-a-iia-maroc" target="_blank" class="reg-member__cta">
                    <span>{{ __('pricing.membership.cta') }}</span>
                    <i class="fas fa-chevron-right" aria-hidden="true"></i>
                </a>
            </aside>
        </div>
    </section> 
@endif

<script>
    // Currency toggle. Both currencies' amounts and forms are already in the
    // DOM, so switching only shows the panel whose `data-currency-panel`
    // matches the checked radio. Nothing is recomputed in the browser: the
    // figure shown is the figure the server rendered for the ticket the form
    // will submit.
    document.querySelectorAll('[data-currency-radio]').forEach(function(radio) {
        radio.addEventListener('change', function() {
            if (!radio.checked) {
                return;
            }
            document.querySelectorAll('[data-currency-panel]').forEach(function(panel) {
                panel.hidden = panel.getAttribute('data-currency-panel') !== radio.value;
            });
        });
    });
</script>


<style>
    /* ==========================================================================
   Registration / pricing page — prefix `reg-`. Self-contained.
   ========================================================================== */

    .reg {
        --g-navy: #14106a;
        --g-ink: #3f3d8f;
        --g-purple: #6c3fe6;
        --g-indigo: #2f27b8;
        --g-pink: #c13bd8;
        --g-gold: #b9822a;
        --g-line: #d3d9f4;

        --g-lat-white: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='88' height='88' viewBox='0 0 88 88'%3E%3Cg fill='none' stroke='%23ffffff' stroke-width='1.3'%3E%3Crect x='22' y='22' width='44' height='44'/%3E%3Crect x='22' y='22' width='44' height='44' transform='rotate(45 44 44)'/%3E%3Ccircle cx='44' cy='44' r='10'/%3E%3Cpath d='M0 0L22 22M88 0L66 22M0 88L22 66M88 88L66 66'/%3E%3C/g%3E%3C/svg%3E");
        --g-lat-gold: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='88' height='88' viewBox='0 0 88 88'%3E%3Cg fill='none' stroke='%23d9a441' stroke-width='1.3'%3E%3Crect x='22' y='22' width='44' height='44'/%3E%3Crect x='22' y='22' width='44' height='44' transform='rotate(45 44 44)'/%3E%3Ccircle cx='44' cy='44' r='10'/%3E%3Cpath d='M0 0L22 22M88 0L66 22M0 88L22 66M88 88L66 66'/%3E%3C/g%3E%3C/svg%3E");
        --g-wave: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 1440 220' preserveAspectRatio='none'%3E%3Cpath d='M0 150C240 70 470 210 760 135S1210 60 1440 120V220H0Z' fill='%23ffffff' fill-opacity='.38'/%3E%3Cpath d='M0 150C240 70 470 210 760 135S1210 60 1440 120' fill='none' stroke='%23ffffff' stroke-opacity='.9' stroke-width='2'/%3E%3Cpath d='M0 175C260 110 520 220 800 160S1230 100 1440 150' fill='none' stroke='%23ffffff' stroke-opacity='.55' stroke-width='1.5'/%3E%3C/svg%3E");

        --g-from-start: to right;
        --g-from-end: to left;
    }

    [dir="rtl"] .reg {
        --g-from-start: to left;
        --g-from-end: to right;
    }

    /* Photo backgrounds, washed with lavender so they read as texture (colour shows if image fails). */
    .reg-bgimg {
 
        background-position: center;
        background-size: cover;
        isolation: isolate;
        overflow: hidden;
        position: relative;
    }

    .reg-section {
        padding-block: clamp(2.25rem, 5vw, 3.5rem);
    }

    /* ---------------------------------------------------------------- banner */
    .reg-banner {
        background:
            radial-gradient(80% 90% at 0% 0%, rgb(255 255 255 / 85%) 0%, transparent 60%),
            linear-gradient(120deg, #f4f0ff 0%, #e6e0fd 50%, #d6cdf8 100%);
        isolation: isolate;
        min-height: 21rem;
        overflow: hidden;
        padding-block: clamp(2.75rem, 5.5vw, 4rem);
        position: relative;
    }

    .reg-banner__photo {
        -webkit-mask-image: linear-gradient(var(--g-from-end), #000 55%, transparent 100%);
        mask-image: linear-gradient(var(--g-from-end), #000 55%, transparent 100%);
        background-color: #b9a7f2;
        background-position: center 45%;
        background-repeat: no-repeat;
        background-size: cover;
        inset-block: 0;
        inset-inline-end: 0;
        position: absolute;
        width: min(66%, 980px);
        z-index: -1;
    }

    .reg-banner::before {
        /* lattice on the start edge */
        -webkit-mask-image: linear-gradient(var(--g-from-start), #000 0%, transparent 100%);
        mask-image: linear-gradient(var(--g-from-start), #000 0%, transparent 100%);
        background-image: var(--g-lat-white);
        background-size: 88px 88px;
        content: '';
        inset-block: 0;
        inset-inline-start: 0;
        position: absolute;
        width: min(16%, 240px);
        z-index: -1;
    }

    .reg-banner::after {
        /* white wave ribbons + glow at the foot */
        background:
            var(--g-wave) bottom / 100% 100% no-repeat,
            radial-gradient(70% 100% at 10% 120%, rgb(140 120 255 / 45%) 0%, transparent 70%);
        content: '';
        height: 42%;
        inset: auto 0 0 0;
        pointer-events: none;
        position: absolute;
        z-index: -1;
    }

    .reg-banner__inner>* {
        max-width: 34rem;
    }

    .reg-banner__inner>.reg-banner__facts {
        max-width: 44rem;
    }

    .reg-banner__eyebrow {
        align-items: center;
        color: var(--g-indigo);
        display: flex;
        font-size: .72rem;
        font-weight: 800;
        gap: .55rem;
        letter-spacing: .16em;
        margin: 0 0 var(--sp-3);
        text-transform: uppercase;
    }

    .reg-banner__eyebrow::before {
        background: var(--g-indigo);
        border-radius: 2px;
        content: '';
        flex: 0 0 auto;
        height: 4px;
        width: 3.2rem;
    }

    body.rtl .reg-banner__eyebrow {
        letter-spacing: .02em;
    }

    .reg-banner__title {
        color: var(--g-navy);
        font-size: clamp(2rem, 1.4rem + 2.4vw, 3rem);
        font-weight: 800;
        line-height: 1.08;
        margin: 0 0 var(--sp-3);
    }

    body.rtl .reg-banner__title {
        line-height: 1.35;
    }

    .reg-banner__accent {
        background: linear-gradient(90deg, #5a35e0 0%, #8a5cf6 100%);
        -webkit-background-clip: text;
        background-clip: text;
        color: transparent;
        display: block;
        -webkit-text-fill-color: transparent;
    }

    .reg-banner__lede {
        color: #1d1a5e;
        font-size: var(--t-base);
        line-height: 1.6;
        margin: 0 0 var(--sp-5);
        max-width: 30rem;
    }

    .reg-banner__facts {
        align-items: center;
        display: flex;
        flex-wrap: wrap;
        gap: var(--sp-3) 0;
        list-style: none;
        margin: 0;
        padding: 0;
    }

    .reg-banner__facts li {
        align-items: center;
        color: #2c22a8;
        display: flex;
        gap: var(--sp-3);
    }

    .reg-banner__facts li+li {
        border-inline-start: 1px solid rgb(42 31 110 / 28%);
        margin-inline-start: var(--sp-5);
        padding-inline-start: var(--sp-5);
    }

    .reg-banner__facts strong {
        font-size: var(--t-sm);
        line-height: 1.3;
    }

    .reg-banner__icon {
        color: #4b2fd0;
        font-size: 1.8rem;
        line-height: 1;
    }

    @media (max-width: 767.98px) {
        .reg-banner__photo {
            opacity: .3;
            width: 100%;
        }

        .reg-banner::before {
            display: none;
        }

        .reg-banner__facts li+li {
            border: 0;
            margin: 0;
            padding: 0;
        }
    }

    /* --------------------------------------------------------------- headings */
    .reg-head {
        margin-block-end: var(--sp-6);
        text-align: center;
    }

    .reg-eyebrow {
        color: var(--g-purple);
        font-size: .72rem;
        font-weight: 700;
        letter-spacing: .2em;
        margin-bottom: 25px;
        text-transform: uppercase;
    }

    .reg-eyebrow--pink {
        color: var(--g-pink);
    }

    .reg-eyebrow::after {
        background: linear-gradient(90deg, var(--g-indigo), var(--g-pink));
        border-radius: 2px;
        content: '';
        display: block;
        height: 2px;
        margin: .4rem auto 0;
        width: 2.6rem;
    }

    body.rtl .reg-eyebrow {
        letter-spacing: .04em;
    }

    .reg-title {
        color: var(--g-navy);
        font-size: clamp(1.6rem, 1.25rem + 1.5vw, 2.1rem);
        font-weight: 800;
        margin: 0;
    }

    .reg-title__accent {
        color: var(--g-purple);
    }

    /* ----------------------------------------------------------------- access */
    .reg-access {
        background-image: linear-gradient(180deg, rgb(255 255 255 / 94%) 0%, rgb(247 245 255 / 96%) 100%), var(--reg-bg, none);
    }

    .reg-access__grid {
        display: grid;
        gap: var(--sp-6) 0;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        list-style: none;
        margin: 0;
        padding: 0;
    }

    @media (max-width: 991.98px) {
        .reg-access__grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (max-width: 575.98px) {
        .reg-access__grid {
            grid-template-columns: 1fr;
        }
    }

    .reg-access__item {
        padding-inline: var(--sp-5);
        position: relative;
        text-align: center;
    }

    .reg-access__item+.reg-access__item::before {
        background: var(--g-line);
        content: '';
        inset-block: 1.4rem 1rem;
        inset-inline-start: 0;
        position: absolute;
        width: 1px;
    }

    @media (max-width: 991.98px) {
        .reg-access__item:nth-child(3)::before {
            display: none;
        }
    }

    @media (max-width: 575.98px) {
        .reg-access__item::before {
            display: none;
        }
    }

    .reg-access__icon {
        align-items: center;
        background: #ece8fe;
        border-radius: 50%;
        color: #5b3fd9;
        display: inline-flex;
        font-size: 1.6rem;
        height: 4rem;
        justify-content: center;
        margin-block-end: var(--sp-3);
        width: 4rem;
    }

    .reg-access__title {
        color: var(--g-navy);
        font-size: 1.02rem;
        font-weight: 800;
        margin: 0 0 var(--sp-2);
    }

    .reg-access__text {
        color: var(--g-ink);
        font-size: var(--t-sm);
        line-height: 1.55;
        margin: 0 auto;
        max-width: 16rem;
    }

    /* ---------------------------------------------------------------- tariffs */
    .reg-tariffs::before {
        -webkit-mask-image: linear-gradient(var(--g-from-end), #000, transparent);
        mask-image: linear-gradient(var(--g-from-end), #000, transparent);
        background-image: var(--g-lat-white);
        background-size: 88px 88px;
        content: '';
        inset-block: 0;
        inset-inline-end: 0;
        opacity: .75;
        position: absolute;
        width: min(26%, 380px);
        z-index: -1;
    }

    .reg-notice {
        align-items: center;
        background: #fff8ea;
        border: 1px solid #f3dfb8;
        border-radius: 10px;
        color: #7a4b12;
        display: flex;
        font-size: var(--t-sm);
        gap: var(--sp-3);
        margin: 0 auto var(--sp-4);
        max-width: 52rem;
        padding: var(--sp-3) var(--sp-4);
    }

    .reg-notice a {
        color: inherit;
        font-weight: 700;
    }

    .reg-notice--danger {
        background: #fdf3f2;
        border-color: #e6b4b0;
        color: #b3261e;
    }

    .reg-type {
        color: var(--g-navy);
        font-size: var(--t-lg);
        margin: var(--sp-5) auto var(--sp-3);
        max-width: 52rem;
    }

    .reg-prices {
        align-items: start;
        display: grid;
        gap: var(--sp-4);
        grid-template-columns: repeat(2, minmax(0, 1fr));
        margin-inline: auto;
        max-width: 52rem;
    }

    @media (max-width: 767.98px) {
    /* ------------------------------------------------------------ currency */
    .reg-currency {
        background: #eef0fe;
        border: 1px solid #d3d9f4;
        border-radius: 999px;
        display: flex;
        gap: .3rem;
        margin: 0 auto var(--sp-4);
        padding: .28rem;
        width: fit-content;
    }

    .reg-currency__opt {
        cursor: pointer;
        display: inline-flex;
        position: relative;
    }

    .reg-currency__opt input {
        height: 1px;
        opacity: 0;
        pointer-events: none;
        position: absolute;
        width: 1px;
    }

    .reg-currency__opt span {
        align-items: center;
        border-radius: 999px;
        color: var(--g-indigo);
        display: inline-flex;
        font-size: .82rem;
        font-weight: 800;
        justify-content: center;
        letter-spacing: .05em;
        min-width: 4.6rem;
        padding: .5rem 1.15rem;
        transition: background-color var(--dur-2) var(--ease), color var(--dur-2) var(--ease);
    }

    .reg-currency__opt input:checked+span {
        background: linear-gradient(135deg, #2f27b8 0%, #231a9a 100%);
        color: #fff;
    }

    .reg-currency__opt input:focus-visible+span {
        outline: 3px solid rgb(115 132 255 / 55%);
        outline-offset: 2px;
    }

    .reg-currency__hint {
        color: var(--g-ink);
        font-size: var(--t-xs);
        margin: 0 auto var(--sp-4);
        max-width: 52rem;
        text-align: center;
    }

    .reg-prices {
            grid-template-columns: 1fr;
        }
    }

    .reg-price {
        background: #fff;
        border-radius: 6px;
        box-shadow: 0 10px 30px rgb(42 31 110 / 12%);
        overflow: hidden;
        text-align: center;
    }

    .reg-price__head {
        align-items: center;
        background: #eaf0ff;
        color: var(--g-navy);
        display: flex;
        gap: var(--sp-4);
        justify-content: center;
        min-height: 4.1rem;
        /* = member head + badge */
        padding: var(--sp-3) var(--sp-4);
    }

    .reg-price__head i {
        font-size: 1.7rem;
    }

    .reg-price__head h3 {
        color: inherit;
        font-family: var(--f-body);
        font-size: var(--t-xs);
        font-weight: 800;
        letter-spacing: .03em;
        margin: 0;
        text-transform: uppercase;
    }

    body.rtl .reg-price__head h3 {
        letter-spacing: 0;
    }

    .reg-price--member .reg-price__head {
        background: linear-gradient(135deg, #3326b8 0%, #231a9a 100%);
        color: #fff;
        min-height: 2.5rem;
        padding-block: .5rem;
    }

    .reg-price--member .reg-price__head i {
        font-size: 1.25rem;
    }

    .reg-price__badge {
        align-items: center;
        background: #dcd3fb;
        color: var(--g-navy);
        display: flex;
        font-size: .78rem;
        gap: var(--sp-2);
        justify-content: center;
        margin: 0;
        min-height: 1.6rem;
        padding: .25rem var(--sp-3);
    }

    .reg-price__body {
        padding: var(--sp-4) var(--sp-5) var(--sp-5);
    }

    .reg-price__amount {
        align-items: baseline;
        color: var(--g-navy);
        display: flex;
        gap: .4rem;
        justify-content: center;
        margin: 0;
    }

    .reg-price__figure {
        font-family: var(--f-title);
        font-size: clamp(2.2rem, 1.8rem + 1.6vw, 2.9rem);
        font-style: italic;
        font-weight: 800;
        line-height: 1.05;
    }

    .reg-price__currency {
        font-family: var(--f-title);
        font-size: clamp(1.3rem, 1.1rem + .7vw, 1.75rem);
        font-style: italic;
        font-weight: 700;
    }

    .reg-price__alt {
        color: var(--g-navy);
        font-size: 1.05rem;
        font-weight: 600;
        margin: 0 0 var(--sp-3);
    }

    .reg-price__amount+.reg-btn,
    .reg-price__amount+form {
        margin-block-start: var(--sp-4);
    }

    /* ---------------------------------------------------------------- buttons */
    .reg-btn {
        align-items: center;
        border: 2px solid var(--g-indigo);
        border-radius: 5px;
        cursor: pointer;
        display: inline-flex;
        font-family: inherit;
        font-size: .8rem;
        font-weight: 800;
        gap: .65rem;
        justify-content: center;
        letter-spacing: .03em;
        min-width: min(100%, 12.5rem);
        padding: .72rem 1.3rem;
        text-decoration: none;
        text-transform: uppercase;
        transition: background-color var(--dur-2) var(--ease), color var(--dur-2) var(--ease), transform var(--dur-2) var(--ease), box-shadow var(--dur-2) var(--ease);
    }

    .reg-btn i {
        font-size: .72em;
    }

    [dir="rtl"] .reg-btn i,
    [dir="rtl"] .reg-member__cta i {
        transform: scaleX(-1);
    }

    .reg-btn--solid {
        background: linear-gradient(135deg, #2f27b8 0%, #231a9a 100%);
        color: #fff;
    }

    .reg-btn--solid:hover {
        box-shadow: var(--sh-brand);
        color: #fff;
        transform: translateY(-2px);
    }

    .reg-btn--outline {
        background: #fff;
        color: var(--g-indigo);
    }

    .reg-btn--outline:hover {
        background: var(--g-indigo);
        color: #9d6c20;
        transform: translateY(-2px);
    }

    .reg-btn--gold {
        background: var(--g-gold);
        border-color: var(--g-gold);
        color: #fff;
        min-width: min(100%, 16rem);
    }

    .reg-btn--gold:hover {
        background: #9d6c20;
        border-color: #9d6c20;
        color: #fff;
        transform: translateY(-2px);
    }

    .reg-btn--block {
        display: flex;
        width: 100%;
    }

    .reg-btn--off {
        background: #eceaf5;
        border-color: #eceaf5;
        color: #8b86a8;
        cursor: not-allowed;
    }

    .reg-btn:focus-visible,
    .reg-member__cta:focus-visible,
    .reg-link:focus-visible,
    .reg-eye:focus-visible {
        outline: 3px solid rgb(115 132 255 / 55%);
        outline-offset: 2px;
    }

    .reg-note {
        color: var(--g-navy);
        font-size: var(--t-sm);
        margin: var(--sp-4) auto var(--sp-5);
        max-width: 52rem;
        text-align: center;
    }

    /* ------------------------------------------------------------ member band */
    .reg-member {
        align-items: center;
        background: linear-gradient(120deg, #ebe9fd 0%, #dfe1fb 100%);
        border: 1px solid #d3d6f6;
        border-radius: 8px;
        display: flex;
        gap: var(--sp-5);
        isolation: isolate;
        overflow: hidden;
        padding: var(--sp-5);
        position: relative;
    }

    .reg-member::before {
        -webkit-mask-image: linear-gradient(var(--g-from-end), #000, transparent);
        mask-image: linear-gradient(var(--g-from-end), #000, transparent);
        background-image: var(--g-lat-white);
        background-size: 88px 88px;
        content: '';
        inset-block: 0;
        inset-inline-end: 0;
        opacity: .8;
        position: absolute;
        width: 45%;
        z-index: -1;
    }

    .reg-member__icon {
        align-items: center;
        background: #fff;
        border-radius: 50%;
        color: #4a35d0;
        display: flex;
        flex: 0 0 auto;
        font-size: 1.9rem;
        height: 5rem;
        justify-content: center;
        width: 5rem;
    }

    .reg-member__body {
        border-inline-start: 1px solid rgb(42 31 110 / 30%);
        flex: 1 1 auto;
        padding-inline-start: var(--sp-5);
    }

    .reg-member__title {
        color: var(--g-navy);
        font-size: 1.25rem;
        font-weight: 800;
        margin: 0 0 var(--sp-2);
    }

    .reg-member__text {
        color: #3d3770;
        font-size: .88rem;
        line-height: 1.55;
        margin: 0;
    }

    .reg-member__cta {
        align-items: center;
        background: linear-gradient(135deg, #2f27b8 0%, #231a9a 100%);
        border-radius: 5px;
        color: #fff;
        display: inline-flex;
        flex: 0 0 auto;
        font-size: .76rem;
        font-weight: 800;
        gap: .7rem;
        max-width: 13rem;
        padding: .85rem 1.2rem;
        text-decoration: none;
        text-transform: uppercase;
        transition: transform var(--dur-2) var(--ease), box-shadow var(--dur-2) var(--ease);
    }

    .reg-member__cta:hover {
        box-shadow: var(--sh-brand);
        color: #fff;
        transform: translateY(-2px);
    }

    @media (max-width: 991.98px) {
        .reg-member {
            align-items: flex-start;
            flex-direction: column;
        }

        .reg-member__body {
            border: 0;
            padding: 0;
        }

        .reg-member__cta {
            max-width: none;
            width: 100%;
            justify-content: center;
        }
    }

    /* ------------------------------------------------------------ the 2 paths */
    .reg-paths::before {
        -webkit-mask-image: linear-gradient(var(--g-from-start), #000, transparent);
        mask-image: linear-gradient(var(--g-from-start), #000, transparent);
        background-image: var(--g-lat-white);
        background-size: 88px 88px;
        content: '';
        inset-block: 0;
        inset-inline-start: 0;
        opacity: .8;
        position: absolute;
        width: min(22%, 320px);
        z-index: -1;
    }

    .reg-paths__grid {
        align-items: stretch;
        display: grid;
        gap: var(--sp-3);
        grid-template-columns: minmax(0, 1fr) auto minmax(0, 1fr);
    }

    @media (max-width: 991.98px) {
        .reg-paths__grid {
            grid-template-columns: 1fr;
        }
    }

    .reg-path {
        border-radius: 10px;
        padding: var(--sp-5);
    }

    .reg-path--login {
        background: #fff;
        border: 1px solid #e4e1f8;
        box-shadow: 0 8px 26px rgb(42 31 110 / 8%);
    }

    .reg-path--create {
        background: linear-gradient(135deg, #fffaf0 0%, #fdf1da 100%);
        border: 1px solid #f6e5c4;
        display: flex;
        flex-direction: column;
        isolation: isolate;
        justify-content: center;
        overflow: hidden;
        position: relative;
    }

    .reg-path--create::after {
        -webkit-mask-image: linear-gradient(var(--g-from-end), #000, transparent);
        mask-image: linear-gradient(var(--g-from-end), #000, transparent);
        background-image: var(--g-lat-gold);
        background-size: 88px 88px;
        content: '';
        inset-block: 0;
        inset-inline-end: 0;
        opacity: .25;
        position: absolute;
        width: 55%;
        z-index: -1;
    }

    .reg-path__head {
        align-items: flex-start;
        display: flex;
        gap: var(--sp-4);
    }

    .reg-path__icon {
        align-items: center;
        background: #e6e3fb;
        border-radius: 50%;
        color: var(--g-navy);
        display: flex;
        flex: 0 0 auto;
        font-size: 1.5rem;
        height: 4rem;
        justify-content: center;
        width: 4rem;
    }

    .reg-path--create .reg-path__icon {
        background: #fae7c6;
        color: var(--g-gold);
    }

    .reg-path__title {
        color: var(--g-navy);
        font-size: 1.1rem;
        font-weight: 800;
        margin: 0 0 var(--sp-1);
    }

    .reg-path__text {
        color: var(--g-ink);
        font-size: .88rem;
        line-height: 1.55;
        margin: 0;
    }

    .reg-path__text--spaced {
        margin: var(--sp-4) 0 var(--sp-5);
    }

    .reg-path--create .reg-btn {
        align-self: flex-start;
    }

    .reg-or {
        align-items: center;
        display: flex;
        flex-direction: column;
        justify-content: center;
        position: relative;
    }

    .reg-or::before {
        background: var(--g-line);
        content: '';
        inset-block: 1.2rem;
        position: absolute;
        width: 1px;
    }

    .reg-or span {
        align-items: center;
        background: #e8e9ff;
        border-radius: 50%;
        color: var(--g-navy);
        display: flex;
        font-size: .75rem;
        font-weight: 800;
        height: 2.5rem;
        justify-content: center;
        position: relative;
        width: 2.5rem;
    }

    @media (max-width: 991.98px) {
        .reg-or {
            padding-block: var(--sp-2);
        }

        .reg-or::before {
            height: 1px;
            inset: 50% 0 auto;
            width: auto;
        }
    }

    .reg-form {
        margin-block-start: var(--sp-3);
    }

    .reg-form .form-label {
        color: var(--g-navy);
        font-size: .76rem;
        font-weight: 700;
        margin: var(--sp-2) 0 .25rem;
    }

    .reg-input {
        background: #fff;
        border: 1px solid #d8d5ea;
        border-radius: 4px;
        color: var(--g-navy);
        font-family: inherit;
        font-size: var(--t-sm);
        min-height: 2.4rem;
        padding: .4rem .8rem;
        width: 100%;
    }

    .reg-input::placeholder {
        color: #9d99ba;
    }

    .reg-input:focus {
        border-color: var(--d-periwinkle, #7384ff);
        box-shadow: 0 0 0 4px rgb(115 132 255 / 18%);
        outline: none;
    }

    .reg-input.is-invalid {
        border-color: #d4483e;
    }

    .reg-input-wrap {
        position: relative;
    }

    .reg-input-wrap .reg-input {
        padding-inline-end: 2.6rem;
    }

    .reg-eye {
        background: none;
        border: 0;
        color: #6b6890;
        cursor: pointer;
        inset-block: 0;
        inset-inline-end: .4rem;
        padding: 0 .6rem;
        position: absolute;
    }

    .reg-form__row {
        align-items: center;
        display: flex;
        flex-wrap: wrap;
        gap: var(--sp-2) var(--sp-4);
        justify-content: space-between;
        margin: var(--sp-3) 0;
    }

    .reg-check {
        align-items: center;
        color: var(--g-navy);
        cursor: pointer;
        display: inline-flex;
        font-size: .76rem;
        font-weight: 600;
        gap: var(--sp-2);
        margin: 0;
    }

    .reg-link {
        color: var(--g-purple);
        font-size: .76rem;
        text-decoration: none;
    }

    .reg-link:hover {
        text-decoration: underline;
    }

    .reg-form .field-error,
    .reg-form .invalid-feedback {
        color: #b3261e;
        font-size: var(--t-xs);
    }

    /* ------------------------------------------------------------------ trust */
    .reg-trust-wrap {
        background: linear-gradient(180deg, #f8f7ff 0%, #f1efff 100%);
        padding-block: var(--sp-3) var(--sp-8);
    }

    .reg-trust {
        background: #eceefe;
        border-radius: 8px;
        display: grid;
        gap: var(--sp-4) 0;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        list-style: none;
        margin: 0;
        padding: var(--sp-4) 0;
    }

    @media (max-width: 767.98px) {
        .reg-trust {
            grid-template-columns: 1fr;
        }
    }

    .reg-trust__item {
        align-items: center;
        display: flex;
        gap: var(--sp-4);
        padding-inline: var(--sp-5);
    }

    .reg-trust__item+.reg-trust__item {
        border-inline-start: 1px solid #cfd5f3;
    }

    @media (max-width: 767.98px) {
        .reg-trust__item+.reg-trust__item {
            border: 0;
        }
    }

    .reg-trust__icon {
        color: var(--g-navy);
        flex: 0 0 auto;
        font-size: 2.2rem;
    }

    .reg-trust__title {
        color: var(--g-navy);
        font-size: .9rem;
        font-weight: 800;
        margin: 0 0 2px;
    }

    .reg-trust__text {
        color: var(--g-ink);
        font-size: .8rem;
        line-height: 1.5;
        margin: 0;
    }

    @media (prefers-reduced-motion: reduce) {

        .reg-btn,
        .reg-member__cta,
        .reg-currency__opt span {
            transition: none;
        }

        .reg-btn:hover,
        .reg-member__cta:hover {
            transform: none;
        }
    }
</style>