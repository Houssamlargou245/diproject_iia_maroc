@extends('layouts.app')

@section('title', __('sponsoring.title'))
@section('description', __('sponsoring.meta_description', ['year' => $edition->year]))

@section('content')

@php
    $year = $edition->year;

    /* ---- Online images (Unsplash CDN). Each has a CSS fallback, so a dead link never breaks the layout.
          To use your own files, replace the URL with asset('assets/images/sponsoring/xxx.jpg'). ---- */
    $imgBanner  = 'https://images.unsplash.com/photo-1540575467063-178a50c2df87?auto=format&fit=crop&w=1800&q=80'; // conference hall, stage & audience
    $imgWhy     = 'https://images.unsplash.com/photo-1613327986042-63d4425a1a5d?auto=format&fit=crop&w=1800&q=60'; // soft purple/white abstract
    $imgOffer   = 'https://images.unsplash.com/photo-1583339522870-0d9f28cef33f?auto=format&fit=crop&w=1800&q=60'; // lavender draped textile
    $imgClosing = 'https://images.unsplash.com/photo-1551882547-ff40c63fe5fa?auto=format&fit=crop&w=1800&q=80'; // hotel, palms & pool at dusk

    /* Wraps the last $n words of a title in an accent span (works in fr / en / ar). */
    $accent = static function (string $text, int $n): \Illuminate\Support\HtmlString {
        $words = preg_split('/\s+/u', trim($text)) ?: [];
        $n = max(0, min($n, count($words) - 1));
        if ($n === 0) {
            return new \Illuminate\Support\HtmlString(e($text));
        }
        return new \Illuminate\Support\HtmlString(
            e(implode(' ', array_slice($words, 0, -$n))) .
            ' <span class="sp-accent">' . e(implode(' ', array_slice($words, -$n))) . '</span>'
        );
    };

    $reasons = [
        ['icon' => 'fa-users',       'key' => 'reach'],
        ['icon' => 'fa-bullseye',    'key' => 'profiles'],
        ['icon' => 'fa-bullseye', 'key' => 'decision'],
        ['icon' => 'fa-bullseye','key' => 'visibility'],
        ['icon' => 'fa-handshake',   'key' => 'community'],
    ];

    // The four formulas, drawn from one array so a fifth tier is a data change.
    $formulas = collect([
        ['key' => 'platinum', 'icon' => 'fa-gem',          'tone' => 'dark'],
        ['key' => 'gold',     'icon' => 'fa-star',         'tone' => 'gold'],
        ['key' => 'silver',   'icon' => 'fa-chart-column', 'tone' => 'silver'],
        ['key' => 'lab',      'icon' => 'fa-lightbulb',    'tone' => 'lab'],
    ])->map(static fn (array $formula): array => [
        ...$formula,
        ...__('sponsoring.packages.' . $formula['key']),
    ]);

    $activationIcons = ['fa-chalkboard', 'fa-utensils', 'fa-mug-hot', 'fa-id-badge', 'fa-gift', 'fa-globe'];
    $activations = (array) __('sponsoring.activations.items');
    $per = __('sponsoring.packages.per');

    // Previous partners — ONLINE logos. 'logo' (your own file/URL) wins, then the Simple Icons CDN
    // ('slug' + 'color'), then Google's favicon service as a last resort (also used if a logo fails to load).
    $favicon = fn (string $domain): string => 'https://t3.gstatic.com/faviconV2?client=SOCIAL&type=FAVICON&fallback_opts=TYPE,SIZE,URL&size=256&url=https://' . $domain;
// Previous partners: local files in public/assets/parteners/.
// 'url' is optional; without it the logo is shown but not linked.
$allies = [
  ['name' => 'CIH Bank',       'logo' => 'assets/images/logo/cih-logo.png' , 'url' => 'https://cihbank.ma/' ],
    ['name' => 'Al Omrane',         'logo' => 'assets/parteners/images.png',         'url' => 'https://mega.io'],
    ['name' => 'Banque Populaire',  'logo' => 'assets/parteners/banque-populaire-logo.png', 'url' => 'https://www.groupebcp.com'],
    ['name' => 'CaseWare',          'logo' => 'assets/parteners/Logo_IFACI-Certification_alixds.png',         'url' => 'https://www.ifaci.com/'],
    ['name' => 'Marsa Maroc',       'logo' => 'assets/parteners/Logo-holmarcom-1.png',      'url' => 'https://www.holmarcom.ma/'],
    ['name' => 'Mazars',            'logo' => 'assets/parteners/OCP_Group.svg',           'url' => 'https://www.ocpgroup.ma/en'],
    ['name' => 'Mazars',            'logo' => 'assets/parteners/Sofac-logo.png',           'url' => 'https://www.ocpgroup.ma/en'],
];
    $allyLogo = static fn (array $a, callable $fav): string => $a['logo']
        ?? (isset($a['slug']) ? 'https://cdn.simpleicons.org/' . $a['slug'] . '/' . ($a['color'] ?? '000000') : $fav($a['domain']));

    $dossierUrl = $dossier ? $dossier->downloadUrl() : route('contact');
@endphp

{{-- Shared front-office hero (its own default buttons are used: the dossier and
     "become a partner" buttons now live in the banner right below, as in the design). --}}
