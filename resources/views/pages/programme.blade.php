@extends('layouts.app')

@section('title', __('programme.page.title'))
@section('description', __('programme.page.provisional_notice'))

@section('content')

@php
    // Closing band photo: ONLINE placeholder, replace this URL by your own
    // image (e.g. asset('assets/images/conference/audience.jpg')).
    $ctaImage = 'https://images.unsplash.com/photo-1540575467063-178a50c2df87?auto=format&fit=crop&w=1800&q=70';

    $registerUrl = $edition->registration_open
        ? (auth()->check() ? route('pricing') : route('register'))
        : null;

    $programmePdfUrl = $edition->programmePdfUrl($locale);

    // Inline QR for the day-2 card: scanning it opens the programme PDF in the
    // language the reader is on. Generated server-side so no JS or third party
    // is needed and the code never breaks when the assets are blocked.
    $programmeQr = $programmePdfUrl
        ? \App\Support\QrCode::svg($programmePdfUrl, 240)
        : null;

    // Row icon by type.
    $icons = [
        'welcome'   => 'fa-users',
        'ceremony'  => 'fa-landmark',
        'trophy'    => 'fa-trophy',
        'plenary'   => 'fa-user',
        'break'     => 'fa-coffee',
        'lunch'     => 'fa-utensils',
        'workshops' => 'fa-user',
        'lab'       => 'fa-lightbulb',
    ];

    // [time, type, lang key under programme.page.s.*]
    $schedule = [
        1 => [
            ['08:00 – 09:00', 'welcome',   'welcome1'],
            ['09:30 – 10:00', 'ceremony',  'ceremony'],
            ['10:00 – 10:30', 'trophy',    'trophies'],
            ['10:30 – 11:15', 'plenary',   'pl1'],
            ['11:15 – 11:45', 'break',     'break'],
            ['11:45 – 12:30', 'plenary',   'pl2'],
            ['12:30 – 13:30', 'plenary',   'pl3'],
            ['13:30 – 14:30', 'lunch',     'lunch'],
            ['14:30 – 16:20', 'workshops', 'workshops'],
            ['16:20 – 16:50', 'break',     'break'],
            ['16:50 – 17:35', 'lab',       'lab1'],
        ],
        2 => [
            ['08:00 – 09:00', 'welcome',   'welcome2'],
            ['09:00 – 10:00', 'plenary',   'pl4'],
            ['10:00 – 11:00', 'plenary',   'pl5'],
            ['11:00 – 11:30', 'break',     'break'],
            ['11:30 – 12:30', 'plenary',   'pl6'],
            ['12:30 – 13:30', 'plenary',   'pl7'],
            ['13:30 – 14:30', 'lunch',     'lunch'],
            ['14:30 – 16:20', 'workshops', 'workshops'],
            ['16:20 – 16:50', 'break',     'break'],
            ['16:50 – 17:35', 'lab',       'lab2'],
        ],
    ];

    // Workshop time slots (same on both days).
    $slotTimes = ['14:30 – 15:00', '15:10 – 15:40', '15:50 – 16:20'];

    $tracks = [
        ['tone' => 'ai',  'icon' => 'fa-brain',         'title' => 'programme.page.track_ai_title',         'text' => 'programme.page.track_ai'],
        ['tone' => 'res', 'icon' => 'fa-shield-alt',    'title' => 'programme.page.track_resilience_title', 'text' => 'programme.page.track_res'],
        ['tone' => 'aud', 'icon' => 'fa-user-graduate', 'title' => 'programme.page.track_auditor_title',    'text' => 'programme.page.track_aud'],
    ];
@endphp

