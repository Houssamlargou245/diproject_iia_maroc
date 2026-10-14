@extends('layouts.app')

@section('title', __('pricing.title'))
@section('description', __('pricing.currency_note'))

@section('content')

@php
/* ---- Online images (Unsplash CDN). Every one has a CSS fallback, so a dead link never breaks the layout. ---- */
$imgBanner = 'https://images.unsplash.com/photo-1551882547-ff40c63fe5fa?auto=format&fit=crop&w=1800&q=80'; // hotel, palms & pool at dusk
$imgAccess = 'https://images.unsplash.com/photo-1613327986042-63d4425a1a5d?auto=format&fit=crop&w=1800&q=60'; // soft purple/white abstract
$imgTariffs = 'https://images.unsplash.com/photo-1583339522870-0d9f28cef33f?auto=format&fit=crop&w=1800&q=60'; // lavender draped textile
$imgPaths = 'https://images.unsplash.com/photo-1629196914168-3a2652305f9f?auto=format&fit=crop&w=1800&q=60'; // lavender watercolour

$hasReset = \Illuminate\Support\Facades\Route::has('password.request');
$accessIcons = ['fa-users', 'fa-microphone', 'fa-file', 'fa-users'];
$trustIcons = ['fa-shield-halved', 'fa-file-lines', 'fa-headset'];
$madPerUsd = (float) config('brand.mad_per_usd', 10); // 7 500 MAD -> 750 USD, 8 500 MAD -> 850 USD
@endphp

