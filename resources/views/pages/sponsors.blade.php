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
    ['name' => 'CIH Bank',            'tier' => 'platinum', 'logo' => 'assets/images/logo/cih-logo.png',                      'url' => 'https://cihbank.ma/'],

    ['name' => 'Banque Populaire',    'tier' => 'gold',     'logo' => 'assets/parteners/banque-populaire-logo.png',           'url' => 'https://www.groupebcp.com'],
    ['name' => 'OCP Group',           'tier' => 'gold',     'logo' => 'assets/parteners/OCP_Group.svg',                       'url' => 'https://www.ocpgroup.ma/en'],
    ['name' => 'IFACI Certification', 'tier' => 'gold',     'logo' => 'assets/parteners/Logo_IFACI-Certification_alixds.png', 'url' => 'https://www.ifaci.com/'],
    ['name' => 'MEGA',                'tier' => 'gold',     'logo' => 'assets/parteners/images.png',                          'url' => 'https://www.mega.com'],

    ['name' => 'Holmarcom',           'tier' => 'silver',   'logo' => 'assets/parteners/Logo-holmarcom-1.png',                'url' => 'https://www.holmarcom.ma/'],
    ['name' => 'SOFAC',               'tier' => 'silver',   'logo' => 'assets/parteners/Sofac-logo.png',                      'url' => 'https://www.sofac.ma'],
    ['name' => 'Caseware',            'tier' => 'silver',   'logo' => 'assets/parteners/caseware.svg',                        'url' => 'https://www.caseware.com'],
    ['name' => 'Wolters Kluwer',      'tier' => 'silver',   'logo' => 'assets/parteners/Wolters_Kluwer_Logo.png',             'url' => 'https://www.wolterskluwer.com'],
    ['name' => 'TeamMate',            'tier' => 'silver',   'logo' => 'assets/parteners/TeamMate-Updated-Logo.png',           'url' => 'https://www.teammatesolutions.com'],
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

                                <a href="#sponsor-form" data-formula="{{ $formula['key'] }}" class="sp-package__cta">
                                    <span>{{ $ctaLabel }}</span>
                                    <i class="fas fa-chevron-right" aria-hidden="true"></i>
                                </a>
                            </div>
                        </article>
                    @endforeach
                </div>

              <div class="sp-hesitate">
    <div>
        <h3 class="sp-hesitate__title">@lang('sponsoring.hesitate.title')</h3>
        <p class="sp-hesitate__text">@lang('sponsoring.hesitate.text')</p>
    </div>
    <a href="#sponsor-form" class="sp-btn sp-btn--solid">
        <i class="fas fa-comments" aria-hidden="true"></i>
        <span>@lang('sponsoring.hesitate.button')</span>
    </a>
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
@php
    $tierLabels = [
        'platinum' => __('sponsoring.tier_platinum'),
        'gold'     => __('sponsoring.tier_gold'),
        'silver'   => __('sponsoring.tier_silver'),
    ];
    $allyGroups = collect($allies)->groupBy('tier');
@endphp

<section class="sp-allies" aria-labelledby="sponsoring-allies-title">
    <div class="container">
        <header class="sp-head">
            <h2 id="sponsoring-allies-title" class="sp-title">@lang('sponsoring.allies.eyebrow')</h2>
        </header>

        @foreach ($tierLabels as $tier => $label)
            @continue(! $allyGroups->has($tier))

            <div class="sp-tier sp-tier--{{ $tier }}">
                <span class="sp-pill sp-pill--{{ $tier }}">{{ $label }}</span>

                <ul class="sp-tier__row" role="list">
                    @foreach ($allyGroups[$tier] as $ally)
                        <li class="sp-tier__card">
                            @if (! empty($ally['url']))
                                <a href="{{ $ally['url'] }}" target="_blank" rel="noopener noreferrer"
                                   class="sp-tier__link" aria-label="{{ $ally['name'] }}">
                                    <img src="{{ asset($ally['logo']) }}" alt="{{ $ally['name'] }}" loading="lazy">
                                </a>
                            @else
                                <span class="sp-tier__link">
                                    <img src="{{ asset($ally['logo']) }}" alt="{{ $ally['name'] }}" loading="lazy">
                                </span>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </div>
        @endforeach
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
    
