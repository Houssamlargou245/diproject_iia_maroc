{{--
    Site footer.

    Columns, in order:

      1. Brand — the locale's ARABCIA lockup, plus the social channels.
      2. Quick links — every navigable rubrique (guarded by Route::has so a
         removed route can never break the footer).
      3. Contact — host hotel, address, phone, e-mail and a map link.
      4. Host hotel map — an API-key-free Google Maps embed driven by the
         edition's stored coordinates, so there is one source of truth for the
         venue's location. Falls back to a plain map link when there are no
         coordinates.

    Design tokens are shared with the Programme page (ink / deep / surface /
    line), the zellige line-art sits on the end side, and logical properties
    are used throughout so the whole footer mirrors correctly in RTL.
--}}
@php
    $edition = $currentEdition ?? null;

    /* ---- City only if it isn't already inside the address ---- */
    $showCity = $edition?->city
        && ! \Illuminate\Support\Str::contains(
            mb_strtolower((string) $edition->venue_address),
            mb_strtolower($edition->city)
        );

    /* Embedded map of the host hotel: an API-key-free Google Maps embed driven
       by the same coordinates the rest of the site links to, so there is one
       source of truth for where the venue is. */
    $mapEmbed = ($edition?->venue_lat && $edition?->venue_lng)
        ? 'https://maps.google.com/maps?q='.$edition->venue_lat.','.$edition->venue_lng
            .'&z=16&hl='.app()->getLocale().'&output=embed'
        : null;
@endphp

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet"
      href="https://fonts.googleapis.com/css2?family=Archivo:wght@700&family=Inter:wght@400;500;600&display=swap">