<x-front.hero
    :title="__('sponsoring.hero.title_lead').' '.__('sponsoring.hero.title_accent')"
    :eyebrow="__('sponsoring.hero.eyebrow')"
    :lede="__('sponsoring.hero.lede', ['year' => $year])"
    :crumbs="[__('nav.sponsors') => null]"
    :facts="[
        ['icon' => 'fa-calendar-alt', 'label' => $edition->dateLine($locale)],
        ['icon' => 'fa-map-marker-alt',  'label' => $edition->venueLine($locale)],
    ]"
    image="assets/images/bg/hero_bg1.jpg" />

<div class="sp-page">

    {{-- ================= 1. BANNER ================= --}}
    <section class="sp-intro" aria-labelledby="sponsoring-intro-title">
        <div class="sp-intro__photo" style="background-image: url('{{ asset('assets/images/Bannière-sponsoring.png') }}'); background-size: cover; background-position: center; background-repeat: no-repeat;" aria-hidden="true"></div>
        <div class="container">
            <div class="sp-intro__body">
                <p class="sp-intro__eyebrow">@lang('sponsoring.hero.eyebrow', ['year' => $year])</p>

                <h2 id="sponsoring-intro-title" class="sp-intro__title">
                    {{ $accent(__('sponsoring.hero.title_lead') . ' ' . __('sponsoring.hero.title_accent'), 4) }}
                </h2>

                <p class="sp-intro__lede">@lang('sponsoring.hero.lede', ['year' => $year])</p>

                <ul class="sp-intro__facts">
                    <!-- <li>
                        <span class="sp-intro__icon" aria-hidden="true"><i class="fas fa-calendar-days"></i></span>
                        <strong>{{ $edition->dateLine($locale) }}</strong>
                    </li>
                    <li>
                        <span class="sp-intro__icon" aria-hidden="true"><i class="fas fa-location-dot"></i></span>
                        <strong>{{ $edition->venueLine($locale) }}</strong>
                    </li> -->
                </ul>

                <div class="sp-intro__actions">
                 <a href="{{ asset('ARABCIA_2026_Sponsorship.pdf') }}"
   class="sp-btn sp-btn--solid"
   download="ARABCIA_2026_Sponsorship.pdf">
    <i class="fas fa-download" aria-hidden="true"></i>
    <span>@lang('sponsoring.hero_actions.download')</span>