{{-- ================= FORMULAIRE DE PARTENARIAT ================= --}}
@php
    $selectedFormula = old('formula', request('formule', 'platinum'));
    $openForm = session('sponsor_sent') || $errors->hasAny(['company', 'name', 'email', 'phone', 'formula', 'consent']);
@endphp

<div class="sp-modal" id="sponsor-form" data-sp-modal @if (! $openForm) hidden @endif>
    <div class="sp-modal__overlay" data-sp-close></div>

    <div class="sp-modal__dialog" role="dialog" aria-modal="true" aria-label="@lang('sponsoring.form.title')">
        <button type="button" class="sp-modal__close" data-sp-close aria-label="@lang('action.close')">
            <i class="fas fa-times" aria-hidden="true"></i>
        </button>

        <div class="sp-modal__scroll">
            <div class="sp-form__banner">
            <p class="sp-form__kicker">@lang('sponsoring.form.kicker')</p>
            <h2 class="sp-form__banner-title">{{ $accent(__('sponsoring.form.banner_title'), 2) }}</h2>
            <p class="sp-form__banner-text">
                @lang('sponsoring.form.banner_text', ['year' => $year, 'dates' => $edition->dateLine($locale), 'venue' => $edition->venueLine($locale)])
            </p>
        </div>

        <div class="sp-form__card">
            @if (session('sponsor_sent'))
                <div class="sp-form__success" role="status">
                    <i class="fas fa-circle-check" aria-hidden="true"></i>
                    <p>@lang('sponsoring.form.success')</p>
                </div>
            @else
                <span class="sp-form__bar" aria-hidden="true"></span>
                <h2 id="sponsor-form-title" class="sp-form__title">{{ $accent(__('sponsoring.form.title'), 1) }}</h2>
                <p class="sp-form__lede">@lang('sponsoring.form.lede')</p>

                <form method="POST" action="{{ route('sponsoring.request') }}" class="sp-form__form" novalidate>
                    @csrf

                    {{-- Honeypot --}}
                    <div class="sp-form__trap" aria-hidden="true">
                        <input type="text" name="website" tabindex="-1" autocomplete="off">
                    </div>

                    <div class="sp-field sp-field--full">
                        <label for="sf-company"><i class="far fa-building" aria-hidden="true"></i> @lang('sponsoring.form.company') *</label>
                        <input id="sf-company" name="company" type="text" value="{{ old('company') }}" placeholder="@lang('sponsoring.form.company_ph')" required>
                        @error('company')<p class="sp-field__error">{{ $message }}</p>@enderror
                    </div>

                    <div class="sp-field">
                        <label for="sf-name"><i class="far fa-user" aria-hidden="true"></i> @lang('sponsoring.form.name') *</label>
                        <input id="sf-name" name="name" type="text" value="{{ old('name') }}" placeholder="@lang('sponsoring.form.name_ph')" autocomplete="name" required>
                        @error('name')<p class="sp-field__error">{{ $message }}</p>@enderror
                    </div>

                    <div class="sp-field">
                        <label for="sf-email"><i class="far fa-envelope" aria-hidden="true"></i> @lang('sponsoring.form.email') *</label>
                        <input id="sf-email" name="email" type="email" dir="ltr" value="{{ old('email') }}" placeholder="nom@entreprise.com" autocomplete="email" required>
                        @error('email')<p class="sp-field__error">{{ $message }}</p>@enderror
                    </div>

                    <div class="sp-field">
                        <label for="sf-phone"><span class="sp-field__glyph" aria-hidden="true"><svg viewBox="0 0 20 20" width="1em" height="1em" fill="currentColor" focusable="false"><path d="M3.5 2A1.5 1.5 0 0 0 2 3.5V5c0 1.149.15 2.263.43 3.326a13.022 13.022 0 0 0 9.244 9.244c1.063.28 2.177.43 3.326.43h1.5a1.5 1.5 0 0 0 1.5-1.5v-1.148a1.5 1.5 0 0 0-1.175-1.465l-3.223-.716a1.5 1.5 0 0 0-1.767 1.052l-.267.933c-.117.41-.555.643-.95.48a11.542 11.542 0 0 1-6.254-6.254c-.163-.395.07-.833.48-.95l.933-.267a1.5 1.5 0 0 0 1.052-1.767l-.716-3.223A1.5 1.5 0 0 0 4.648 2H3.5Zm13 2.56l-3.22 3.22a.75.75 0 1 1-1.06-1.06l3.22-3.22h-2.69a.75.75 0 0 1 0-1.5h4.5a.75.75 0 0 1 .75.75v4.5a.75.75 0 0 1-1.5 0V4.56Z"/></svg></span> @lang('sponsoring.form.phone') *</label>
                        <input id="sf-phone" name="phone" type="tel" dir="ltr" value="{{ old('phone') }}" placeholder="+212..." autocomplete="tel" required>
                        @error('phone')<p class="sp-field__error">{{ $message }}</p>@enderror
                    </div>

                    <div class="sp-field">
                        <label for="sf-formula"><i class="far fa-star" aria-hidden="true"></i> @lang('sponsoring.form.formula') *</label>
                        <select id="sf-formula" name="formula" required>
                            @foreach ($formulas as $formula)
                                <option value="{{ $formula['key'] }}" @selected($selectedFormula === $formula['key'])>{{ $formula['name'] }}</option>
                            @endforeach
                        </select>
                        @error('formula')<p class="sp-field__error">{{ $message }}</p>@enderror
                    </div>

                    <div class="sp-field sp-field--full">
                        <label for="sf-message"><i class="far fa-comment-dots" aria-hidden="true"></i> @lang('sponsoring.form.message')</label>
                        <textarea id="sf-message" name="message" rows="4" placeholder="@lang('sponsoring.form.message_ph')">{{ old('message') }}</textarea>
                        @error('message')<p class="sp-field__error">{{ $message }}</p>@enderror
                    </div>

                    <div class="sp-field sp-field--full">
                        <label class="sp-check">
                            <input type="checkbox" name="consent" value="1" @checked(old('consent')) required>
                            <span>@lang('sponsoring.form.consent') *</span>
                        </label>
                        @error('consent')<p class="sp-field__error">{{ $message }}</p>@enderror
                    </div>

                    <div class="sp-field--full sp-form__submit">
                        <button type="submit" class="sp-form__btn">@lang('sponsoring.form.submit')</button>
                    </div>
                </form>
            @endif
        </div>
        </div>
    </div>
