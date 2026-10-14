@extends('layouts.app')

@section('title', __('presentation.title'))
@section('description', __('presentation.intro.lede'))

@section('content')

@php
    $year = $edition->year;

    /* ---- Images. Banner = online placeholder (swap for a Rabat / Oudayas photo, e.g. asset('assets/images/presentation/banner.jpg')).
          Each section keeps a solid CSS fallback, so a dead link never breaks the layout. ---- */
    $imgBanner = 'assets/images/presentation/logoarabiia.png';
    $imgAbout  = ['src' => 'assets/images/presentation/popup.png', 'width' => 660, 'height' => 430];
    $imgSoft   = 'https://images.unsplash.com/photo-1613327986042-63d4425a1a5d?auto=format&fit=crop&w=1800&q=60';

    /* Wraps the last $n words of a title in an accent span (fr / en / ar). */
    $accent = static function (string $text, int $n): \Illuminate\Support\HtmlString {
        $words = preg_split('/\s+/u', trim($text)) ?: [];
        $n = max(0, min($n, count($words) - 1));
        if ($n === 0) {
            return new \Illuminate\Support\HtmlString(e($text));
        }
        return new \Illuminate\Support\HtmlString(
            e(implode(' ', array_slice($words, 0, -$n))) .
            ' <span class="pr-accent">' . e(implode(' ', array_slice($words, -$n))) . '</span>'
        );
    };

    // Journey: icon + tint per step (texts come from the lang file, in order).
    $stepStyle = [
        ['icon' => 'fa-brain',        'tone' => 'violet'],
        ['icon' => 'fa-bullseye',     'tone' => 'violet'],
        ['icon' => 'fa-brain', 'tone' => 'violet'],
    ];
    $steps = (array) __('presentation.journey.steps');

    // Organisation cards: DB rows when published, static fallback otherwise.
    $orgLogos = [
        ['src' => 'assets/images/presentation/logoarabiia.png',   'width' => 628, 'height' => 206],
        ['src' => 'assets/images/presentation/logo-iia-maroc.png', 'width' => 228, 'height' => 206],
    ];
    $orgTones = ['ARABCIA' => 'gold', 'IIA_MAROC' => 'navy'];
$orgWebsites = [ 'ARABCIA' => 'https://arabciia.org/ar/', 'IIA_MAROC' => 'https://www.iiamaroc.org/', ];
   $orgCards = collect($organisations ?? [])->map(static function ($o) use ($orgWebsites): array { return [ 'code' => $o->code, 'name' => $o->name, 'text' => $o->description, 'logo' => $o->logo_path, 'url' => $orgWebsites[$o->code] ?? $o->website_url, ]; }); if ($orgCards->isEmpty()) { $orgCards = collect([ [ 'code' => 'ARABCIA', 'name' => 'ARABCIA', 'text' => __('presentation.organisations.organiser_desc'), 'logo' => null, 'url' => $orgWebsites['ARABCIA'], ], [ 'code' => 'IIA_MAROC', 'name' => 'IIA Maroc', 'text' => __('presentation.organisations.host_desc'), 'logo' => null, 'url' => $orgWebsites['IIA_MAROC'], ], ]); } 
@endphp

{{-- Shared front-office hero (unchanged). --}}
<div class="presentation-hero"  >
        <x-front.hero
        style="margin-bottom: 10%;"
            :title="__('presentation.title')"
            :eyebrow="$edition->identityLabel()"
            :lede="__('presentation.intro.lede')"
            :crumbs="[__('nav.presentation') => null]"
            :facts="[
                ['icon' => 'fa-calendar-alt', 'label' => $edition->dateLine($locale)],
                ['icon' => 'fa-map-marker-alt', 'label' => $edition->venueLine($locale)],
            ]"
            :cta-label="$edition->registration_open ? __('nav.registration') : null"
            :cta-url="$edition->registration_open ? (auth()->check() ? route('pricing') : route('register')) : null"
            :secondary-label="__('nav.programme')"
            :secondary-url="route('programme')"
        />
</div>

<div class="pr-page" >

    {{-- ================= 1. BANNER ================= --}}
    <section class="pr-intro" aria-labelledby="presentation-banner-title">
        <div class="pr-intro__photo" style="background-image: url('{{ asset('assets/images/ban_presentation.webp') }}'); background-size: cover; background-position: center; background-repeat: no-repeat;" aria-hidden="true"></div>

        <div class="container">
            <div class="pr-intro__body">
                <h2 id="presentation-banner-title" class="pr-intro__title">
                    {{ $accent(__('presentation.banner.title_lead') . ' ' . __('presentation.banner.title_accent', ['year' => $year]), 2) }}
                </h2>
                <p class="pr-intro__lede">@lang('presentation.banner.lede')</p>
