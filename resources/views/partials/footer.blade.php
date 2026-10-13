{{--
    Site footer.

    Layout (desktop) — matches the approved design:

      [ logo ]   [ quick links (one row)      ]   [ office map ]
                 [ phone · e-mail · map link  ]
                 [ social icons               ]
                 [ office address             ]
      ----------------------------------------------------------------
      © year organiser · rights                  privacy · legal · a11y

    Column titles are kept in the markup for accessibility but visually
    hidden. Logical properties are used throughout so RTL mirrors correctly.
--}}
@php
    $edition = $currentEdition ?? null;

    /* ---- Office address (shown in the footer and used by the map) ---- */
    $addressLines = [
        'Bd Sidi Mohamed Ben Abdellah',
        'Résidence Belle vue, Imm. B, Etage 2 Bureau 1 , Casablanca',
    ];

    /* Exact position of IIA MAROC on Google Maps (guarantees the red pin). */
    $mapLat = '33.60411083721038';
    $mapLng = '-7.64255270132866';

    /* API-key-free Google Maps embed, centred on the pin. */
    $mapEmbed = 'https://maps.google.com/maps?q='.$mapLat.','.$mapLng
        .'&z=17&hl='.app()->getLocale().'&output=embed';

    /* Link that opens the pin in Google Maps. */
    $mapLink = 'https://www.google.com/maps/search/?api=1&query='.$mapLat.','.$mapLng;
@endphp

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet"
      href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap">