</div>

<script>
    (function () {
        var modal = document.getElementById('sponsor-form');
        if (!modal) { return; }

        var select = document.getElementById('sf-formula');
        var lastFocus = null;

        function open(formula) {
            if (formula && select) { select.value = formula; }
            lastFocus = document.activeElement;
            modal.hidden = false;
            document.body.classList.add('sp-modal-open');

            var first = document.getElementById('sf-company');
            if (first) { window.setTimeout(function () { first.focus(); }, 40); }
        }

        function close() {
            modal.hidden = true;
            document.body.classList.remove('sp-modal-open');
            if (lastFocus && lastFocus.focus) { lastFocus.focus(); }
        }

        // « Devenir partenaire » (sous une formule) et « Nous contacter » ouvrent la modale.
        document.querySelectorAll('[data-formula], a[href="#sponsor-form"]').forEach(function (link) {
            link.addEventListener('click', function (e) {
                e.preventDefault();
                open(link.getAttribute('data-formula'));
            });
        });

        modal.querySelectorAll('[data-sp-close]').forEach(function (el) {
            el.addEventListener('click', close);
        });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && !modal.hidden) { close(); }
        });

        // Ouverture directe si on arrive avec le hash, ou s'il y a une erreur / une confirmation.
        if (location.hash === '#sponsor-form') { open(null); }
        if (!modal.hidden) { document.body.classList.add('sp-modal-open'); }
    })();