<!-- 
                <ul class="pr-intro__facts">
                    <li>
                        <span class="pr-intro__icon" aria-hidden="true"><i class="fas fa-calendar-days"></i></span>
                        <strong>{{ $edition->dateLine($locale) }}</strong>
                    </li>
                    <li>
                        <span class="pr-intro__icon" aria-hidden="true"><i class="fas fa-location-dot"></i></span>
                        <strong>{{ $edition->venueLine($locale) }}</strong>
                    </li>
                </ul> -->
            </div>
        </div>
    </section>

    {{-- ================= 2. ABOUT ================= --}}
    <section class="pr-about" aria-labelledby="presentation-about-title">
        <div class="container">
            <div class="pr-about__grid">
                <div class="pr-about__body">
                    <p class="pr-eyebrow pr-eyebrow--start">@lang('presentation.about.eyebrow')</p>
                    <h2 id="presentation-about-title" class="pr-about__title">
                        {{ $accent(__('presentation.about.title'), (int) __('presentation.about.accent')) }}
                    </h2>

                    @foreach ((array) __('presentation.about.paragraphs', ['year' => $year]) as $paragraph)
                        <p class="pr-about__text">{!! $paragraph !!}</p>
                    @endforeach
                </div>

                <figure class="pr-about__media">
                    <img src="{{ asset($imgAbout['src']) }}" width="{{ $imgAbout['width'] }}" height="{{ $imgAbout['height'] }}"
                         alt="@lang('presentation.about.alt')" loading="lazy" decoding="async">
                </figure>
            </div>
        </div>
    </section>

    {{-- ================= 3. JOURNEY ================= --}}
    <section class="pr-journey pr-bgimg"  aria-labelledby="presentation-journey-title">
        <div class="container">
            <header class="pr-head">
                <p class="pr-eyebrow">@lang('presentation.journey.eyebrow')</p>
                <h2 id="presentation-journey-title" class="pr-title">{{ $accent(__('presentation.journey.title'), 2) }}</h2>
                <p class="pr-lede">@lang('presentation.journey.lede')</p>
            </header>

            <ol class="pr-steps" data-ux-stagger="90">
                @foreach ($steps as $i => $step)
                    <li class="pr-step pr-step--{{ $stepStyle[$i]['tone'] ?? 'violet' }} ux-reveal">
                        <span class="pr-step__icon" aria-hidden="true"><i class="fas {{ $stepStyle[$i]['icon'] ?? 'fa-star' }}"></i></span>
                        <div>
                            <h3 class="pr-step__title">{{ $step['title'] }}</h3>
                            <p class="pr-step__text">{{ $step['text'] }}</p>
                        </div>
                    </li>
                @endforeach
            </ol>
        </div>
    </section>

    {{-- ================= 4. ORGANISERS ================= --}}
    <section class="pr-orgs pr-bgimg"  aria-labelledby="presentation-orgs-title">
        <div class="container">
            <header class="pr-head">
                <p class="pr-eyebrow">@lang('presentation.organisations.eyebrow')</p>
                <h2 id="presentation-orgs-title" class="pr-title">@lang('presentation.organisations.title')</h2>
                <p class="pr-lede">@lang('presentation.organisations.lede')</p>
            </header>

            <ul class="pr-org-grid" data-ux-stagger="90">
                @foreach ($orgCards as $org)
                  @php $logo = $orgLogos[$loop->index] ?? $orgLogos[0]; $profile = __('presentation.organisations.profiles.' . $org['code']); $profile = is_array($profile) ? $profile : null; $tone = $orgTones[$org['code']] ?? 'navy'; $href = $org['url'] ?: route('contact'); $isExternal = !empty($org['url']); @endphp

                    <li class="pr-org ux-reveal">
                        <span class="pr-org__mark">
                            <img src="{{ asset($org['logo'] ?: $logo['src']) }}" width="{{ $logo['width'] }}" height="{{ $logo['height'] }}"
                                 alt="{{ $org['name'] }}" loading="lazy" decoding="async">
                        </span>

                        <div class="pr-org__text">
                            @if ($profile && isset($profile['paragraphs']))
                                @foreach ($profile['paragraphs'] as $p)
                                    <p>{{ $p }}</p>
                                @endforeach
                            @else
                                <p>{{ $org['text'] }}</p>
                            @endif
                        </div>

                        @if ($profile && isset($profile['stats']))
                            <dl class="pr-org__stats">
                                @foreach ($profile['stats'] as $stat)
                                    <div class="pr-org__stat">
                                        <dt class="pr-org__stat-icon" aria-hidden="true"><i class="fas {{ $stat['icon'] }}"></i></dt>
                                        <dd>
                                            @isset($stat['pre'])<small>{{ $stat['pre'] }}</small>@endisset
                                            <strong>{{ $stat['value'] }}</strong>
                                            <span>{{ $stat['label'] }}</span>
                                        </dd>
                                    </div>
                                @endforeach
                            </dl>
                        @endif

                        <a href="{{ $href }}" class="pr-org__cta pr-org__cta--{{ $tone }}" @if ($isExternal) target="_blank" rel="noopener noreferrer" @endif> <span>{{ $profile['cta'] ?? $org['name'] }}</span> <i class="fas fa-chevron-right" aria-hidden="true"></i> </a>
                    </li>
                @endforeach
            </ul>
        </div>
    </section>