<div class="pg-page">

    <x-front.hero
        :title="__('programme.page.title')"
        :eyebrow="$edition->identityLabel()"
        :lede="__('programme.page.hero_lede')"
        :crumbs="[__('nav.programme') => null]"
        :facts="[
            ['icon' => 'fa-calendar-alt', 'label' => $edition->dateLine($locale)],
            ['icon' => 'fa-map-marker-alt', 'label' => $edition->venueLine($locale)],
        ]"
        :cta-label="$registerUrl ? __('nav.registration') : null"
        :cta-url="$registerUrl" />

    {{-- ============ 1. Intro ============ --}}
    <section class="pg-intro" aria-labelledby="pg-intro-title">
        <div class="container">
            <h2 id="pg-intro-title" class="pg-h2">@lang('programme.page.intro_title')</h2>
            <h3 class="pg-h3">@lang('programme.page.intro_heading')</h3>
            <p class="pg-p">{{ __('programme.page.intro_p1', ['year' => $edition->year]) }}</p>
            <p class="pg-p">@lang('programme.page.intro_p2')</p>
        </div>
    </section>

    {{-- ============ 2. Tracks + Lab ============ --}}
    <section class="pg-main" aria-labelledby="pg-tracks-title">
        <div class="container">

            <h2 id="pg-tracks-title" class="pg-h2 pg-h2--sm">@lang('programme.page.tracks_title')</h2>
            <p class="pg-sub">@lang('programme.page.tracks_lede')</p>

            <ul class="pg-tracks" data-ux-stagger="90">
                @foreach ($tracks as $track)
                    <li class="pg-track ux-reveal">
                        <span class="pg-track__icon" aria-hidden="true"><i class="fas {{ $track['icon'] }}"></i></span>
                        <div>
                            <h3 class="pg-track__title">{{ __($track['title']) }}</h3>
                            <p class="pg-track__text">{{ __($track['text']) }}</p>
                        </div>
                    </li>
                @endforeach
            </ul>

            <div class="pg-lab ux-reveal">
                <span class="pg-track__icon" aria-hidden="true"><i class="far fa-lightbulb"></i></span>
                <div>
                    <h3 class="pg-lab__title">@lang('programme.page.lab_title')</h3>
                    <p class="pg-lab__text">@lang('programme.page.lab_text')</p>
                </div>
            </div>
        </div>
    </section>

    {{-- ============ 3. Two days, side by side ============ --}}
    <section class="pg-schedule" aria-label="@lang('programme.page.title')">
        <div class="container">

            <div class="pg-days">
                @foreach ($schedule as $n => $rows)
                    <article class="pg-day">
                        <header class="pg-day__head">
                            <span class="pg-day__icon" aria-hidden="true"><i class="fas fa-calendar-check"></i></span>
                            <div>
                                <p class="pg-day__name">@lang('programme.page.day', ['day' => $n])</p>
                                <p class="pg-day__date">{{ __('programme.page.day'.$n.'_date') }}</p>
                            </div>
                        </header>

                        <ol class="pg-list">
                            @foreach ($rows as [$time, $type, $key])
                                @php $t = __('programme.page.s.'.$key); @endphp
                                <li class="pg-row pg-row--{{ $type }}">
                                    <p class="pg-time" dir="ltr">{{ $time }}</p>
                                    <span class="pg-ico" aria-hidden="true"><i class="fas {{ $icons[$type] }}"></i></span>

                                    <div class="pg-body">
                                        <h4 class="pg-body__title">{{ $t['title'] }}</h4>

                                        @isset($t['subtitle'])
                                            <p class="pg-body__subtitle">{{ $t['subtitle'] }}</p>
                                        @endisset

                                        @isset($t['desc'])
                                            <p class="pg-body__desc">{{ $t['desc'] }}</p>
                                        @endisset

                                        @if (! empty($t['bullets']))
                                            <ul class="pg-bullets">
                                                @foreach ($t['bullets'] as $bullet)
                                                    <li>{{ $bullet }}</li>
                                                @endforeach
                                            </ul>
                                        @endif

                                        @if ($type === 'workshops')
                                            @php
                                                $slots = __('programme.page.ws.d'.$n);
                                                $wid   = 'ws-'.$n;
                                            @endphp
                                            <div class="pg-wt" data-pg-tabs>
                                                <div class="pg-wt__list" role="tablist" aria-label="{{ $t['title'] }}">
                                                    @foreach ($tracks as $track)
                                                        <button type="button"
                                                                role="tab"
                                                                id="{{ $wid }}-tab-{{ $track['tone'] }}"
                                                                aria-controls="{{ $wid }}-panel-{{ $track['tone'] }}"
                                                                aria-selected="{{ $loop->first ? 'true' : 'false' }}"
                                                                tabindex="{{ $loop->first ? '0' : '-1' }}"
                                                                class="pg-wt__tab pg-wt__tab--{{ $track['tone'] }}">
                                                            <span class="pg-wt__ico" aria-hidden="true"><i class="fas {{ $track['icon'] }}"></i></span>
                                                            <span class="pg-wt__label">{{ __($track['title']) }}</span>
                                                        </button>
                                                    @endforeach
                                                </div>

                                                @foreach ($tracks as $track)
                                                    <div role="tabpanel"
                                                         id="{{ $wid }}-panel-{{ $track['tone'] }}"
                                                         aria-labelledby="{{ $wid }}-tab-{{ $track['tone'] }}"
                                                         tabindex="0"
                                                         class="pg-wt__panel pg-wt__panel--{{ $track['tone'] }}"
                                                         @unless ($loop->first) hidden @endunless>
                                                        <p class="pg-wt__lead">{{ __($track['text']) }}</p>

                                                        <ol class="pg-wt__timeline">
                                                            @foreach ($slots as $i => $slot)
                                                                <li class="pg-wt__item">
                                                                    <span class="pg-wt__time" dir="ltr">
                                                                        <i class="far fa-clock" aria-hidden="true"></i>{{ $slotTimes[$i] }}
                                                                    </span>
                                                                    <p class="pg-wt__title">{{ $slot[$track['tone']] }}</p>
                                                                </li>
                                                            @endforeach
                                                        </ol>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>
                                </li>
                            @endforeach
                        </ol>

                        @if ($n === 2 && $programmeQr)
                            <div class="pg-qr">
                                <div class="pg-qr__code" role="img" aria-label="@lang('programme.page.qr_text')">
                                    {!! $programmeQr !!}
                                </div>

                                <div class="pg-qr__body">
                                    <p class="pg-qr__title">@lang('programme.page.qr_title')</p>
                                    <p class="pg-qr__text">@lang('programme.page.qr_text')</p>

                                    <a href="{{ $programmePdfUrl }}" class="pg-qr__link" download>
                                        <i class="fas fa-download" aria-hidden="true"></i>
                                        <span>@lang('programme.page.qr_download')</span>
                                    </a>
                                </div>
                            </div>
                        @endif
                    </article>
                @endforeach
            </div>

            <p class="pg-note">
                <i class="fas fa-info-circle" aria-hidden="true"></i>
                <span>@lang('programme.page.notice')</span>
            </p>
        </div>
    </section>

    {{-- ============ 4. Closing band ============ --}}
    <section class="pg-cta" aria-labelledby="pg-cta-title">
        <img class="pg-cta__bg"  src="{{ asset('assets/images/devenezsponsor.png') }}"  alt="" aria-hidden="true"
             loading="lazy" decoding="async" referrerpolicy="no-referrer">

        <div class="container pg-cta__inner">
            <h2 id="pg-cta-title" class="pg-cta__title">@lang('programme.page.cta_title')</h2>

            <ul class="pg-cta__facts">
                <li><i class="far fa-calendar-alt" aria-hidden="true"></i><span>{{ $edition->dateLine($locale) }}</span></li>
                <li><i class="fas fa-map-marker-alt" aria-hidden="true"></i><span>{{ $edition->venueLine($locale) }}</span></li>
            </ul>

            <div class="pg-cta__actions">
                @if ($registerUrl)
                    <a href="{{ $registerUrl }}" class="pg-cta__btn">@lang('nav.registration')</a>
                @endif

                @if ($programmePdfUrl)
                    <a href="{{ $programmePdfUrl }}" class="pg-cta__btn pg-cta__btn--download" download>
                        <i class="fas fa-download" aria-hidden="true"></i>
                        @lang('home.landing.programme.download')
                    </a>
                @endif
            </div>
        </div>
    </section>