</a>
                    <a href="{{ route('contact') }}" class="sp-btn sp-btn--outline">
                        <span>@lang('sponsoring.hero_actions.partner')</span>
                        <i class="fas fa-chevron-right" aria-hidden="true"></i>
                    </a>
                </div>
            </div>
        </div>
    </section>

    {{-- ================= 2. WHY ================= --}}
    <section class="sp-why sp-bgimg"  aria-labelledby="sponsoring-why-title">
        <div class="container">
            <header class="sp-head">
                <p class="sp-eyebrow">@lang('sponsoring.why.eyebrow')</p>
                <h2 id="sponsoring-why-title" class="sp-title">{{ $accent(__('sponsoring.why.title'), 3) }}</h2>
            </header>

            <ul class="sp-reasons" data-ux-stagger="90">
                @foreach ($reasons as $reason)
                    <li class="sp-reason ux-reveal">
                        <span class="sp-reason__icon" aria-hidden="true"><i class="fas {{ $reason['icon'] }}"></i></span>
                        <h3 class="sp-reason__title">@lang('sponsoring.why.' . $reason['key'] . '.title')</h3>
                        <p class="sp-reason__text">@lang('sponsoring.why.' . $reason['key'] . '.text')</p>
                    </li>
                @endforeach
            </ul>
        </div>
    </section>

    {{-- ================= 3. OFFER: formulas + à-la-carte + bespoke ================= --}}
    <section class="sp-offer sp-bgimg" style="--sp-bg:url('{{ $imgOffer }}')" aria-labelledby="sponsoring-packages-title">
        <div class="container">

            {{-- 3a. The four formulas --}}
            <header class="sp-head">

                <h2 id="sponsoring-packages-title" class="sp-title">@lang('sponsoring.packages.eyebrow')</h2>
            </header>

            <div class="sp-package-grid" data-ux-stagger="90" style="margin-top: 5%;">
                @foreach ($formulas as $formula)
                    @php
                        // "100 000 MAD" -> figure "100 000" + currency "MAD" (then "HT")
                        $figure = $formula['price']; $currency = '';
                        if (preg_match('/^([\d\s\x{00A0}\x{202F},.]+?)\s*(\D.*)$/u', $formula['price'], $m)) {
                            $figure = trim($m[1]); $currency = trim($m[2]);
                        }

                        // "Maximum 2 partenaires" -> "MAX. 2 PARTENAIRES" (falls back to the raw text when no digit)
                        $badge = preg_match('/\d+/', $formula['limit'], $n)
                            ? __('sponsoring_ui.' . ($formula['key'] === 'lab' ? 'max_sessions' : 'max_partners'), ['n' => $n[0]])
                            : $formula['limit'];

                        $ctaLabel = $formula['key'] === 'lab' ? $formula['cta'] : __('sponsoring_ui.cta_partner');
                    @endphp

                    <article class="sp-package sp-package--{{ $formula['tone'] }} ux-reveal">
                        <header class="sp-package__head">
                            <span class="sp-package__icon" aria-hidden="true"><i class="fas {{ $formula['icon'] }}"></i></span>
                            <h3 class="sp-package__name">{{ $formula['name'] }}</h3>
                            <span class="sp-package__badge">{{ $badge }}</span>
                        </header>

                        <div class="sp-package__body">
                            <p class="sp-package__price">
                                <span class="sp-package__amount" dir="ltr">{{ $figure }}</span>
                                <span class="sp-package__amount">{{ $currency }} {{ $per }}</span>
                            </p>

                            <p class="sp-package__summary">{{ $formula['summary'] }}</p>

                            <ul class="sp-package__features">
                                @foreach ($formula['features'] as $feature)
                                    <li>{{ $feature }}</li>
                                @endforeach
                            </ul>

                            <a href="{{ route('contact') }}" class="sp-package__cta">
                                <span>{{ $ctaLabel }}</span>
                                <i class="fas fa-chevron-right" aria-hidden="true"></i>
                            </a>
                        </div>
                    </article>
                @endforeach
            </div>

            <div class="sp-offer__foot">
                <!-- <a href="{{ asset('assets/images/conference/sponsorship-packages-overview.png') }}"
                    class="sp-compare"
                    data-lightbox="sponsoring-packages"
                    data-title="@lang('sponsoring.plate_packages')"
                    target="_blank" rel="noopener">
                    <i class="fas fa-scale-balanced" aria-hidden="true"></i>
                    <span>@lang('sponsoring.packages.compare')</span>
                    <i class="fas fa-chevron-right" aria-hidden="true"></i>
                </a> -->
            </div>

            {{-- 3b. À-la-carte activations --}}
            <div class="sp-head">
                 <h2 class="sp-title sp-title--sub" style="margin-bottom: 50px !important;" id="sponsoring-activations-title">@lang('sponsoring.activations.title')</h2>
            </div>

            <div class="sp-activation-grid" data-ux-stagger="70" aria-labelledby="sponsoring-activations-title">
                @foreach ($activations as $i => $activation)
                    @php
                        // "40 000 MAD / jour" -> "40 000 MAD HT / jour"
                        [$base, $suffix] = array_pad(explode(' / ', $activation['price'], 2), 2, null);
                        $priceLine = $base . ' ' . $per . ($suffix ? ' / ' . $suffix : '');
                        $many = count($activation['features']) > 1;
                    @endphp

                    <article class="sp-activation ux-reveal">
                        <span class="sp-activation__icon" aria-hidden="true"><i class="fas {{ $activationIcons[$i] ?? 'fa-star' }}"></i></span>
                        <h3 class="sp-activation__name">{{ $activation['name'] }}</h3>
                        <p class="sp-activation__price">{{ $priceLine }}</p>

                        <ul @class(['sp-activation__features', 'is-check' => $many])>
                            @foreach ($activation['features'] as $feature)
                                <li>{{ $feature }}</li>
                            @endforeach
                        </ul>

                        <!-- <a href="{{ route('contact') }}" class="sp-activation__more">@lang('sponsoring_ui.more')</a> -->
                    </article>
                @endforeach
            </div>

            {{-- 3c. Bespoke --}}
            <aside class="sp-bespoke" role="note">
                <span class="sp-bespoke__icon" aria-hidden="true"><i class="fas fa-circle-info"></i></span>
                <h3 class="sp-bespoke__title">@lang('sponsoring.bespoke.title')</h3>
                <p class="sp-bespoke__text">@lang('sponsoring.bespoke.text')</p>
            </aside>
        </div>
    </section>

    {{-- ================= 4. THIS EDITION'S SPONSORS (only when some are confirmed) ================= --}}
    @if (isset($sponsors) && count($sponsors) > 0)
        <section class="sp-roster" aria-labelledby="sponsoring-roster-title">
            <div class="container">
                <header class="sp-head">
                    <p class="sp-eyebrow">@lang('sponsoring.roster.eyebrow', ['year' => $year])</p>
                    <h2 id="sponsoring-roster-title" class="sp-title">@lang('sponsoring.roster.title', ['year' => $year])</h2>
                </header>

                <ul class="sp-wall" data-ux-stagger="70">
                    @foreach ($sponsors as $sponsor)
                        <li class="sp-plate">
                            @if ($sponsor->website_url)
                                <a href="{{ $sponsor->website_url }}" rel="noopener noreferrer sponsored" target="_blank">
                                    <x-sponsor-logo :sponsor="$sponsor" />
                                </a>
                            @else
                                <x-sponsor-logo :sponsor="$sponsor" />
                            @endif
                        </li>
                    @endforeach
                </ul>
            </div>
        </section>
    @endif

    {{-- ================= 5. PREVIOUS PARTNERS ================= --}}
    <section class="sp-allies" aria-labelledby="sponsoring-allies-title">
        <div class="container">
            <header class="sp-head">
                <h2 id="sponsoring-allies-title" class="sp-title">@lang('sponsoring.allies.eyebrow')</h2>
            </header>

           @php
               $allyItems = collect($allies)->map(static function (array $ally): array {
                   /* Banque Populaire's crest is squarer than the wordmarks, so it
                      gets a hook the stylesheet can scale up on its own. */
                   $isBanquePopulaire = str_contains(mb_strtolower($ally['name']), 'banque populaire');

                   return [
                       'src'   => asset($ally['logo']),
                       'name'  => $ally['name'],
                       'url'   => $ally['url'] ?? null,
                       'class' => $isBanquePopulaire ? 'sponsor-banque-populaire' : null,
                   ];
               })->values();
           @endphp

           <div class="sp-allies__marquee">
               <x-logo-marquee :items="$allyItems" :label="__('sponsoring.allies.eyebrow')" :speed="3200" />
           </div>
        </div>
    </section>

    {{-- ================= 6. CLOSING BAND ================= --}}
    <section class="sp-closing" aria-labelledby="sponsoring-closing-title">
        <div class="sp-closing__photo" style="background-image: url('{{ asset('assets/images/ban_presentation.webp') }}') ;background-size: cover; background-position: center; background-repeat: no-repeat;" aria-hidden="true"></div>

        <div class="container">
            <div class="sp-closing__body">
                <p class="sp-closing__eyebrow">@lang('sponsoring.cta.title')</p>
                <h2 id="sponsoring-closing-title" class="sp-closing__title">@lang('sponsoring.cta.lede', ['year' => $year])</h2>
                <p class="sp-closing__text">@lang('sponsoring.cta.text')</p>

                <a href="{{ route('contact') }}" class="sp-btn sp-btn--light">
                    <span>@lang('sponsoring.cta.button')</span>
                    <i class="fas fa-chevron-right" aria-hidden="true"></i>
                </a>
            </div>
        </div>
    </section>
</div>

{{-- Styles live INSIDE the section on purpose: anything after @endsection in a
     child view is printed before <!DOCTYPE>, which forces quirks mode. --}}
<style>
    .sp-page {
        --sp-ink: #14106a;
        --sp-brand: #2f27b8;
        --sp-accent: #6c3fe6;
        --sp-muted: #4a4a8c;
        --sp-tint: #ece8fe;
        --sp-line: #d3d9f4;
        --sp-gold: #b9822a;

        --sp-lat-white: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='88' height='88' viewBox='0 0 88 88'%3E%3Cg fill='none' stroke='%23ffffff' stroke-width='1.3'%3E%3Crect x='22' y='22' width='44' height='44'/%3E%3Crect x='22' y='22' width='44' height='44' transform='rotate(45 44 44)'/%3E%3Ccircle cx='44' cy='44' r='10'/%3E%3Cpath d='M0 0L22 22M88 0L66 22M0 88L22 66M88 88L66 66'/%3E%3C/g%3E%3C/svg%3E");
        --sp-wave: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 1440 220' preserveAspectRatio='none'%3E%3Cpath d='M0 150C240 70 470 210 760 135S1210 60 1440 120V220H0Z' fill='%23ffffff' fill-opacity='.38'/%3E%3Cpath d='M0 150C240 70 470 210 760 135S1210 60 1440 120' fill='none' stroke='%23ffffff' stroke-opacity='.9' stroke-width='2'/%3E%3Cpath d='M0 175C260 110 520 220 800 160S1230 100 1440 150' fill='none' stroke='%23ffffff' stroke-opacity='.55' stroke-width='1.5'/%3E%3C/svg%3E");

        --sp-to-start: to right;
        --sp-to-end: to left;
        color: var(--sp-ink);
    }
    [dir="rtl"] .sp-page { --sp-to-start: to left; --sp-to-end: to right; }

    .sp-page section { overflow: hidden; position: relative; }
    .sp-page ul { list-style: none; padding: 0; }
    .sp-page img { max-width: 100%; }
    [dir="rtl"] .sp-page .fa-chevron-right { transform: scaleX(-1); }
    .sp-accent { color: var(--sp-accent); }

    /* Photo background washed with lavender so it only reads as texture */
    .sp-bgimg {
        background-color: #f4f2ff;
        background-image:
            linear-gradient(180deg, rgb(250 249 255 / 92%) 0%, rgb(240 237 254 / 94%) 100%),
            var(--sp-bg, none);
        background-position: center;
        background-size: cover;
        isolation: isolate;
    }

    /* ---------- Shared headings ---------- */
    .sp-head { margin-block-end: 2.25rem; text-align: center; }

    .sp-eyebrow {
        color: var(--sp-brand);
        font-size: .72rem;
        font-weight: 800;
        letter-spacing: .2em;
        margin-top: 20px;
        margin-bottom: 20px;
        text-transform: uppercase;
    }
    .sp-eyebrow::after {
        background: linear-gradient(90deg, var(--sp-brand), #c13bd8);
        border-radius: 2px;
        content: '';
        display: block;
        height: 2px;
        margin: .4rem auto 0;
        width: 2.6rem;
    }
    [dir="rtl"] .sp-eyebrow { letter-spacing: .04em; }

    .sp-title { color: var(--sp-ink); font-size: clamp(1.6rem, 1.25rem + 1.5vw, 2.1rem); font-weight: 800; line-height: 1.2; }
    .sp-title--sub { margin: 3.25rem 0 1.75rem; text-align: center; }

    /* ---------- Buttons ---------- */
    .sp-btn {
        align-items: center;
        border: 2px solid var(--sp-brand);
        border-radius: 6px;
        display: inline-flex;
        font-size: .76rem;
        font-weight: 800;
        gap: .8rem;
        justify-content: center;
        letter-spacing: .02em;
        line-height: 1.25;
        padding: .8rem 1.4rem;
        text-decoration: none;
        text-transform: uppercase;
        transition: background-color .2s, color .2s, transform .2s, box-shadow .2s;
    }
    .sp-btn i { font-size: .8em; }
    .sp-btn--solid { background: linear-gradient(135deg, #2f27b8 0%, #231a9a 100%); color: #fff; max-width: 17rem; text-align: start; }
    .sp-btn--solid i { border-inline-end: 1px solid rgb(255 255 255 / 40%); font-size: 1.15rem; padding-inline-end: .8rem; }
    .sp-btn--solid:hover { box-shadow: 0 12px 24px -14px rgba(35, 26, 154, .9); color: #fff; transform: translateY(-2px); }
    .sp-btn--outline { background: #fff; color: var(--sp-brand); min-width: 13rem; }
    .sp-btn--outline:hover { background: var(--sp-brand); color: #fff; transform: translateY(-2px); }
    .sp-btn--light { background: #fff; border-color: #fff; color: var(--sp-ink); justify-content: space-between; min-width: 14rem; }
    .sp-btn--light:hover { box-shadow: 0 12px 26px -14px rgba(0, 0, 0, .55); color: var(--sp-ink); transform: translateY(-2px); }
    .sp-btn:focus-visible, .sp-package__cta:focus-visible, .sp-compare:focus-visible, .sp-activation__more:focus-visible {
        outline: 3px solid rgb(115 132 255 / 55%);
        outline-offset: 2px;
    }

    /* ---------- 1. Banner ---------- */
    .sp-intro {
        background:
            radial-gradient(80% 90% at 0% 0%, rgb(255 255 255 / 85%) 0%, transparent 60%),
            linear-gradient(120deg, #f4f0ff 0%, #e6e0fd 50%, #d6cdf8 100%);
        isolation: isolate;
        min-height: 22rem;
        padding-block: clamp(2.25rem, 4.5vw, 3.25rem);
    }
    .sp-intro__photo {
        -webkit-mask-image: linear-gradient(var(--sp-to-end), #000 55%, transparent 100%);
        mask-image: linear-gradient(var(--sp-to-end), #000 55%, transparent 100%);
        background-color: #8b7bd8;
        background-position: center 40%;
        background-repeat: no-repeat;
        background-size: cover;
        inset-block: 0;
        inset-inline-end: 0;
        position: absolute;
        width: min(62%, 920px);
        z-index: -1;
    }
    .sp-intro::before {
        -webkit-mask-image: linear-gradient(var(--sp-to-start), #000 0%, transparent 100%);
        mask-image: linear-gradient(var(--sp-to-start), #000 0%, transparent 100%);
        background-image: var(--sp-lat-white);
        background-size: 88px 88px;
        content: '';
        inset-block: 0;
        inset-inline-start: 0;
        position: absolute;
        width: min(16%, 240px);
        z-index: -1;
    }
    .sp-intro::after {
        background:
            var(--sp-wave) bottom / 100% 100% no-repeat,
            radial-gradient(70% 100% at 10% 120%, rgb(140 120 255 / 45%) 0%, transparent 70%);
        content: '';
        height: 38%;
        inset: auto 0 0 0;
        pointer-events: none;
        position: absolute;
        z-index: -1;
    }

    .sp-intro__body { max-width: 37rem; }
    .sp-intro__eyebrow {
        align-items: center;
        color: var(--sp-brand);
        display: flex;
        font-size: .72rem;
        font-weight: 800;
        gap: .6rem;
        letter-spacing: .16em;
        margin-block-end: .9rem;
        text-transform: uppercase;
    }
    .sp-intro__eyebrow::before { background: var(--sp-brand); border-radius: 2px; content: ''; flex: 0 0 auto; height: 3px; width: 3rem; }
    [dir="rtl"] .sp-intro__eyebrow { letter-spacing: .02em; }

    .sp-intro__title { color: var(--sp-ink); font-size: clamp(1.9rem, 1.3rem + 2.2vw, 2.7rem); font-weight: 800; line-height: 1.12; margin-block-end: 1rem; }
    [dir="rtl"] .sp-intro__title { line-height: 1.35; }
    .sp-intro__lede { color: #1d1a5e; font-size: .95rem; line-height: 1.6; margin-block-end: 1.25rem; max-width: 32rem; }

    .sp-intro__facts { align-items: center; display: flex; flex-wrap: wrap; gap: .75rem 0; margin-block-end: 1.4rem !important; }
    .sp-intro__facts li { align-items: center; color: #2c22a8; display: flex; gap: .75rem; }
    .sp-intro__facts li + li { border-inline-start: 1px solid rgb(42 31 110 / 28%); margin-inline-start: 1.4rem; padding-inline-start: 1.4rem; }
    .sp-intro__facts strong { font-size: .85rem; line-height: 1.3; max-width: 13rem; }
    .sp-intro__icon { color: #4b2fd0; font-size: 1.7rem; line-height: 1; }

    .sp-intro__actions { display: flex; flex-wrap: wrap; gap: .9rem; }

    /* ---------- 2. Why ---------- */
    .sp-why { padding-block: clamp(2.25rem, 5vw, 3.25rem); background-image: linear-gradient(180deg, rgb(255 255 255 / 95%), rgb(248 246 255 / 96%)), var(--sp-bg, none); }

    .sp-reasons { display: grid; gap: 1.75rem 0; grid-template-columns: repeat(5, minmax(0, 1fr)); }
    .sp-reason { padding-inline: 1.1rem; position: relative; text-align: center; }
    .sp-reason + .sp-reason::before { background: var(--sp-line); content: ''; inset-block: 1.2rem .8rem; inset-inline-start: 0; position: absolute; width: 1px; }
    .sp-reason__icon {
        align-items: center;
        background: #ece8fe;
        border-radius: 50%;
        color: #5b3fd9;
        display: inline-flex;
        font-size: 1.55rem;
        height: 4rem;
        justify-content: center;
        margin-block-end: .8rem;
        width: 4rem;
    }
    .sp-reason__title { color: var(--sp-ink); font-size: .95rem; font-weight: 800; line-height: 1.3; margin-block-end: .4rem; }
    .sp-reason__text { color: var(--sp-muted); font-size: .8rem; line-height: 1.55; }

    @media (max-width: 1099.98px) {
        .sp-reasons { grid-template-columns: repeat(3, minmax(0, 1fr)); }
        .sp-reason:nth-child(4)::before { display: none; }
    }
    @media (max-width: 767.98px) {
        .sp-reasons { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .sp-reason::before { display: none; }
    }
    @media (max-width: 479.98px) { .sp-reasons { grid-template-columns: 1fr; } }

    /* ---------- 3. Offer ---------- */
    .sp-offer { padding-block: clamp(2rem, 4.5vw, 3rem) clamp(2.25rem, 5vw, 3.25rem); }
    .sp-offer::before {
        -webkit-mask-image: linear-gradient(var(--sp-to-end), #000, transparent);
        mask-image: linear-gradient(var(--sp-to-end), #000, transparent);
        background-image: var(--sp-lat-white);
        background-size: 88px 88px;
        content: '';
        inset-block: 0 auto;
        height: 36rem;
        inset-inline-end: 0;
        opacity: .7;
        position: absolute;
        width: min(26%, 380px);
        z-index: -1;
    }

    .sp-package-grid { align-items: stretch; display: grid; gap: 1rem; grid-template-columns: repeat(4, minmax(0, 1fr)); }
    @media (max-width: 1099.98px) { .sp-package-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
    @media (max-width: 575.98px) { .sp-package-grid { grid-template-columns: 1fr; } }

    .sp-package {
        background: #fff;
        border-radius: 8px;
        box-shadow: 0 10px 30px rgb(42 31 110 / 12%);
        display: flex;
        flex-direction: column;
        overflow: hidden;
    }

    .sp-package__head {
        align-items: center;
        color: #fff;
        display: flex;
        gap: .8rem;
        min-height: 4.1rem;
        padding: .8rem 4.6rem .8rem 1rem;
        position: relative;
    }
    [dir="rtl"] .sp-package__head { padding: .8rem 1rem .8rem 4.6rem; }
    .sp-package--dark .sp-package__head   { background: linear-gradient(135deg, #4a35cf 0%, #3326b8 100%); }
    .sp-package--gold .sp-package__head   { background: linear-gradient(135deg, #d3a54f 0%, #b9822a 100%); }
    .sp-package--silver .sp-package__head { background: linear-gradient(135deg, #8d9bb2 0%, #6f7e97 100%); }
    .sp-package--lab .sp-package__head    { background: #d9d3fb; color: var(--sp-ink); }

    .sp-package__icon { flex: 0 0 auto; font-size: 1.9rem; line-height: 1; text-align: center; width: 2.2rem; }
    .sp-package__name { color: inherit; font-size: .8rem; font-weight: 800; letter-spacing: .02em; line-height: 1.25; text-transform: uppercase; }
    [dir="rtl"] .sp-package__name { letter-spacing: 0; }

    .sp-package__badge {
        background: rgb(255 255 255 / 20%);
        border-end-start-radius: 6px;
        font-size: .56rem;
        font-weight: 800;
        inset-block-start: 0;
        inset-inline-end: 0;
        letter-spacing: .04em;
        line-height: 1.25;
        max-width: 4.6rem;
        padding: .55rem .15rem;
        position: absolute;
        text-align: center;
        text-transform: uppercase;
    }
    [dir="rtl"] .sp-package__badge { border-end-start-radius: 0; border-end-end-radius: 6px; letter-spacing: 0; }
    .sp-package--lab .sp-package__badge { background: rgb(47 39 184 / 12%); }

    .sp-package__body { display: flex; flex: 1 1 auto; flex-direction: column; padding: 1rem 1.1rem 1.15rem; }

    .sp-package__price { align-items: baseline; color: var(--sp-ink); display: flex; flex-wrap: wrap; gap: .35rem; justify-content: center; margin-block-end: .6rem !important; }
    .sp-package__amount { font-size: 1.75rem; font-weight: 800; line-height: 1.1; }
    .sp-package__unit { font-size: .85rem; font-weight: 700; }

    .sp-package__summary { color: var(--sp-muted); font-size: .8rem; line-height: 1.5; margin-block-end: .9rem !important; text-align: center; }
    .sp-package--lab .sp-package__summary { color: #5b3fd9; }

    .sp-package__features { display: grid; gap: .4rem; margin-block-end: 1.1rem !important; }
    .sp-package__features li { color: var(--sp-muted); font-size: .76rem; line-height: 1.4; padding-inline-start: 1.5rem; position: relative; }
    .sp-package__features li::before {
        background: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%234f3cc9' stroke-width='3.5' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M4 12.5l5 5L20 6.5'/%3E%3C/svg%3E") center / contain no-repeat;
        content: '';
        height: .95rem;
        inset-block-start: .1rem;
        inset-inline-start: .2rem;
        position: absolute;
        width: .95rem;
    }

    .sp-package__cta {
        align-items: center;
        border-radius: 5px;
        color: #fff;
        display: flex;
        font-size: .74rem;
        font-weight: 800;
        gap: .6rem;
        justify-content: center;
        margin-block-start: auto;
        padding: .8rem 1rem;
        text-align: center;
        text-decoration: none;
        text-transform: uppercase;
        transition: transform .2s, box-shadow .2s, filter .2s;
    }
    .sp-package__cta i { font-size: .72em; }
    .sp-package__cta:hover { color: #fff; filter: brightness(1.08); transform: translateY(-2px); }
    .sp-package--dark .sp-package__cta,
    .sp-package--lab .sp-package__cta    { background: linear-gradient(135deg, #3a2fd0 0%, #2a1fa8 100%); }
    .sp-package--gold .sp-package__cta   { background: var(--sp-gold); }
    .sp-package--silver .sp-package__cta { background: #6f7e97; }

    .sp-offer__foot { display: flex; justify-content: center; margin-block-start: 1.4rem; }
    .sp-compare {
        align-items: center;
        background: #fff;
        border: 1.5px solid var(--sp-brand);
        border-radius: 5px;
        color: var(--sp-ink);
        display: inline-flex;
        font-size: .76rem;
        font-weight: 800;
        gap: .9rem;
        padding: .75rem 1.6rem;
        text-decoration: none;
        text-transform: uppercase;
        transition: background-color .2s, color .2s;
    }
    .sp-compare i { color: var(--sp-brand); font-size: 1.1rem; }
    .sp-compare i:last-child { font-size: .7rem; }
    .sp-compare:hover { background: var(--sp-brand); color: #fff; }
    .sp-compare:hover i { color: #fff; }

    /* ---- À la carte ---- */
    .sp-activation-grid { display: grid; gap: .8rem; grid-template-columns: repeat(6, minmax(0, 1fr)); }
    @media (max-width: 1199.98px) { .sp-activation-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); } }
    @media (max-width: 767.98px)  { .sp-activation-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
    @media (max-width: 479.98px)  { .sp-activation-grid { grid-template-columns: 1fr; } }

    .sp-activation {
        background: #fff;
        border: 1px solid #e4e1f8;
        border-radius: 6px;
        box-shadow: 0 8px 22px rgb(42 31 110 / 8%);
        display: flex;
        flex-direction: column;
        padding: 1rem .85rem .9rem;
        text-align: center;
    }
    .sp-activation__icon { color: var(--sp-brand); font-size: 1.9rem; line-height: 1; margin-block-end: .6rem; }
    .sp-activation__name { color: var(--sp-ink); font-size: .74rem; font-weight: 800; line-height: 1.25; margin-block-end: .3rem; text-transform: uppercase; }
    .sp-activation__price { color: var(--sp-accent); font-size: .78rem; font-weight: 800; line-height: 1.35; margin-block-end: .7rem !important; unicode-bidi: plaintext; }

    .sp-activation__features { display: grid; gap: .3rem; margin-block-end: .9rem !important; text-align: start; }
    .sp-activation__features li { color: var(--sp-muted); font-size: .72rem; line-height: 1.4; padding-inline-start: 1rem; position: relative; }
    .sp-activation__features li::before { background: var(--sp-brand); border-radius: 50%; content: ''; height: 5px; inset-block-start: .5em; inset-inline-start: .15rem; position: absolute; width: 5px; }
    .sp-activation__features.is-check li::before {
        background: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%234f3cc9' stroke-width='3.5' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M4 12.5l5 5L20 6.5'/%3E%3C/svg%3E") center / contain no-repeat;
        border-radius: 0;
        height: .8rem;
        inset-block-start: .15em;
        inset-inline-start: 0;
        width: .8rem;
    }

    .sp-activation__more {
        border: 1.5px solid var(--sp-brand);
        border-radius: 4px;
        color: var(--sp-brand);
        font-size: .66rem;
        font-weight: 800;
        margin-block-start: auto;
        padding: .45rem .5rem;
        text-decoration: none;
        text-transform: uppercase;
        transition: background-color .2s, color .2s;
    }
    .sp-activation__more:hover { background: var(--sp-brand); color: #fff; }

    /* ---- Bespoke ---- */
    .sp-bespoke {
        align-items: center;
        background: rgb(255 255 255 / 70%);
        border: 1px solid #ddd9f6;
        border-radius: 6px;
        display: flex;
        gap: 1rem 1.25rem;
        margin-block-start: 1rem;
        padding: .8rem 1.1rem;
    }
    .sp-bespoke__icon { color: var(--sp-accent); flex: 0 0 auto; font-size: 1.6rem; line-height: 1; }
    .sp-bespoke__title { color: var(--sp-brand); flex: 0 0 auto; font-size: .76rem; font-weight: 800; text-transform: uppercase; }
    .sp-bespoke__text { color: var(--sp-muted); flex: 1 1 auto; font-size: .76rem; line-height: 1.5; }
    @media (max-width: 767.98px) { .sp-bespoke { align-items: flex-start; flex-direction: column; } }

    /* ---------- 4. Roster (only when sponsors exist) ---------- */
    .sp-roster { background: #f6f4ff; padding-block: 3rem; }
    .sp-wall { display: grid; gap: 1.2rem; grid-template-columns: repeat(4, minmax(0, 1fr)); }
    @media (max-width: 767.98px) { .sp-wall { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
    .sp-plate { align-items: center; background: #fff; border: 1px solid var(--sp-line); border-radius: 12px; display: flex; justify-content: center; min-height: 7rem; padding: 1.2rem; transition: box-shadow .25s, transform .25s; }
    .sp-plate:hover { box-shadow: 0 14px 30px -18px rgba(60, 40, 160, .45); transform: translateY(-3px); }
    .sp-plate img { max-height: 3.6rem; max-width: 100%; object-fit: contain; }

    /* ---------- 5. Previous partners ---------- */
    .sp-allies { background: #fff; padding-block: clamp(1.75rem, 4vw, 2.5rem) clamp(2rem, 4.5vw, 2.75rem); }
    .sp-allies .sp-head { margin-block-end: 1.5rem; }
    .sp-allies .sp-title { font-size: clamp(1.25rem, 1.05rem + .9vw, 1.6rem); }

    .sp-allies__marquee { margin-block: clamp(1.5rem, 4vw, 2.25rem); }

    .sp-allies__row { align-items: center; display: flex; flex-wrap: wrap; gap: 1rem 2rem; justify-content: space-between; }
    .sp-ally { align-items: center; display: flex; flex: 1 1 5.5rem; height: 3.2rem; justify-content: center; }
    .sp-ally a { align-items: center; display: flex; height: 100%; justify-content: center; width: 100%; }
    .sp-ally img { height: auto; max-height: 6.4rem; max-width: 6.5rem; object-fit: contain; transition: transform .2s; width: auto; }
    .sp-ally a:hover img { transform: scale(1.06); }

    /* ---------- 6. Closing band ---------- */
    .sp-closing {
        background: linear-gradient(110deg, #1d0f7d 0%, #2d1fa3 60%, #3a27b3 100%);
        isolation: isolate;
        padding-block: clamp(1.75rem, 4vw, 2.5rem);
    }
    .sp-closing__photo {
        -webkit-mask-image: linear-gradient(var(--sp-to-end), #000 55%, transparent 100%);
        mask-image: linear-gradient(var(--sp-to-end), #000 55%, transparent 100%);
        background-color: #5a45c8;
        background-position: center 55%;
        background-repeat: no-repeat;
        background-size: cover;
        inset-block: 0;
        inset-inline-end: 0;
        position: absolute;
        width: min(58%, 840px);
        z-index: -1;
    }
    .sp-closing::before {
        -webkit-mask-image: linear-gradient(var(--sp-to-start), #000, transparent);
        mask-image: linear-gradient(var(--sp-to-start), #000, transparent);
        background-image: var(--sp-lat-white);
        background-size: 88px 88px;
        content: '';
        inset-block: 0;
        inset-inline-start: 0;
        opacity: .18;
        position: absolute;
        width: min(22%, 320px);
        z-index: -1;
    }
    .sp-closing__body { color: #fff; max-width: 36rem; }
    .sp-closing__eyebrow { color: rgb(255 255 255 / 85%); font-size: .68rem; font-weight: 800; letter-spacing: .16em; margin-block-end: .4rem; text-transform: uppercase; }
    [dir="rtl"] .sp-closing__eyebrow { letter-spacing: .02em; }
    .sp-closing__title { color: #fff; font-size: clamp(1.15rem, 1rem + .7vw, 1.5rem); font-weight: 800; line-height: 1.25; margin-block-end: .5rem; }
    .sp-closing__text { color: rgb(255 255 255 / 90%); font-size: .8rem; line-height: 1.55; margin-block-end: 1rem !important; }
    .sp-closing .sp-btn--light { min-width: 12.5rem; padding-block: .7rem; }

    @media (max-width: 767.98px) {
        .sp-intro__photo, .sp-closing__photo { opacity: .3; width: 100%; }
        .sp-intro::before { display: none; }
        .sp-intro__facts li + li { border: 0; margin: 0; padding: 0; }
        .sp-btn--solid { max-width: none; }
        .sp-allies__row { justify-content: center; }
    }

    @media (prefers-reduced-motion: reduce) {
        .sp-btn, .sp-package__cta, .sp-ally img, .sp-plate { transition: none; }
        .sp-btn:hover, .sp-package__cta:hover, .sp-plate:hover { transform: none; }
    }
</style>

@endsection