</div>

{{-- Styles live INSIDE the section on purpose: anything after @endsection in a
     child view is printed before <!DOCTYPE>, which forces quirks mode. --}}
<style>
    
    .pr-page {
        --pr-ink: #14106a;
        --pr-brand: #2f27b8;
        --pr-accent: #6c3fe6;
        --pr-muted: #3f3d8f;
        --pr-line: #d3d9f4;
        --pr-gold: #b9822a;

        --pr-lat: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='88' height='88' viewBox='0 0 88 88'%3E%3Cg fill='none' stroke='%23ffffff' stroke-width='1.3'%3E%3Crect x='22' y='22' width='44' height='44'/%3E%3Crect x='22' y='22' width='44' height='44' transform='rotate(45 44 44)'/%3E%3Ccircle cx='44' cy='44' r='10'/%3E%3Cpath d='M0 0L22 22M88 0L66 22M0 88L22 66M88 88L66 66'/%3E%3C/g%3E%3C/svg%3E");
        --pr-wave: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 1440 220' preserveAspectRatio='none'%3E%3Cpath d='M0 150C240 70 470 210 760 135S1210 60 1440 120V220H0Z' fill='%23ffffff' fill-opacity='.38'/%3E%3Cpath d='M0 150C240 70 470 210 760 135S1210 60 1440 120' fill='none' stroke='%23ffffff' stroke-opacity='.9' stroke-width='2'/%3E%3Cpath d='M0 175C260 110 520 220 800 160S1230 100 1440 150' fill='none' stroke='%23ffffff' stroke-opacity='.55' stroke-width='1.5'/%3E%3C/svg%3E");

        --pr-to-start: to right;
        --pr-to-end: to left;
        color: var(--pr-ink);
    }
    [dir="rtl"] .pr-page { --pr-to-start: to left; --pr-to-end: to right; }

    .pr-page section { overflow: hidden; position: relative; }
    .pr-page ul, .pr-page ol { list-style: none; padding: 0; }
    [dir="rtl"] .pr-page .fa-chevron-right { transform: scaleX(-1); }
    .pr-accent { color: var(--pr-accent); }

    /* Photo washed with lavender so it only reads as texture */
    .pr-bgimg {
        background-color: #f4f2ff;
        background-image: linear-gradient(180deg, rgb(250 249 255 / 93%) 0%, rgb(240 237 254 / 95%) 100%), var(--pr-bg, none);
        background-position: center;
        background-size: cover;
        isolation: isolate;
    }

    /* ---------- Shared headings ---------- */
    .pr-head { margin-block-end: 2.25rem; text-align: center; }
    .pr-eyebrow { margin-top: 20px; margin-bottom: 20px; color: var(--pr-brand); font-size: .72rem; font-weight: 800; letter-spacing: .2em; text-transform: uppercase; }
    .pr-eyebrow::after { background: linear-gradient(90deg, var(--pr-brand), #c13bd8); border-radius: 2px; content: ''; display: block; height: 2px; margin: .4rem auto 0; width: 2.6rem; }
    .pr-eyebrow--start::after { margin-inline: 0 auto; }
    [dir="rtl"] .pr-eyebrow { letter-spacing: .04em; }
    .pr-title { font-size: clamp(1.6rem, 1.25rem + 1.5vw, 2.1rem); font-weight: 800; line-height: 1.2; margin-block-end: .6rem; }
    .pr-lede { color: var(--pr-muted); font-size: .92rem; line-height: 1.6; margin-inline: auto; max-width: 46rem; }

    /* ---------- 1. Banner ---------- */
    .pr-intro {
        background:
            radial-gradient(80% 90% at 0% 0%, rgb(255 255 255 / 85%) 0%, transparent 60%),
            linear-gradient(120deg, #f4f0ff 0%, #e6e0fd 50%, #d6cdf8 100%);
        isolation: isolate;
        min-height: 21rem;
        padding-block: clamp(2.25rem, 4.5vw, 3.25rem);
    }
    .pr-intro__photo {
        -webkit-mask-image: linear-gradient(var(--pr-to-end), #000 55%, transparent 100%);
        mask-image: linear-gradient(var(--pr-to-end), #000 55%, transparent 100%);
        background-color: #8b7bd8;
        background-position: center 45%;
        background-repeat: no-repeat;
        background-size: cover;
        inset-block: 0;
        inset-inline-end: 0;
        position: absolute;
        width: min(66%, 980px);
        z-index: -1;
    }
    .pr-intro::before {
        -webkit-mask-image: linear-gradient(var(--pr-to-start), #000 0%, transparent 100%);
        mask-image: linear-gradient(var(--pr-to-start), #000 0%, transparent 100%);
        background-image: var(--pr-lat);
        background-size: 88px 88px;
        content: '';
        inset-block: 0;
        inset-inline-start: 0;
        position: absolute;
        width: min(16%, 240px);
        z-index: -1;
    }
    .pr-intro::after {
        background: var(--pr-wave) bottom / 100% 100% no-repeat, radial-gradient(70% 100% at 10% 120%, rgb(140 120 255 / 45%) 0%, transparent 70%);
        content: '';
        height: 38%;
        inset: auto 0 0 0;
        pointer-events: none;
        position: absolute;
        z-index: -1;
    }
    .pr-intro__body { max-width: 34rem; }
    .pr-intro__body::before { background: var(--pr-brand); border-radius: 2px; content: ''; display: block; height: 4px; margin-block-end: 1rem; width: 3.2rem; }
    .pr-intro__title { color: var(--pr-ink); font-size: clamp(2rem, 1.3rem + 2.4vw, 3rem); font-weight: 800; line-height: 1.08; margin-block-end: 1rem; }
    [dir="rtl"] .pr-intro__title { line-height: 1.35; }
    .pr-intro__title .pr-accent { background: linear-gradient(90deg, #5a35e0, #8a5cf6); -webkit-background-clip: text; background-clip: text; color: transparent; display: block; }
    .pr-intro__lede { color: #1d1a5e; font-size: 1.05rem; line-height: 1.55; margin-block-end: 1.4rem; max-width: 28rem; }
    .pr-intro__facts { align-items: center; display: flex; flex-wrap: wrap; gap: .75rem 0; }
    .pr-intro__facts li { align-items: center; color: #2c22a8; display: flex; gap: .75rem; }
    .pr-intro__facts li + li { border-inline-start: 1px solid rgb(42 31 110 / 28%); margin-inline-start: 1.4rem; padding-inline-start: 1.4rem; }
    .pr-intro__facts strong { font-size: .85rem; line-height: 1.3; max-width: 13rem; }
    .pr-intro__icon { color: #4b2fd0; font-size: 1.7rem; line-height: 1; }

    /* ---------- 2. About ---------- */
    .pr-about { background: #fff; padding-block: clamp(2.25rem, 5vw, 3.5rem); }
    .pr-about__grid { align-items: center; display: grid; gap: 3rem; grid-template-columns: minmax(0, 1.1fr) minmax(0, 1fr); }
    .pr-about__title { color: var(--pr-ink); font-size: clamp(1.6rem, 1.2rem + 1.6vw, 2.2rem); font-weight: 800; line-height: 1.2; margin-block-end: 1.25rem; }
    .pr-about__text { color: var(--pr-muted); font-size: .9rem; line-height: 1.7; margin-block-end: .9rem; max-width: 36rem; }
    .pr-about__text strong { color: var(--pr-ink); }
    .pr-about__media { aspect-ratio: 4 / 3; border-radius: 14px; box-shadow: 0 28px 50px -26px rgba(60, 40, 160, .5); margin: 0; overflow: hidden; }
    .pr-about__media img { display: block; height: 100%; object-fit: cover; width: 100%; }

    /* ---------- 3. Journey ---------- */
    .pr-journey { padding-block: clamp(2.25rem, 5vw, 3.25rem); }
    .pr-steps { display: grid; gap: 1.1rem; grid-template-columns: repeat(3, minmax(0, 1fr)); }
    .pr-step { align-items: center; border: 1px solid #e4e1f8; border-radius: 10px; box-shadow: 0 10px 28px rgb(42 31 110 / 8%); display: flex; gap: 1.1rem; padding: 1.25rem 1.3rem; }
    .pr-step--violet { background: #f4f1ff; }
    .pr-step--blue { background: #eef4ff; }
    .pr-step__icon { align-items: center; background: #e6e0fd; border-radius: 50%; color: #5b3fd9; display: inline-flex; flex: 0 0 auto; font-size: 1.6rem; height: 4rem; justify-content: center; width: 4rem; }
    .pr-step--blue .pr-step__icon { background: #dbe8ff; color: #2f6bd9; }
    .pr-step__title { color: var(--pr-ink); font-size: .9rem; font-weight: 800; margin-block-end: .35rem; text-transform: uppercase; }
    .pr-step__text { color: var(--pr-muted); font-size: .78rem; line-height: 1.5; }

    /* ---------- 4. Organisers ---------- */
    .pr-orgs { padding-block: clamp(2.25rem, 5vw, 3.25rem); }
    .pr-orgs .pr-lede { max-width: 38rem; }
    .pr-org-grid { align-items: stretch; display: grid; gap: 1.5rem; grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .pr-org { background: #fff; border-radius: 22px; box-shadow: 0 14px 40px rgb(42 31 110 / 14%); display: flex; flex-direction: column; gap: 1.1rem; padding: 1.75rem 1.9rem; }
    .pr-org__mark { display: block; height: 4.2rem; }
    .pr-org__mark img { height: 100%; max-width: 100%; object-fit: contain; object-position: left center;  }
    [dir="rtl"] .pr-org__mark img { object-position: right center; }
    .pr-org__text { display: grid; gap: .65rem; }
    .pr-org__text p { color: var(--pr-muted); font-size: .8rem; line-height: 1.6; }

    .pr-org__stats { align-items: center; display: flex; flex-wrap: wrap; gap: 1rem 0; margin-block-start: auto; }
    .pr-org__stat { align-items: center; display: flex; gap: .75rem; padding-inline-end: 1.25rem; }
    .pr-org__stat + .pr-org__stat { border-inline-start: 1px solid var(--pr-line); padding-inline-start: 1.25rem; }
    .pr-org__stat-icon { color: var(--pr-brand); font-size: 1.7rem; line-height: 1; }
    .pr-org__stat dd { color: var(--pr-muted); display: grid; font-size: .74rem; line-height: 1.25; }
    .pr-org__stat small { font-size: .7rem; }
    .pr-org__stat strong { color: var(--pr-ink); font-size: 1.45rem; font-weight: 800; line-height: 1.1; }

    .pr-org__cta {margin-top: 2%; align-items: center; align-self: flex-start; border-radius: 4px; color: #fff; display: inline-flex; font-size: .76rem; font-weight: 800; gap: .9rem; padding: .8rem 1.4rem; text-decoration: none; text-transform: uppercase; transition: transform .2s, filter .2s; }
    .pr-org__cta i { font-size: .72em; }
    .pr-org__cta:hover { color: #fff; filter: brightness(1.08); transform: translateY(-2px); }
    .pr-org__cta--gold { background: var(--pr-gold); }
    .pr-org__cta--navy { background: linear-gradient(135deg, #2f27b8 0%, #1d4fa8 100%); }
    .pr-org__cta:focus-visible { outline: 3px solid rgb(115 132 255 / 55%); outline-offset: 2px; }

    /* ---------- Responsive ---------- */
    @media (max-width: 991.98px) {
        .pr-about__grid, .pr-org-grid { grid-template-columns: 1fr; }
        .pr-steps { grid-template-columns: 1fr; }
    }
    @media (max-width: 767.98px) {
        .pr-intro__photo { opacity: .3; width: 100%; }
        .pr-intro::before { display: none; }
        .pr-intro__facts li + li { border: 0; margin: 0; padding: 0; }
        .pr-org { padding: 1.4rem 1.25rem; }
    }
    @media (prefers-reduced-motion: reduce) {
        .pr-org__cta { transition: none; }
        .pr-org__cta:hover { transform: none; }
    }
</style>

@endsection