</div>

{{-- Styles stay inside the section (anything after @endsection prints before <!DOCTYPE>). --}}
<style>
    .pg-page {
        --pg-ink: #1b1464;
        --pg-deep: #2b1d9a;
        --pg-brand: #3a27b3;
        --pg-muted: #5f6384;
        --pg-line: #e4e1f4;
        --pg-tint: #f1effd;
        --pg-surface: #f6f5fd;
        color: var(--pg-ink);
    }

    .pg-page section { position: relative; overflow: hidden; }

    .pg-page h2, .pg-page h3, .pg-page h4,
    .pg-page p, .pg-page ul, .pg-page ol { margin: 0; }

    .pg-page ul, .pg-page ol { list-style: none; padding: 30px; }

    /* ---------- 1. Intro ---------- */
    .pg-intro { padding-block: 64px 56px; background: #fff; }

    .pg-h2 {
        margin-block-end: 36px;
        font-size: clamp(1.5rem, 2.4vw, 1.9rem);
        font-weight: 700;
        color: var(--pg-deep);
    }

    .pg-h3 {
        margin-block-end: 22px;
        font-size: clamp(1.15rem, 1.8vw, 1.4rem);
        font-weight: 700;
        color: var(--pg-deep);
    }

    .pg-p {
        max-width: 78rem;
        margin-block-end: 20px;
        font-size: 1rem;
        line-height: 1.75;
        color: var(--pg-ink);
    }

    /* ---------- 2. Tracks + Lab ---------- */
    .pg-main {
        padding-block: 56px 64px;
        background:
            radial-gradient(900px 340px at 100% 0, rgba(61, 40, 170, .08), transparent 70%),
            var(--pg-surface);
    }

    .pg-h2--sm { margin-block-end: 8px; font-size: 1.35rem; }

    .pg-sub { margin-block-end: 22px; font-size: .9rem; color: var(--pg-muted); }

    .pg-tracks {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 20px;
        margin-block-end: 20px;
    }

    .pg-track,
    .pg-lab {
        display: flex;
        gap: 18px;
        align-items: flex-start;
        padding: 22px;
        border: 1px solid var(--pg-line);
        border-radius: 12px;
        background: #fff;
    }

    .pg-track { background: linear-gradient(180deg, #f4f2fd, #fff 70%); }

    .pg-track__icon {
        display: grid;
        place-items: center;
        flex: 0 0 auto;
        width: 56px;
        height: 56px;
        border-radius: 50%;
        background: linear-gradient(150deg, #2b1d9a, #3a27b3);
        color: #fff;
        font-size: 1.3rem;
        box-shadow: 0 12px 22px -12px rgba(43, 29, 154, .8);
    }

    .pg-track__title,
    .pg-lab__title {
        margin-block-end: 8px;
        font-size: 1rem;
        font-weight: 700;
        color: var(--pg-ink);
    }

    .pg-track__text,
    .pg-lab__text {
        font-size: .84rem;
        line-height: 1.65;
        color: var(--pg-muted);
    }

    .pg-lab {
        align-items: center;
        background: linear-gradient(100deg, #ece9fb, #f6f5fd);
    }

    /* ---------- 3. Schedule: two day columns ---------- */
    .pg-schedule { padding-block: 56px 72px; background: #fff; }

    .pg-days {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 28px;
        align-items: start;
    }

    .pg-day {
        overflow: hidden;
        border: 1px solid var(--pg-line);
        border-radius: 14px;
        background: #fff;
        box-shadow: 0 24px 50px -36px rgba(43, 29, 154, .45);
    }

    .pg-day__head {
        display: flex;
        align-items: center;
        gap: 14px;
        padding: 18px 22px;
        background: linear-gradient(120deg, #2b1d9a 0%, #3a27b3 75%, #2d1f9e 100%);
        color: #fff;
    }

    .pg-day__icon {
        display: grid;
        place-items: center;
        flex: 0 0 auto;
        width: 42px;
        height: 42px;
        border-radius: 50%;
        background: rgba(255, 255, 255, .16);
        font-size: 1.1rem;
    }

    .pg-day__name { font-size: 1.05rem; font-weight: 700; line-height: 1.2; }
    .pg-day__date { font-size: .82rem; opacity: .88; }

    .pg-list { padding: 6px 22px 14px; }

    /* ---------- Day 2 — programme PDF QR ---------- */
    .pg-qr {
        display: flex;
        align-items: center;
        gap: 18px;
        margin: -13px 22px 22px;
        padding: 16px;
        border: 1px dashed var(--pg-line);
        border-radius: 12px;
        background: var(--pg-tint);
    }

    .pg-qr__code {
        flex: 0 0 auto;
        width: 138px;
        padding: 8px;
        border-radius: 10px;
        background: #fff;
        box-shadow: 0 10px 24px -18px rgba(43, 29, 154, .55);
    }

    .pg-qr__code svg { display: block; width: 100%; height: auto; }

    .pg-qr__title {
        margin-block-end: 4px;
        font-size: .95rem;
        font-weight: 700;
        color: #2b1d9a;
    }

    .pg-qr__text {
        margin-block-end: 10px;
        font-size: .82rem;
        line-height: 1.45;
        color: #4a4666;
    }

    .pg-qr__link {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        font-size: .82rem;
        font-weight: 600;
        color: #2b1d9a;
        text-decoration: none;
    }

    .pg-qr__link:hover,
    .pg-qr__link:focus-visible { text-decoration: underline; }

    .pg-row {
        display: grid;
        grid-template-columns: 96px 30px minmax(0, 1fr);
        column-gap: 14px;
        align-items: start;
        padding-block: 14px;
        border-block-end: 1px solid var(--pg-line);
    }

    .pg-row:last-child { border-block-end: 0; }

    .pg-time {
        justify-self: start;
        min-width: 100%;
        padding: 5px 8px;
        border-radius: 6px;
        background: #eef0fb;
        font-size: .74rem;
        font-weight: 600;
        text-align: center;
        white-space: nowrap;
        color: var(--pg-deep);
    }

    .pg-ico {
        display: grid;
        place-items: center;
        width: 30px;
        height: 26px;
        color: var(--pg-deep);
        font-size: 1rem;
    }

    .pg-body__title {
        font-size: .9rem;
        font-weight: 700;
        line-height: 1.4;
        color: var(--pg-ink);
    }

    .pg-body__subtitle {
        margin-block-start: 2px;
        font-size: .85rem;
        font-weight: 600;
        line-height: 1.5;
        color: var(--pg-ink);
    }

    .pg-body__desc {
        margin-block-start: 3px;
        font-size: .8rem;
        line-height: 1.6;
        color: var(--pg-muted);
    }

    .pg-bullets { margin-block-start: 6px; display: grid; gap: 3px; }

    .pg-bullets li {
        position: relative;
        padding-inline-start: 16px;
        font-size: .8rem;
        color: var(--pg-muted);
    }

    .pg-bullets li::before {
        content: "";
        position: absolute;
        inset-inline-start: 4px;
        inset-block-start: .6em;
        width: 4px;
        height: 4px;
        border-radius: 50%;
        background: var(--pg-brand);
    }

    /* breaks and meals read lighter than sessions */
    .pg-row--break .pg-body__title,
    .pg-row--lunch .pg-body__title { font-weight: 600; }

    /* ---------- Workshop tabs (one tab per track) ---------- */
    .pg-wt { margin-block-start: 14px; }

    .pg-wt__tab--ai,  .pg-wt__panel--ai  { --tone: #5a3fd0; --tone-bg: #efeaff; --tone-line: #ddd3fb; --tone-grad: linear-gradient(140deg, #6b4fe3, #4a31c4); }
    .pg-wt__tab--res, .pg-wt__panel--res { --tone: #12857f; --tone-bg: #e2f6f4; --tone-line: #c4ebe7; --tone-grad: linear-gradient(140deg, #1aa59d, #0e6f6a); }
    .pg-wt__tab--aud, .pg-wt__panel--aud { --tone: #2a8a4b; --tone-bg: #e6f6ea; --tone-line: #cbebd3; --tone-grad: linear-gradient(140deg, #38a85f, #1f7340); }

    .pg-wt__list {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 8px;
    }

    .pg-wt__tab {
        position: relative;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: flex-start;
        gap: 8px;
        padding: 12px 6px 11px;
        border: 1px solid var(--tone-line);
        border-radius: 10px;
        background: var(--tone-bg);
        color: var(--tone);
        font: inherit;
        font-size: .72rem;
        font-weight: 700;
        line-height: 1.3;
        text-align: center;
        cursor: pointer;
        -webkit-tap-highlight-color: transparent;
        transition: transform .2s, box-shadow .2s, background-color .2s, color .2s, border-color .2s;
    }

    .pg-wt__tab:hover { transform: translateY(-2px); }

    .pg-wt__tab:focus-visible {
        outline: 2px solid var(--tone);
        outline-offset: 2px;
    }

    .pg-wt__ico {
        display: grid;
        place-items: center;
        width: 32px;
        height: 32px;
        border-radius: 50%;
        background: rgba(255, 255, 255, .75);
        font-size: .95rem;
        transition: background-color .2s, transform .25s;
    }

    .pg-wt__tab[aria-selected="true"] {
        border-color: transparent;
        background: var(--tone-grad);
        color: #fff;
        box-shadow: 0 14px 22px -14px var(--tone);
    }

    .pg-wt__tab[aria-selected="true"] .pg-wt__ico {
        background: rgba(255, 255, 255, .2);
        transform: scale(1.08);
    }

    /* little pointer joining the active tab to its panel */
    .pg-wt__tab[aria-selected="true"]::after {
        content: "";
        position: absolute;
        left: 50%;
        bottom: -7px;
        width: 12px;
        height: 12px;
        background: var(--tone);
        transform: translateX(-50%) rotate(45deg);
        border-radius: 2px;
    }

    .pg-wt__panel {
        margin-block-start: 14px;
        padding: 16px 16px 4px;
        border: 1px solid var(--tone-line);
        border-block-start: 3px solid var(--tone);
        border-radius: 12px;
        background: linear-gradient(180deg, var(--tone-bg), #fff 90px);
    }

    .pg-wt__panel[hidden] { display: none; }

    .pg-wt__panel:not([hidden]) { animation: pgWtIn .3s ease both; }

    .pg-wt__panel:focus-visible { outline: 2px solid var(--tone); outline-offset: 2px; }

    @keyframes pgWtIn {
        from { opacity: 0; transform: translateY(8px); }
        to   { opacity: 1; transform: none; }
    }

    .pg-wt__lead {
        margin-block-end: 16px;
        font-size: .78rem;
        line-height: 1.6;
        color: var(--pg-muted);
    }

    .pg-page .pg-wt__timeline { padding: 0; }

    .pg-wt__item {
        position: relative;
        padding-inline-start: 28px;
        padding-block-end: 18px;
    }

    /* timeline dot */
    .pg-wt__item::before {
        content: "";
        position: absolute;
        inset-inline-start: 0;
        inset-block-start: 3px;
        width: 14px;
        height: 14px;
        border: 3px solid var(--tone);
        border-radius: 50%;
        background: #fff;
        box-sizing: border-box;
    }

    /* timeline line */
    .pg-wt__item::after {
        content: "";
        position: absolute;
        inset-inline-start: 6px;
        inset-block-start: 20px;
        inset-block-end: 2px;
        width: 2px;
        background: var(--tone-line);
    }

    .pg-wt__item:last-child::after { display: none; }

    .pg-wt__time {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 3px 10px;
        border-radius: 999px;
        background: var(--tone-bg);
        border: 1px solid var(--tone-line);
        font-size: .72rem;
        font-weight: 700;
        color: var(--tone);
    }

    .pg-wt__time i { font-size: .7rem; }

    .pg-wt__title {
        margin-block-start: 7px;
        font-size: .88rem;
        font-weight: 600;
        line-height: 1.5;
        color: var(--pg-ink);
    }

    @media (prefers-reduced-motion: reduce) {
        .pg-wt__tab, .pg-wt__ico { transition: none; }
        .pg-wt__tab:hover { transform: none; }
        .pg-wt__panel:not([hidden]) { animation: none; }
    }

    .pg-note {
        display: flex;
        gap: 10px;
        align-items: flex-start;
        margin-block-start: 26px;
        font-size: .8rem;
        line-height: 1.6;
        color: var(--pg-muted);
    }

    .pg-note i { margin-block-start: .25em; color: var(--pg-brand); }

    /* ---------- 4. Closing band ---------- */
    .pg-cta {
        display: flex;
        align-items: center;
        min-height: 300px;
        padding-block: 70px;
        color: #fff;
        isolation: isolate;
    }

    .pg-cta__bg {
        position: absolute;
        inset: 0;
        z-index: -2;
        width: 100%;
        height: 100%;
        object-fit: cover;
        object-position: center 40%;
    }

    .pg-cta::after {
        content: "";
        position: absolute;
        inset: 0;
        z-index: -1;
        background: linear-gradient(90deg,
            rgba(29, 15, 125, .94) 0%,
            rgba(39, 24, 157, .82) 50%,
            rgba(25, 17, 112, .45) 100%);
    }

    [dir="rtl"] .pg-cta::after { transform: scaleX(-1); }

    .pg-cta__title {
        max-width: 18em;
        margin-block-end: 26px;
        font-size: clamp(1.4rem, 2.6vw, 2rem);
        font-weight: 500;
        line-height: 1.3;
        color: #fff;
    }

    .pg-cta__facts {
        display: flex;
        flex-wrap: wrap;
        gap: 14px 36px;
        margin-block-end: 30px;
    }

    .pg-cta__facts li {
        display: flex;
        align-items: center;
        gap: 12px;
        font-size: .9rem;
        color: #fff;
    }

    .pg-cta__facts i { font-size: 1.2rem; opacity: .9; }

    .pg-cta__btn {
        display: inline-flex;
        padding: 14px 40px;
        border-radius: 999px;
        background: #fff;
        font-size: .8rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .02em;
        color: var(--pg-ink);
        text-decoration: none;
        transition: transform .2s, box-shadow .2s;
    }

    .pg-cta__btn:hover,
    .pg-cta__btn:focus-visible {
        transform: translateY(-2px);
        box-shadow: 0 14px 26px -14px rgba(0, 0, 0, .6);
        color: var(--pg-ink);
    }

    .pg-cta__actions {
        display: flex;
        flex-wrap: wrap;
        gap: 14px;
    }

    .pg-cta__btn--download {
        gap: 10px;
        background: transparent;
        border: 2px solid rgba(255, 255, 255, .85);
        color: #fff;
    }

    .pg-cta__btn--download:hover,
    .pg-cta__btn--download:focus-visible { color: #fff; }

    /* ---------- Responsive ---------- */
    @media (max-width: 991.98px) {
        .pg-tracks { grid-template-columns: minmax(0, 1fr); }
        .pg-days { grid-template-columns: minmax(0, 1fr); }
    }

    @media (max-width: 575.98px) {
        .pg-list { padding-inline: 16px; }

        .pg-qr { flex-direction: column; align-items: center; margin-inline: 16px; text-align: center; }
        .pg-qr__code { width: 160px; }

        .pg-row { grid-template-columns: 30px minmax(0, 1fr); row-gap: 4px; }
        .pg-time { grid-column: 1 / -1; min-width: 0; justify-self: start; padding-inline: 12px; }

        .pg-wt__list { gap: 6px; }
        .pg-wt__tab { padding-inline: 4px; font-size: .66rem; }
        .pg-wt__panel { padding-inline: 12px; }
    }
</style>

<script>
    (function () {
        document.querySelectorAll('[data-pg-tabs]').forEach(function (root) {
            var tabs = Array.prototype.slice.call(root.querySelectorAll('[role="tab"]'));
            var rtl = getComputedStyle(root).direction === 'rtl';

            function activate(tab, focus) {
                tabs.forEach(function (t) {
                    var on = t === tab;
                    t.setAttribute('aria-selected', on ? 'true' : 'false');
                    t.tabIndex = on ? 0 : -1;
                    var panel = document.getElementById(t.getAttribute('aria-controls'));
                    if (panel) { panel.hidden = !on; }
                });
                if (focus) { tab.focus(); }
            }

            tabs.forEach(function (tab, i) {
                tab.addEventListener('click', function () { activate(tab, false); });

                tab.addEventListener('keydown', function (e) {
                    var next = null;
                    var fwd = rtl ? 'ArrowLeft' : 'ArrowRight';
                    var back = rtl ? 'ArrowRight' : 'ArrowLeft';

                    if (e.key === fwd)       { next = tabs[(i + 1) % tabs.length]; }
                    else if (e.key === back) { next = tabs[(i - 1 + tabs.length) % tabs.length]; }
                    else if (e.key === 'Home') { next = tabs[0]; }
                    else if (e.key === 'End')  { next = tabs[tabs.length - 1]; }

                    if (next) { e.preventDefault(); activate(next, true); }
                });
            });
        });
    })();
</script>

@endsection