<footer class="d-footer" role="contentinfo">
    <span class="d-footer__zellige" aria-hidden="true"></span>

    {{-- Wide top row ---------------------------------------------------------- --}}
    <div class="d-footer__inner">

        <div class="d-footer__grid">

            {{-- Column 1 — logo ------------------------------------------- --}}
            <div class="d-footer__brand">
                <a href="{{ route('home') }}" class="d-footer__logo" aria-label="{{ __('site.site_name') }}">
                    <img src="{{ asset('assets/brand/logo-iia-maroc.png') }}"
                         alt="{{ __('site.site_name') }}"
                         loading="lazy"
                         decoding="async">
                </a>
            </div>

            {{-- Column 2 — links, contact, social, address ---------------- --}}
            <div class="d-footer__center">

                <nav class="d-footer__links" aria-labelledby="footer-links-title">
                    <h2 id="footer-links-title" class="visually-hidden">@lang('footer.quick_links')</h2>

                    <ul class="d-footer__list d-footer__list--row">
                        @foreach ([
                            ['presentation', __('nav.presentation')],
                            ['programme', __('nav.programme')],
                            ['sponsors', __('nav.sponsors')],
                            ['pricing', __('nav.pricing')],
                            ['contact', __('nav.contact')],
                        ] as [$route, $label])
                            @continue(! Route::has($route))
                            <li><a href="{{ route($route) }}">{{ $label }}</a></li>
                        @endforeach
                    </ul>
                </nav>

                <div class="d-footer__contact">
                    <h2 class="visually-hidden">@lang('nav.contact')</h2>

                    {{-- dir="ltr" only on the value so the icon still sits at the start in Arabic --}}
                    <ul class="d-footer__list d-footer__list--row d-footer__list--icons">
                        @if ($edition?->contact_phone)
                            <li>
                                <a href="tel:{{ preg_replace('/[^0-9+]/', '', $edition->contact_phone) }}">
                                    <i class="fas fa-phone" aria-hidden="true"></i>
                                    <span dir="ltr">{{ $edition->contact_phone }}</span>
                                </a>
                            </li>
                        @endif

                        @if ($edition?->contact_email)
                            <li>
                                <a href="mailto:{{ $edition->contact_email }}">
                                    <i class="fas fa-envelope" aria-hidden="true"></i>
                                    <span dir="ltr">{{ $edition->contact_email }}</span>
                                </a>
                            </li>
                        @endif

                        {{-- Map link (disabled, uncomment to show it)
                        <li>
                            <a href="{{ $mapLink }}" rel="noopener noreferrer" target="_blank">
                                <i class="fas fa-map-marker-alt" aria-hidden="true"></i>
                                <span>@lang('contact.open_map')</span>
                            </a>
                        </li>
                        --}}
                    </ul>
                </div>

                <ul class="d-social">
                    <li>
                        <a href="https://www.linkedin.com/company/iia-maroc-amaci/?viewAsMember=true"
                           rel="noopener noreferrer" target="_blank" aria-label="LinkedIn">
                            <i class="fab fa-linkedin-in" aria-hidden="true"></i>
                        </a>
                    </li>
                    <li>
                        <a href="https://www.facebook.com/profile.php?id=61561210404784"
                           rel="noopener noreferrer" target="_blank" aria-label="Facebook">
                            <i class="fab fa-facebook-f" aria-hidden="true"></i>
                        </a>
                    </li>
                </ul>

                <address class="d-footer__address">
                    <a class="d-footer__pin" href="{{ $mapLink }}" rel="noopener noreferrer" target="_blank"
                       aria-label="@lang('contact.open_map')">
                        <i class="fas fa-map-marker-alt" aria-hidden="true"></i>
                    </a>
                    @foreach ($addressLines as $line)
                        <span dir="auto">{{ $line }}</span>
                    @endforeach
                </address>
            </div>

            {{-- Column 3 — office map ------------------------------------- --}}
            <div class="d-footer__map">
                <h2 class="visually-hidden">@lang('nav.venue')</h2>

                <iframe
                    class="d-footer__map-frame"
                    src="{{ $mapEmbed }}"
                    title="{{ implode(', ', $addressLines) }}"
                    loading="lazy"
                    referrerpolicy="no-referrer-when-downgrade"
                    allowfullscreen></iframe>

                <a class="d-footer__map-btn" href="{{ $mapLink }}" rel="noopener noreferrer" target="_blank">
                    <i class="fas fa-map-marker-alt" aria-hidden="true"></i>
                    <span>@lang('contact.open_map')</span>
                </a>
            </div>

        </div>
    </div>

    {{-- Bottom bar: own width (1275px), independent from the site's .container -- --}}
    <div class="d-footer__bottom">
        <p>
            &copy; {{ $edition?->year ?? date('Y') }}
            {{ $edition?->organiser ?? __('site.organiser') }}
            &middot; @lang('footer.rights')
        </p>

        <ul class="d-footer__legal">
            <li><a href="{{ route('contact') }}">@lang('footer.privacy')</a></li>
            <li><a href="{{ route('contact') }}">@lang('footer.legal')</a></li>
        </ul>
    </div>
</footer>

<style>
/* ==========================================================================
   ARABCIA footer — measured from the reference screenshot (2000px wide)

   x : logo 147 → 627 · centre column ~697 → 1427 · map 1497 → 1852
   y : map 157 → 372 (355 × 215) · links 157 → 201 · contact 217 → 259
       social 278 → 345 · address 377 → 421 · divider 467 · bottom text 510
   ========================================================================== */

.d-footer {
    --f-base: #261072;
    --f-blue: #7384ff;
    --f-orange: #e39374;
    --f-mauve: #2a1f6e;
    --f-muted: rgba(255, 255, 255, .78);
    --f-line: rgba(255, 255, 255, .14);
    --f-panel: rgba(110, 80, 255, .06);

    position: relative;
    isolation: isolate;
    margin: 0;
    padding-block: 156px 0;
    color: #fff;
    font-family: "Inter", "Roboto", "Segoe UI", system-ui, -apple-system, sans-serif;
    font-size: 1rem;
    line-height: 1.5;
    background: var(--f-base);
    overflow: hidden;
    -webkit-font-smoothing: antialiased;
}

/* soft glow, bottom-end corner (lighter blue on the right like the design) */
.d-footer::after {
    content: "";
    position: absolute;
    z-index: -1;
    inset-inline-end: -200px;
    bottom: -260px;
    width: 700px;
    height: 700px;
    border-radius: 50%;
    background: radial-gradient(closest-side, rgba(90, 80, 230, .30), transparent);
    pointer-events: none;
}

/* zellige line-art on the end side (full height, fades towards the centre) */
.d-footer__zellige {
    position: absolute;
    z-index: -1;
    inset-block: 0;
    inset-inline-end: 0;
    width: min(24%, 480px);
    opacity: .09;
    pointer-events: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='86' height='86' viewBox='0 0 80 80' fill='none' stroke='white' stroke-width='1'%3E%3Crect x='20' y='20' width='40' height='40'/%3E%3Crect x='20' y='20' width='40' height='40' transform='rotate(45 40 40)'/%3E%3Ccircle cx='40' cy='40' r='9'/%3E%3Cpath d='M0 0l8 8M80 0l-8 8M0 80l8-8M80 80l-8-8'/%3E%3C/svg%3E");
    background-size: 86px 86px;
    -webkit-mask-image: linear-gradient(to left, #000 15%, transparent 100%);
            mask-image: linear-gradient(to left, #000 15%, transparent 100%);
}

[dir="rtl"] .d-footer__zellige {
    -webkit-mask-image: linear-gradient(to right, #000 15%, transparent 100%);
            mask-image: linear-gradient(to right, #000 15%, transparent 100%);
}

.d-footer a {
    color: inherit;
    text-decoration: none;
    transition: color .2s ease, transform .2s ease, background .2s ease, border-color .2s ease;
}

.d-footer a:hover,
.d-footer a:focus-visible {
    color: var(--f-blue);
}

.d-footer a:focus-visible {
    outline: 2px solid var(--f-orange);
    outline-offset: 3px;
    border-radius: 8px;
}

.d-footer ul {
    list-style: none;
    padding: 0;
}

.d-footer .visually-hidden {
    position: absolute !important;
    width: 1px;
    height: 1px;
    margin: -1px;
    padding: 0;
    overflow: hidden;
    clip: rect(0, 0, 0, 0);
    white-space: nowrap;
    border: 0;
}

/* ==========================================================================
   Top row : logo | centre column | map
   ========================================================================== */
.d-footer__inner {
    width: min(1705px, 100% - 2 * clamp(20px, 7.35vw, 147px));
    margin-inline: auto;
}

.d-footer__grid {
    display: grid;
    grid-template-columns:
        clamp(200px, 24vw, 480px)        /* logo   */
        minmax(0, 1fr)                   /* centre */
        clamp(260px, 17.75vw, 355px);    /* map    */
    column-gap: clamp(32px, 3.5vw, 70px);
    align-items: start;
    padding-bottom: 46px;
}

/* ---------- Logo — vertically centred on the map (215px tall) ---------- */
.d-footer__brand {
    display: flex;
    align-items: center;
    min-height: clamp(160px, 10.75vw, 215px);
}

.d-footer__logo {
    display: block;
    width: 100%;
}

.d-footer__logo img {
    display: block;
    width: 100%;
    max-width: 480px;
    height: auto;
}

/* ---------- Centre column ---------- */
.d-footer__center {
    display: flex;
    flex-direction: column;
    align-items: stretch;
    text-align: center;
    min-width: 0;
}

.d-footer__links,
.d-footer__contact {
    width: 100%;
    max-width: 695px;
    margin-inline: auto;
}

.d-footer__list--row {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: center;
}

/* quick links panel (157 → 201) */
.d-footer__links .d-footer__list--row {
    min-height: 44px;
    gap: 0 clamp(20px, 1.7vw, 34px);
    padding-inline: 24px;
}

.d-footer__links .d-footer__list a {
    display: inline-flex;
    align-items: center;
    min-height: 44px;
    color: #fff;
}

/* contact panel (217 → 259) */
.d-footer__contact {
    margin-top: 16px;
}

.d-footer__list--icons {
    min-height: 42px;
    gap: 0 clamp(24px, 2vw, 40px);
    padding-inline: 24px;
}

.d-footer__list--icons a {
    display: inline-flex;
    align-items: center;
    gap: 13px;
    min-height: 42px;
    font-size: clamp(.95rem, .86vw, 1.05rem);
    font-weight: 400;
    color: #fff;
    overflow-wrap: anywhere;
}

.d-footer__list--icons i {
    flex: 0 0 37px;
    display: grid;
    place-items: center;
    width: 37px;
    height: 37px;
    border-radius: 50%;
    font-size: .8rem;
    color: var(--f-orange);
    background: rgba(227, 147, 116, .16);
    transition: background .2s ease, color .2s ease;
}

.d-footer__list--icons a:hover i,
.d-footer__list--icons a:focus-visible i {
    color: var(--f-mauve);
    background: var(--f-orange);
}

/* ---------- Social (small tinted panel 278 → 345) ---------- */
.d-social {
    display: flex;
    align-self: center;
    gap: 13px;
    margin-top: 39px;
    padding: 7px 10px 13px;
}

.d-social a {
    display: grid;
    place-items: center;
    width: 47px;
    height: 47px;
    border: 1px solid var(--f-line);
    border-radius: 50%;
    font-size: .95rem;
    color: #fff;
    background: rgba(255, 255, 255, .06);
}

.d-social a:hover,
.d-social a:focus-visible {
    color: #fff;
    background: var(--f-blue);
    border-color: var(--f-blue);
    transform: translateY(-2px);
}

/* ---------- Address (3 lines, 22px pitch) ---------- */
.d-footer__address {
    margin: 32px 0 0;
    font-family: "Aptos", "Inter", "Segoe UI", system-ui, sans-serif;
    font-style: normal;
    line-height: 22px;
    color: #fff;
}

.d-footer__address span {
    display: block;
}

/* pin icon above the address */
.d-footer__address .d-footer__pin {
    display: grid;
    place-items: center;
    width: 37px;
    height: 37px;
    margin: 0 auto 8px;
    border-radius: 50%;
    font-size: .9rem;
    color: var(--f-orange);
    background: rgba(227, 147, 116, .16);
}

.d-footer__address .d-footer__pin:hover,
.d-footer__address .d-footer__pin:focus-visible {
    color: var(--f-mauve);
    background: var(--f-orange);
}

/* ---------- Map (355 × 215) ---------- */
.d-footer__map {
    position: relative;
    min-width: 0;
}

/* "open in Google Maps" button floating on the map */
.d-footer__map-btn {
    position: absolute;
    inset-block-end: 12px;
    inset-inline-start: 12px;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    min-height: 36px;
    padding: 0 14px;
    font-size: .85rem;
    font-weight: 600;
    color: var(--f-mauve) !important;
    background: #fff;
    border-radius: 999px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, .3);
}

.d-footer__map-btn i {
    color: #e53935;
}

.d-footer__map-btn:hover,
.d-footer__map-btn:focus-visible {
    background: var(--f-orange);
}

.d-footer__map-frame {
    display: block;
    width: 100%;
    aspect-ratio: 355 / 215;
    border: 0;
    border-radius: 28px;
    background: rgba(255, 255, 255, .05);
}

/* ==========================================================================
   Bottom bar : 1275px wide, divider at its top (y = 467), text at y ≈ 510
   ========================================================================== */
.d-footer__bottom {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 8px 32px;
    width: min(1275px, 100% - 2 * clamp(20px, 5vw, 40px));
    margin-inline: auto;
    padding-block: 33px 34px;
    border-top: 1px solid rgba(255, 255, 255, .12);
    font-size: .95rem;
    font-weight: 500;
    line-height: 20px;
    color: var(--f-muted);
}

.d-footer__bottom p {
    margin: 0;
}

.d-footer__legal {
    display: flex;
    flex-wrap: wrap;
    gap: 4px 26px;
}

.d-footer__legal a {
    display: inline-block;
    color: var(--f-muted);
}

.d-footer__legal a:hover,
.d-footer__legal a:focus-visible {
    color: var(--f-blue);
}

/* ==========================================================================
   Below 1100px — stack: logo, centre column, map
   ========================================================================== */
@media (max-width: 1100px) {
    .d-footer {
        padding-top: 72px;
    }

    .d-footer__inner {
        width: min(100% - 40px, 720px);
    }

    .d-footer__grid {
        grid-template-columns: 1fr;
        justify-items: center;
        row-gap: 32px;
    }

    .d-footer__brand {
        min-height: 0;
        justify-content: center;
    }

    .d-footer__logo img {
        max-width: min(420px, 100%);
        margin-inline: auto;
    }

    .d-footer__center {
        width: 100%;
    }

    .d-footer__map {
        width: min(100%, 420px);
    }

    .d-footer__zellige {
        width: 40%;
    }
}

/* ==========================================================================
   Mobile
   ========================================================================== */
@media (max-width: 640px) {
    .d-footer {
        padding-top: 52px;
    }

    .d-footer__grid {
        row-gap: 26px;
        padding-bottom: 32px;
    }

    .d-footer__links .d-footer__list--row {
        padding-inline: 8px;
        gap: 2px 16px;
    }

    .d-footer__links .d-footer__list a {
        font-size: 1.05rem;
    }

    .d-footer__list--icons {
        flex-direction: column;
        align-items: flex-start;
        padding-inline: 16px;
        gap: 2px;
    }

    .d-footer__list--icons a {
        font-size: 1rem;
    }

    .d-footer__bottom {
        flex-direction: column;
        align-items: flex-start;
        gap: 4px;
        /* room for the floating back-to-top button + iPhone home bar */
        padding-block-end: calc(88px + env(safe-area-inset-bottom, 0px));
    }

    .d-footer__legal {
        gap: 0 20px;
    }

    .d-footer__legal a {
        min-height: 44px;
        display: inline-flex;
        align-items: center;
    }

    .d-footer__zellige {
        width: 60%;
        opacity: .07;
    }
}

@media (prefers-reduced-motion: reduce) {
    .d-footer *,
    .d-footer *::before,
    .d-footer *::after {
        transition: none !important;
        transform: none !important;
        animation: none !important;
    }
}
</style>