<footer class="d-footer" role="contentinfo">
    <span class="d-footer__zellige" aria-hidden="true"></span>

    <div class="container">

        <div class="d-footer__grid">

            {{-- Column 1 — brand ------------------------------------------ --}}
            <div class="d-footer__brand">
                <a href="{{ route('home') }}" class="d-footer__logo" aria-label="{{ __('site.site_name') }}">
                    <img src="{{asset('assets/brand/logo-iia-maroc.png')}}"
                         width=""
                         height=""
                         alt="{{ __('site.site_name') }}"
                         loading="lazy"
                         decoding="async">
                </a>

                <ul class="d-social">
                    <li>
                        <a href="https://www.linkedin.com/company/iia-maroc-amaci/?viewAsMember=true"
                           rel="noopener noreferrer" target="_blank" aria-label="LinkedIn">
                            <i class="fab fa-linkedin-in" aria-hidden="true"></i>
                        </a>
                    </li>
                    <li>
                        <a href="https://www.facebook.com/profile.php?id=100066862488270"
                           rel="noopener noreferrer" target="_blank" aria-label="Facebook">
                            <i class="fab fa-facebook-f" aria-hidden="true"></i>
                        </a>
                    </li>
                </ul>
            </div>

            {{-- Column 2 — quick links ------------------------------------ --}}
            <nav class="d-footer__links" aria-labelledby="footer-links-title">
                <h2 id="footer-links-title" class="d-footer__title">@lang('footer.quick_links')</h2>

                <ul class="d-footer__list">
                    @foreach ([
                        ['home', __('nav.home')],
                        ['presentation', __('nav.presentation')],
                        ['programme', __('nav.programme')],
                        ['venue', __('nav.venue')],
                        ['speakers', __('nav.speakers')],
                        ['sponsors', __('nav.sponsors')],
                        ['pricing', __('nav.pricing')],
                        ['contact', __('nav.contact')],
                    ] as [$route, $label])
                        @continue(! Route::has($route))
                        <li><a href="{{ route($route) }}">{{ $label }}</a></li>
                    @endforeach
                </ul>
            </nav>

            {{-- Column 3 — contact ---------------------------------------- --}}
            <div class="d-footer__contact">
                <h2 class="d-footer__title">@lang('nav.contact')</h2>

                <address class="d-footer__address">
                    @if ($edition?->venue_name)
                        <strong>{{ $edition->venueLine() }}</strong>
                    @endif
                    @if ($edition?->venue_address)
                        <span dir="auto">{{ $edition->venueAddressLine() }}</span>
                    @endif
                    @if ($showCity)
                        <span dir="auto">{{ $edition->cityLine() }}</span>
                    @endif
                </address>

                {{-- dir="ltr" only on the value (phone / e-mail) so the icon still sits at the start in Arabic --}}
                <ul class="d-footer__list d-footer__list--icons">
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

                    @if ($edition?->mapUrl())
                        <li>
                            <a href="{{ $edition->mapUrl() }}" rel="noopener noreferrer" target="_blank">
                                <i class="fas fa-map-marker-alt" aria-hidden="true"></i>
                                <span>@lang('contact.open_map')</span>
                            </a>
                        </li>
                    @endif
                </ul>
            </div>

            {{-- Column 4 — host hotel map --------------------------------- --}}
            <div class="d-footer__map">
                <h2 class="d-footer__title">@lang('nav.venue')</h2>

                @if ($mapEmbed)
                    <iframe
                        class="d-footer__map-frame"
                        src="{{ $mapEmbed }}"
                        title="{{ $edition?->venueLine() }}"
                        loading="lazy"
                        referrerpolicy="no-referrer-when-downgrade"
                        allowfullscreen></iframe>
                @elseif ($edition?->mapUrl())
                    <a class="d-footer__map-link"
                       href="{{ $edition->mapUrl() }}"
                       rel="noopener noreferrer"
                       target="_blank">
                        <i class="fas fa-map-marker-alt" aria-hidden="true"></i>
                        <span>@lang('contact.open_map')</span>
                    </a>
                @endif
            </div>

        </div>

        <div class="d-footer__bottom">
            <p>
                &copy; {{ $edition?->year ?? date('Y') }}
                {{ $edition?->organiser ?? __('site.organiser') }}
                &middot; @lang('footer.rights')
            </p>

            <ul class="d-footer__legal">
                <li><a href="{{ route('contact') }}">@lang('footer.privacy')</a></li>
                <li><a href="{{ route('contact') }}">@lang('footer.legal')</a></li>
                <li><a href="{{ route('contact') }}">@lang('footer.accessibility')</a></li>
            </ul>
        </div>

    </div>
</footer>

<style>
/* ==========================================================================
   ARABCIA footer
   Fonts  : Archivo Bold (titles) · Aptos → Inter (content)
   Tokens : ink #1b1464 · deep #2f1f9c · brand #4f3cc9 · line #e4e1f4 · surface #f6f5fd
            orange #e39374 (accent) · blue #7384ff (hover)
   ========================================================================== */

.d-footer {
    --f-grad-a: #2c1181;
    --f-grad-b: #201062;
    --f-ink: #1b1464;
    --f-deep: #2f1f9c;
    --f-surface: #f6f5fd;
    --f-pop-line: #e4e1f4;
    --f-mauve: #2a1f6e;
    --f-blue: #7384ff;
    --f-orange: #e39374;
    --f-text: rgba(255, 255, 255, .84);
    --f-muted: rgba(255, 255, 255, .72);
    --f-line: rgba(255, 255, 255, .14);

    position: relative;
    isolation: isolate;
    margin: 0;
    padding-block: 88px 0;
    color: var(--f-text);
    font-family: "Aptos", "Inter", "Segoe UI", system-ui, -apple-system, sans-serif;
    font-size: .95rem;
    line-height: 1.65;
    background: linear-gradient(160deg, var(--f-grad-a) 0%, var(--f-grad-b) 100%);
    overflow: hidden;
    -webkit-font-smoothing: antialiased;
}

/* top accent rule */
.d-footer::before {
    content: "";
    position: absolute;
    inset-inline: 0;
    top: 0;
    height: 3px;
    background: linear-gradient(90deg, var(--f-orange), var(--f-blue));
}