</script>

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
        /* ---------- 5. Previous partners (tiered) ---------- */
.sp-allies {
    padding: 72px 0 80px;
   background-image: url('/assets/parteners/slide0.png');
     background-image: url('/assets/parteners/slide0.png');
    background-size: cover;
    background-position: center;
    background-repeat: no-repeat;
    padding-block: clamp(2.5rem, 5vw, 4rem);
}
.sp-allies .sp-head { margin-block-end: 2.25rem; }
.sp-allies .sp-title { text-transform: uppercase; letter-spacing: .02em; }

.sp-tier { align-items: center; display: flex; flex-direction: column; gap: 1.4rem; margin-block-end: 2.25rem; }

.sp-pill {
    border-radius: 999px;
    box-shadow: 0 4px 12px rgba(30, 20, 120, .18);
    color: #fff;
    font-size: .72rem;
    font-weight: 700;
    letter-spacing: .22em;
    padding: .4rem 1.4rem;
    text-transform: uppercase;
}
.sp-pill--platinum { background: linear-gradient(90deg, #2a1a8f, #6a4df0); }
.sp-pill--gold     { background: linear-gradient(90deg, #a87420, #e8c26a 50%, #a87420); }
.sp-pill--silver   { background: linear-gradient(90deg, #7b7b8c, #c4c4d0 50%, #7b7b8c); }
[dir="rtl"] .sp-pill, [dir="rtl"] .sp-allies .sp-title { letter-spacing: 0; }

.sp-tier__row {
    display: flex;
    flex-wrap: wrap;
    gap: .9rem;
    justify-content: center;
    margin: 0;
    max-width: 1100px;
    padding: 0;
    width: 100%;
}

.sp-tier__card {
    background: #fff;
    border: 1px solid #e4e4ef;
    border-radius: 16px;
    box-shadow: 0 6px 18px rgba(60, 50, 140, .07);
    display: flex;
    transition: transform .2s ease, box-shadow .2s ease;
}
.sp-tier__card:hover { box-shadow: 0 10px 24px rgba(60, 50, 140, .13); transform: translateY(-2px); }

.sp-tier__link { align-items: center; display: flex; flex: 1; justify-content: center; padding: 1rem 1.25rem; }
.sp-tier__link img { display: block; max-height: 100%; max-width: 100%; object-fit: contain; }

/* Platinum: one wide card */
.sp-tier--platinum .sp-tier__card { height: 96px; width: min(480px, 100%); }
.sp-tier--platinum .sp-tier__link img { max-height: 56px; }

/* Gold: four across */
.sp-tier--gold .sp-tier__card { flex: 1 1 220px; height: 98px; max-width: 300px; }
.sp-tier--gold .sp-tier__link img { max-height: 62px; }

/* Silver: five across */
.sp-tier--silver .sp-tier__card { flex: 1 1 170px; height: 92px; max-width: 230px; }
.sp-tier--silver .sp-tier__link img { max-height: 82px; }

@media (max-width: 640px) {
    .sp-tier--gold .sp-tier__card,
    .sp-tier--silver .sp-tier__card { flex-basis: calc(50% - .45rem); max-width: none; }
}
/* ---------- Bandeau « Vous hésitez ? » ---------- */
.sp-hesitate {
    align-items: center;
    background: rgb(255 255 255 / 80%);
    border: 1px solid #ddd9f6;
    border-radius: 12px;
    display: flex;
    flex-wrap: wrap;
    gap: 1rem 2rem;
    justify-content: space-between;
    margin-block-start: 2rem;
    padding: 1.25rem 1.5rem;
}
.sp-hesitate__title { color: var(--sp-ink); font-size: 1.05rem; font-weight: 800; margin-block-end: .25rem; }
.sp-hesitate__text  { color: var(--sp-muted); font-size: .85rem; line-height: 1.5; margin: 0; max-width: 42rem; }

/* ---------- Formulaire ---------- */
.sp-form {
    background:
        radial-gradient(50% 40% at 100% 0%, rgba(124, 108, 255, .14), transparent 70%),
        #f9f9fe;
    padding-block: clamp(2.5rem, 5vw, 4rem);
    scroll-margin-top: 90px; /* hauteur de votre header fixe */
}
.sp-form .container { max-width: 980px; }

.sp-form__banner {
    background-image: url('/assets/images/images.png');
    background-size: cover;
    background-position: center;
    background-repeat: no-repeat;

    border-radius: 18px 18px 0 0;
    color: #fff;
    padding: clamp(1.5rem, 8vw, 3.25rem) clamp(1.25rem, 4vw, 2.5rem) ;
}
.sp-form__kicker { font-size: .75rem; font-weight: 600; letter-spacing: .16em; margin-block-end: .5rem; opacity: .9; text-transform: uppercase; }
.sp-form__banner-title { color: #fff; font-size: clamp(1.5rem, 1.1rem + 1.6vw, 2.1rem); font-weight: 800; line-height: 1.2; margin-block-end: .75rem; }
.sp-form__banner-title .sp-accent { color: #9d8cff; }
.sp-form__banner-text { color: rgb(255 255 255 / 90%); font-size: .95rem; line-height: 1.6; margin: 0; max-width: 40rem; }
[dir="rtl"] .sp-form__kicker { letter-spacing: .02em; }

.sp-form__card {
    background: #fff;
    border-radius: 0 0 18px 18px;
    box-shadow: 0 18px 40px -22px rgba(42, 31, 110, .35);
    padding: clamp(1.5rem, 3vw, 2.25rem) clamp(1.25rem, 4vw, 2.5rem) 2.25rem;
}
.sp-form__bar { background: linear-gradient(90deg, #2f27b8, #6c3fe6); border-radius: 2px; display: block; height: 3px; margin-block-end: 1rem; width: 4.5rem; }
.sp-form__title { color: var(--sp-ink); font-size: clamp(1.6rem, 1.2rem + 1.6vw, 2.2rem); font-weight: 800; margin-block-end: .4rem; }
.sp-form__lede  { color: var(--sp-muted); margin-block-end: 1.5rem; }

.sp-form__form { display: grid; gap: 1.1rem 1.5rem; grid-template-columns: repeat(2, minmax(0, 1fr)); }
.sp-field--full { grid-column: 1 / -1; }
@media (max-width: 640px) { .sp-form__form { grid-template-columns: 1fr; } }

.sp-field label { align-items: center; color: var(--sp-ink); display: flex; font-size: .9rem; font-weight: 700; gap: .6rem; margin-block-end: .45rem; }
.sp-field label i { color: var(--sp-brand); font-size: 1.15rem; width: 1.4rem; text-align: center; }
.sp-field label .sp-field__glyph { color: var(--sp-brand); display: inline-flex; flex: 0 0 auto; font-size: 1.15rem; justify-content: center; width: 1.4rem; }
.sp-field label .sp-field__glyph svg { height: 1em; width: 1em; }

.sp-field input[type="text"],
.sp-field input[type="email"],
.sp-field input[type="tel"],
.sp-field select,
.sp-field textarea {
    background: #fff;
    border: 1px solid #d3d9f4;
    border-radius: 999px;
    color: var(--sp-ink);
    font: inherit;
    padding: .85rem 1.25rem;
    transition: border-color .2s, box-shadow .2s;
    width: 100%;
}
.sp-field textarea { border-radius: 20px; min-height: 7.5rem; resize: vertical; }
.sp-field input::placeholder, .sp-field textarea::placeholder { color: #8b8fc0; }
.sp-field input:focus, .sp-field select:focus, .sp-field textarea:focus {
    border-color: var(--sp-accent);
    box-shadow: 0 0 0 3px rgb(108 63 230 / 18%);
    outline: none;
}
.sp-field__error { color: #c0262d; font-size: .8rem; margin: .35rem 0 0 1rem; }

.sp-check { cursor: pointer; font-weight: 500 !important; gap: .75rem !important; }
.sp-check input { accent-color: var(--sp-brand); flex: 0 0 auto; height: 1.2rem; width: 1.2rem; }
.sp-check span { color: var(--sp-muted); font-size: .85rem; }

.sp-form__trap { height: 0; left: -9999px; overflow: hidden; position: absolute; }

.sp-form__submit { display: flex; justify-content: center; margin-block-start: .75rem; }
.sp-form__btn {
    background: linear-gradient(135deg, #2f27b8 0%, #231a9a 100%);
    border: 0;
    border-radius: 999px;
    box-shadow: 0 12px 24px -14px rgba(35, 26, 154, .9);
    color: #fff;
    cursor: pointer;
    font-size: 1.05rem;
    font-weight: 600;
    padding: .95rem 3.5rem;
    transition: transform .2s, filter .2s;
}
.sp-form__btn:hover { filter: brightness(1.1); transform: translateY(-2px); }
.sp-form__btn:focus-visible { outline: 3px solid rgb(115 132 255 / 55%); outline-offset: 2px; }

.sp-form__success { align-items: center; display: flex; flex-direction: column; gap: .75rem; padding: 2rem 1rem; text-align: center; }
.sp-form__success i { color: #2e9b5f; font-size: 2.5rem; }
.sp-form__success p { color: var(--sp-ink); font-size: 1.1rem; font-weight: 600; margin: 0; }

/* ---------- Modale « Devenir partenaire » ---------- */
body.sp-modal-open { overflow: hidden; }

.sp-modal {
    --sp-ink: #14106a;
    --sp-brand: #2f27b8;
    --sp-accent: #6c3fe6;
    --sp-muted: #4a4a8c;
    --sp-tint: #ece8fe;
    --sp-line: #d3d9f4;
    --sp-gold: #b9822a;

    align-items: center;
    display: flex;
    inset: 0;
    justify-content: center;
    padding: clamp(.75rem, 3vw, 2.5rem);
    position: fixed;
    z-index: 2000;
}
.sp-modal[hidden] { display: none; }

.sp-modal__overlay {
    -webkit-backdrop-filter: blur(3px);
    backdrop-filter: blur(3px);
    background: rgb(20 16 66 / 62%);
    inset: 0;
    position: fixed;
}

.sp-modal__dialog {
    animation: sp-modal-pop .24s ease both;
    background: #fff;
    border-radius: 18px;
    box-shadow: 0 30px 70px -30px rgba(20, 16, 66, .6);
    display: flex;
    max-height: min(92vh, 940px);
    overflow: hidden;
    position: relative;
    width: min(980px, 100%);
    z-index: 1;
}
.sp-modal__scroll { overflow-y: auto; overscroll-behavior: contain; width: 100%; }
.sp-modal__scroll > .sp-form__banner { border-radius: 0; }
.sp-modal__scroll > .sp-form__card   { border-radius: 0; box-shadow: none; }

.sp-modal__close {
    align-items: center;
    background: rgb(255 255 255 / 92%);
    border: 0;
    border-radius: 50%;
    box-shadow: 0 6px 16px -6px rgba(0, 0, 0, .4);
    color: var(--sp-ink);
    cursor: pointer;
    display: inline-flex;
    font-size: 1.1rem;
    height: 2.5rem;
    inset-block-start: .85rem;
    inset-inline-end: .85rem;
    justify-content: center;
    position: absolute;
    transition: background-color .2s, transform .2s;
    width: 2.5rem;
    z-index: 3;
}
.sp-modal__close:hover { background: #fff; transform: rotate(90deg); }
.sp-modal__close:focus-visible { outline: 3px solid rgb(115 132 255 / 55%); outline-offset: 2px; }

@keyframes sp-modal-pop { from { opacity: 0; transform: translateY(14px) scale(.98); } to { opacity: 1; transform: none; } }

html { scroll-behavior: smooth; }
@media (prefers-reduced-motion: reduce) { html { scroll-behavior: auto; } .sp-form__btn { transition: none; } .sp-modal__dialog { animation: none; } .sp-modal__close { transition: none; } }
    </style>

    @endsection