<div class="d-page d-page--pricing reg">

    {{-- ================= HERO (unchanged) ================= --}}
    <x-front.hero
        :title="__('pricing.hero.title', ['year' => $edition->year])"
        :eyebrow="$edition->identityLabel()"
        :lede="__('pricing.hero.lede')"
        :crumbs="[__('nav.pricing') => null]"
        :facts="[
            ['icon' => 'fa-calendar-alt', 'label' => $edition->dateLine($locale)],
            ['icon' => 'fa-map-marker-alt',  'label' => $edition->venueLine($locale)],
        ]"
        image="assets/images/bg/price_bg.jpg" />

    {{-- ================= 0. BANNER ================= --}}
    <section class="reg-banner" aria-labelledby="reg-banner-title">

        <div class="reg-banner__photo" style="background-image: url('{{ asset('assets/images/sponsors_ban.png') }}'); background-size: cover; background-position: center; background-repeat: no-repeat;" aria-hidden="true"></div>

        <div class="container reg-banner__inner">
            <p class="reg-banner__eyebrow">{{ __('pricing.banner.eyebrow') }}</p>

            <h2 class="reg-banner__title" id="reg-banner-title">
                {{ __('pricing.banner.title_a') }}
                <span class="reg-banner__accent">{{ __('pricing.banner.title_b') }}</span>
            </h2>

            <p class="reg-banner__lede">{{ __('pricing.banner.lede') }}</p>

            <ul class="reg-banner__facts">
                <!-- <li>
                    <span class="reg-banner__icon" aria-hidden="true"><i class="fas fa-calendar-days"></i></span>
                    <strong>{{ $edition->dateLine($locale) }}</strong>
                </li>
                <li>
                    <span class="reg-banner__icon" aria-hidden="true"><i class="fas fa-location-dot"></i></span>
                    <strong>{{ $edition->venueLine($locale) }}</strong>
                </li> -->
            </ul>
        </div>
    </section>

    {{-- ================= 1. WHAT YOU GET ================= --}}
    <section class="reg-section reg-access reg-bgimg" style="background-color: #fff !important;"  aria-labelledby="access-title">
        <div class="container">
            <header class="reg-head">
                <p class="reg-eyebrow">{{ __('pricing.access.eyebrow') }}</p>
                <h2 class="reg-title" id="access-title">
                    {{ __('pricing.access.title_a') }}
                    <span class="reg-title__accent">{{ __('pricing.access.title_b') }}</span>
                </h2>
            </header>

            <ul class="reg-access__grid">
                @foreach ((array) __('pricing.access.items') as $i => $item)
                <li class="reg-access__item">
                    <span class="reg-access__icon" aria-hidden="true"><i class="fas {{ $accessIcons[$i] ?? 'fa-star' }}"></i></span>
                    <h3 class="reg-access__title">{{ $item['title'] }}</h3>
                    <p class="reg-access__text">{{ $item['text'] }}</p>
                </li>
                @endforeach
            </ul>
        </div>
    </section>

    {{-- ================= 2. TARIFFS ================= --}}
    <section class="reg-section reg-tariffs reg-bgimg" style="--reg-bg:url('{{ $imgTariffs }}')" aria-labelledby="pricing-heading">
        <div class="container">
            <header class="reg-head">
                <p class="reg-eyebrow">{{ __('pricing.tariffs.eyebrow') }}</p>
                <h2 class="reg-title" id="pricing-heading">
                    {{ __('pricing.tariffs.title_a') }}
                    <span class="reg-title__accent">{{ __('pricing.tariffs.title_b') }}</span>
                </h2>
            </header>

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
            // Only ONE pair of cards (member / non-member): the MAD ticket is the reference,
            // the USD amount is shown as a second line inside each card.
            $primaryType = $ticketTypes->first(function ($t) {
            return stripos((string) $t->formatAmount($t->priceFor(false)), 'MAD') !== false;
            }) ?? $ticketTypes->first();
            @endphp

            @foreach ([$primaryType] as $type)

            <div class="reg-prices">
                {{-- First card = member rate, second = non-member rate.
                             The server still decides which rate is really charged. --}}
                @foreach ([true, false] as $memberCard)
                @php
                $amount = $type->priceFor($memberCard);
                $label = trim((string) $type->formatAmount($amount));

                // "7 500 MAD" -> figure "7 500" + currency "MAD"
                $figure = $label; $currency = null;
                if (preg_match('/^([\d\s\x{00A0}\x{202F}.,]+?)\s*([^\d\s.,][^\d]*)$/u', $label, $m)) {
                $figure = trim($m[1]); $currency = trim($m[2]);
                }

                // Second currency line ("ou 750 USD")
                $usd = null;
                if (method_exists($type, 'formatAmountUsd')) {
                $usd = $type->formatAmountUsd($amount);
                } elseif ($currency && strtoupper($currency) === 'MAD' && $madPerUsd > 0) {
                $digits = preg_replace('/\D/', '', preg_replace('/[.,]\d{1,2}$/', '', $figure));
                $usd = number_format(((float) $digits) / $madPerUsd, 0, ',', "\u{202F}") . ' USD';
                }
                @endphp

                <article @class(['reg-price', 'reg-price--member'=> $memberCard])>
                    <header class="reg-price__head">
                        <i class="fas {{ $memberCard ? 'fa-user-group' : 'fa-users' }}" aria-hidden="true"></i>
                        <h3>{{ $memberCard ? __('pricing.tariffs.member') : __('pricing.tariffs.standard') }}</h3>
                    </header>

                    @if ($memberCard)
                    <p class="reg-price__badge"><i class="fas fa-star" aria-hidden="true"></i> {{ __('pricing.tariffs.member_badge') }}</p>
                    @endif

                    <div class="reg-price__body">
                        <p class="reg-price__amount" dir="ltr">
                            <span class="reg-price__figure">{{ $figure }}</span>
                            @if ($currency)<span class="reg-price__currency">{{ $currency }}</span>@endif
                        </p>

                        @if ($usd)
                        <p class="reg-price__alt" dir="ltr">{{ __('pricing.tariffs.or') }} {{ $usd }}</p>
                        @endif

                        @if (! $registrationOpen)
                        <button type="button" class="reg-btn reg-btn--off" disabled><span>{{ __('pricing.closed') }}</span></button>

                        @elseif (! auth()->check())
                        <a href="#acces" class="reg-btn {{ $memberCard ? 'reg-btn--solid' : 'reg-btn--outline' }}">
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
                            <input type="hidden" name="ticket_type_id" value="{{ $type->id }}">
                            <input type="hidden" name="quantity" value="1">
                            <input type="hidden" name="member_quantity" value="{{ $memberCard ? 1 : 0 }}">
                            <button type="submit" class="reg-btn {{ $memberCard ? 'reg-btn--solid' : 'reg-btn--outline' }}">
                                <span>{{ __('pricing.tariffs.cta') }}</span><i class="fas fa-chevron-right" aria-hidden="true"></i>
                            </button>
                        </form>
                        @endif
                    </div>
                </article>
                @endforeach
            </div>
            @endforeach
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

    {{-- ================= 3. TWO WAYS IN (guests only) ================= --}}
    @guest
    <section class="reg-section reg-paths reg-bgimg" id="acces" style="background-color:#fff;" aria-labelledby="paths-title">
        <div class="container">
            <header class="reg-head">
                <p class="reg-eyebrow reg-eyebrow--pink">{{ __('pricing.paths.eyebrow') }}</p>
                <h2 class="reg-title" id="paths-title">{{ __('pricing.paths.title') }}</h2>
            </header>

            <div class="reg-paths__grid">

                <article class="reg-path reg-path--login">
                    <div class="reg-path__head">
                        <span class="reg-path__icon" aria-hidden="true"><i class="fas fa-key"></i></span>
                        <div>
                            <h3 class="reg-path__title">{{ __('pricing.paths.existing_title') }}</h3>
                            <p class="reg-path__text">{{ __('pricing.paths.existing_text') }}</p>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('login.store') }}" class="reg-form">
                        @csrf

                        <x-form.field name="identifier" :label="__('login.identifier')" required>
                            <input type="text" name="identifier" id="pricing-identifier" required
                                autocomplete="username" dir="ltr"
                                placeholder="{{ __('pricing.paths.identifier_ph') }}"
                                value="{{ old('identifier') }}"
                                @class(['reg-input', 'is-invalid'=> $errors->has('identifier')])>
                        </x-form.field>

                        <x-form.field name="password" :label="__('register.password')" required>
                            <div class="reg-input-wrap">
                                <input type="password" name="password" id="pricing-password" required
                                    autocomplete="current-password" dir="ltr"
                                    placeholder="{{ __('pricing.paths.password_ph') }}"
                                    @class(['reg-input', 'is-invalid'=> $errors->has('password')])>
                                <button type="button" class="reg-eye" data-reg-eye="pricing-password"
                                    aria-label="{{ __('pricing.paths.show_password') }}">
                                    <i class="far fa-eye" aria-hidden="true"></i>
                                </button>
                            </div>
                        </x-form.field>

                        <div class="reg-form__row">
                            <label class="reg-check" for="pricing-remember">
                                <input type="checkbox" name="remember" id="pricing-remember" value="1" @checked(old('remember'))>
                                <span>{{ __('login.remember') }}</span>
                            </label>

                            @if ($hasReset)
                            <a href="{{ route('password.request') }}" class="reg-link">{{ __('pricing.paths.forgot') }}</a>
                            @endif
                        </div>

                        <button type="submit" class="reg-btn reg-btn--solid reg-btn--block">
                            <span>{{ __('pricing.paths.existing_submit') }}</span>
                            <i class="fas fa-chevron-right" aria-hidden="true"></i>
                        </button>
                    </form>
                </article>

                <div class="reg-or" aria-hidden="true"><span>{{ __('pricing.paths.or') }}</span></div>

                <article class="reg-path reg-path--create">
                    <div class="reg-path__head">
                        <span class="reg-path__icon" aria-hidden="true"><i class="fas fa-user-plus"></i></span>
                        <div>
                            <h3 class="reg-path__title">{{ __('pricing.paths.create_title') }}</h3>
                            <p class="reg-path__text">{{ __('pricing.paths.create_text') }}</p>
                        </div>
                    </div>

                    <p class="reg-path__text reg-path__text--spaced">{{ __('pricing.paths.create_text_2') }}</p>

                    <a href="{{ route('register') }}" class="reg-btn reg-btn--gold">
                        <span>{{ __('pricing.paths.create_cta') }}</span>
                        <i class="fas fa-chevron-right" aria-hidden="true"></i>
                    </a>
                </article>
            </div>
        </div>
    </section>
    @endguest

    {{-- ================= 4. REASSURANCE ================= --}}
    <section class="reg-trust-wrap" aria-label="{{ __('pricing.trust.label') }}">
        <div class="container">
            <ul class="reg-trust">
                @foreach ((array) __('pricing.trust.items') as $i => $item)
                <li class="reg-trust__item">
                    <span class="reg-trust__icon" aria-hidden="true"><i class="fas {{ $trustIcons[$i] ?? 'fa-circle-check' }}"></i></span>
                    <div>
                        <h3 class="reg-trust__title">{{ $item['title'] }}</h3>
                        <p class="reg-trust__text">{{ $item['text'] }}</p>
                    </div>
                </li>
                @endforeach
            </ul>
        </div>
    </section>

</div>

<script>
    document.querySelectorAll('[data-reg-eye]').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var input = document.getElementById(btn.dataset.regEye);
            if (!input) {
                return;
            }
            var show = input.type === 'password';
            input.type = show ? 'text' : 'password';
            btn.querySelector('i').className = show ? 'far fa-eye-slash' : 'far fa-eye';
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
        background-color: #f1efff;
        /* background-image:
            linear-gradient(180deg, rgb(248 246 255 / 90%) 0%, rgb(238 235 253 / 93%) 100%),
            var(--reg-bg, none); */
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
        /* background-image: linear-gradient(180deg, rgb(255 255 255 / 94%) 0%, rgb(247 245 255 / 96%) 100%), var(--reg-bg, none); */
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
        color: #fff;
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
        background-color: #fff;
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
        .reg-member__cta {
            transition: none;
        }

        .reg-btn:hover,
        .reg-member__cta:hover {
            transform: none;
        }
    }
</style>

@endsection