/* soft glow for depth */
.d-footer::after {
    content: "";
    position: absolute;
    z-index: -1;
    inset-inline-end: -160px;
    top: -200px;
    width: 520px;
    height: 520px;
    border-radius: 50%;
    background: radial-gradient(closest-side, rgba(115, 132, 255, .22), transparent);
    pointer-events: none;
}

/* zellige line-art on the end side (SVG data-URI, no icon font) */
.d-footer__zellige {
    position: absolute;
    z-index: -1;
    inset-block: 0;
    inset-inline-end: 0;
    width: min(46%, 560px);
    opacity: .09;
    pointer-events: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='80' height='80' viewBox='0 0 80 80' fill='none' stroke='white' stroke-width='1'%3E%3Crect x='20' y='20' width='40' height='40'/%3E%3Crect x='20' y='20' width='40' height='40' transform='rotate(45 40 40)'/%3E%3Ccircle cx='40' cy='40' r='9'/%3E%3Cpath d='M0 0l8 8M80 0l-8 8M0 80l8-8M80 80l-8-8'/%3E%3C/svg%3E");
    background-size: 80px 80px;
    -webkit-mask-image: linear-gradient(to left, #000 10%, transparent 100%);
            mask-image: linear-gradient(to left, #000 10%, transparent 100%);
}

[dir="rtl"] .d-footer__zellige {
    -webkit-mask-image: linear-gradient(to right, #000 10%, transparent 100%);
            mask-image: linear-gradient(to right, #000 10%, transparent 100%);
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

.d-footer a:focus-visible,
.d-footer button:focus-visible {
    outline: 2px solid var(--f-orange);
    outline-offset: 3px;
    border-radius: 8px;
}

.d-footer ul {
    list-style: none;
    margin: 0;
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

/* ---------- Grid ---------- */
.d-footer__grid {
    display: grid;
    grid-template-columns: minmax(0, 1.05fr) minmax(0, .7fr) minmax(0, 1.15fr) minmax(0, 1.4fr);
    gap: 48px 56px;
    padding-bottom: 64px;
}

/* ---------- Titles ---------- */
.d-footer__title {
    position: relative;
    margin: 0 0 26px;
    padding-bottom: 14px;
    font-family: "Archivo", "Aptos", "Inter", sans-serif;
    font-weight: 700;
    font-size: 1.15rem;
    line-height: 1.3;
    letter-spacing: .02em;
    color: #fff;
}

.d-footer__title::after {
    content: "";
    position: absolute;
    inset-inline-start: 0;
    bottom: 0;
    width: 28px;
    height: 3px;
    border-radius: 3px;
    background: var(--f-orange);
}

[dir="rtl"] .d-footer__title,
:lang(ar) .d-footer__title {
    letter-spacing: 0;
}

/* ---------- Brand ---------- */
.d-footer__logo {
    display: inline-block;
    margin-bottom: 22px;
}

.d-footer__logo img {
    display: block;
    height: auto;
    width: auto;
    max-width: 290px;
}

/* ---------- Social ---------- */
.d-social {
    display: flex;
    gap: 12px;
}

.d-social a {
    display: grid;
    place-items: center;
    width: 44px;
    height: 44px;
    border: 1px solid var(--f-line);
    border-radius: 50%;
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

/* ---------- Lists ---------- */
.d-footer__list li + li {
    margin-top: 6px;
}

.d-footer__list a {
    display: inline-flex;
    align-items: center;
    min-height: 36px;
}

.d-footer__list:not(.d-footer__list--icons) a:hover {
    transform: translateX(4px);
}

[dir="rtl"] .d-footer__list:not(.d-footer__list--icons) a:hover {
    transform: translateX(-4px);
}

/* ---------- Contact ---------- */
.d-footer__address {
    margin: 0 0 16px;
    font-style: normal;
    line-height: 1.6;
}

.d-footer__address strong {
    display: block;
    margin-bottom: 4px;
    color: #fff;
    font-weight: 600;
}

.d-footer__address span {
    display: block;
}

.d-footer__list--icons li + li {
    margin-top: 8px;
}

.d-footer__list--icons a {
    gap: 12px;
    overflow-wrap: anywhere;
}

.d-footer__list--icons i {
    flex: 0 0 34px;
    display: grid;
    place-items: center;
    width: 34px;
    height: 34px;
    border-radius: 50%;
    font-size: .8rem;
    color: var(--f-orange);
    background: rgba(227, 147, 116, .14);
    transition: background .2s ease, color .2s ease;
}

.d-footer__list--icons a:hover i,
.d-footer__list--icons a:focus-visible i {
    color: var(--f-mauve);
    background: var(--f-orange);
}

/* ---------- Map ---------- */
.d-footer__map {
    scroll-margin-top: 96px;
    min-width: 0;
}

.d-footer__map-frame {
    display: block;
    width: 100%;
    aspect-ratio: 4 / 3;
    border: 1px solid var(--f-line);
    border-radius: 16px;
    background: rgba(255, 255, 255, .05);
    box-shadow: 0 18px 40px -24px rgba(10, 5, 60, .8);
}

.d-footer__map-link {
    display: inline-flex;
    align-items: center;
    gap: 12px;
    min-height: 44px;
    padding: 0 20px;
    font-weight: 600;
    color: #fff;
    background: rgba(255, 255, 255, .06);
    border: 1px solid var(--f-line);
    border-radius: 999px;
}

.d-footer__map-link:hover,
.d-footer__map-link:focus-visible {
    color: #fff;
    background: var(--f-blue);
    border-color: var(--f-blue);
}

/* ---------- Bottom bar ---------- */
.d-footer__bottom {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 8px 32px;
    padding-block: 22px;
    border-top: 1px solid var(--f-line);
    font-size: .85rem;
    color: var(--f-muted);
}

.d-footer__bottom p {
    margin: 0;
}

.d-footer__legal {
    display: flex;
    flex-wrap: wrap;
    gap: 4px 24px;
}

.d-footer__legal a {
    display: inline-block;
    padding-block: 6px;
}

/* ==========================================================================
   Tablet
   ========================================================================== */
@media (max-width: 1100px) {
    .d-footer {
        padding-top: 72px;
    }

    .d-footer__grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 44px 40px;
        padding-bottom: 48px;
    }

    .d-footer__brand,
    .d-footer__map {
        grid-column: 1 / -1;
    }
}

/* make sure the site's own .container never leaves a huge side gutter on small screens */
@media (max-width: 991.98px) {
    .d-footer .container {
        width: 100%;
        max-width: none;
        margin-inline: auto;
        padding-inline: clamp(20px, 5vw, 40px);
    }
}

/* ==========================================================================
   Mobile
   ========================================================================== */
@media (max-width: 640px) {
    .d-footer {
        padding-top: 52px;
        font-size: 1rem;
    }

    .d-footer__grid {
        grid-template-columns: 1fr;
        gap: 36px;
        padding-bottom: 36px;
    }

    .d-footer__title {
        margin-bottom: 18px;
    }

    /* quick links -> pills */
    .d-footer__links .d-footer__list {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
    }

    .d-footer__links .d-footer__list li + li {
        margin-top: 0;
    }

    .d-footer__links .d-footer__list a {
        min-height: 44px;
        padding-inline: 20px;
        border: 1px solid var(--f-line);
        border-radius: 999px;
        background: rgba(255, 255, 255, .06);
        color: #fff;
    }

    .d-footer__links .d-footer__list a:hover,
    .d-footer__links .d-footer__list a:focus-visible {
        transform: none;
        color: #fff;
        background: var(--f-blue);
        border-color: var(--f-blue);
    }

    .d-footer__list--icons a {
        min-height: 48px;
    }

    .d-footer__map-frame {
        aspect-ratio: 3 / 2;
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
        width: 70%;
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
