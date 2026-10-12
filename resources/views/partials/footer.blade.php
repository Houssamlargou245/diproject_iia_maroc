{{--
    Site footer — ARABCIA 2026 / IIA Morocco

    v3
    - Brand intro: short text + a popover showing the FULL text on hover (desktop),
      on tap (touch) or via the "read more" button (keyboard). Esc / outside click closes it.
    - Newsletter now posts to its OWN route (newsletter.store) — no more
      "Le champ name / message est obligatoire". Works with or without JS
      (fetch when JS is on, classic POST + flash message when it is off).
    - Mobile: quick links become pills, contact items get round icon badges,
      newsletter sits in a card, bigger touch targets, safe-area padding.
    - Design tokens shared with the Programme page (ink / deep / surface / line),
      zellige line-art on the end side, logical properties everywhere (RTL ready).
--}}
@php
    $edition = $currentEdition ?? null;

    /* ---- Brand intro: clean ellipsis, cut by words, keep the full text for the popover ---- */
    $introFull = trim(strip_tags((string) ($edition?->introduction ?: __('site.organiser'))));
    $introFull = preg_replace('/(\.{2,}|…)+\s*$/u', '…', $introFull);

    $introLimit     = 28;
    $introWords     = count(preg_split('/\s+/u', $introFull, -1, PREG_SPLIT_NO_EMPTY));
    $introTruncated = $introWords > $introLimit;
    $intro          = $introTruncated
        ? \Illuminate\Support\Str::words(rtrim($introFull, ' .…'), $introLimit, '…')
        : $introFull;

    /* ---- City only if it isn't already inside the address ---- */
    $showCity = $edition?->city
        && ! \Illuminate\Support\Str::contains(
            mb_strtolower((string) $edition->venue_address),
            mb_strtolower($edition->city)
        );

    /* ---- Newsletter feedback (no-JS fallback) ---- */
    $nlOk    = session('newsletter_status');
    $nlError = $errors->newsletter->first('email');
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
                    <img src="{{ \App\Support\Brand::logoUrl() }}"
                         width="{{ \App\Support\Brand::logo()['width'] }}"
                         height="{{ \App\Support\Brand::logo()['height'] }}"
                         alt="{{ __('site.site_name') }}"
                         loading="lazy"
                         decoding="async">
                </a>

                <div class="d-intro" data-footer-intro>
                    <p class="d-footer__desc" id="footer-intro-text">{{ $intro }}</p>

                    @if ($introTruncated)
                        <button type="button"
                                class="d-intro__btn"
                                aria-expanded="false"
                                aria-controls="footer-intro-pop">
                            <span>@lang('footer.read_more')</span>
                            <i class="fas fa-chevron-down" aria-hidden="true"></i>
                        </button>

                        <div class="d-pop" id="footer-intro-pop" role="region" aria-label="@lang('footer.about_full')">
                            <p>{{ $introFull }}</p>
                            <button type="button" class="d-pop__close">@lang('footer.close')</button>
                        </div>
                    @endif
                </div>

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
                        ['programme', __('nav.programme')],
                        ['pricing', __('nav.pricing')],
                        ['sponsors', __('nav.sponsors')],
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

            {{-- Column 4 — newsletter ------------------------------------- --}}
            <div class="d-footer__newsletter" id="newsletter">
                <h2 class="d-footer__title">@lang('footer.newsletter')</h2>

                <p class="d-footer__desc">@lang('footer.newsletter_lede')</p>

                <form method="POST"
                      action="{{ route('newsletter.store') }}"
                      class="d-news"
                      id="footer-newsletter-form"
                      data-error="{{ __('footer.newsletter_err_generic') }}">
                    @csrf

                    {{-- Honeypot --}}
                    <div class="visually-hidden" aria-hidden="true">
                        <label for="footer-newsletter-company">@lang('misc.leave_blank')</label>
                        <input type="text" id="footer-newsletter-company" name="company"
                               tabindex="-1" autocomplete="off">
                    </div>

                    <label class="visually-hidden" for="footer-newsletter">
                        @lang('footer.newsletter_email')
                    </label>

                    <div class="d-news__row">
                        <input type="email"
                               id="footer-newsletter"
                               name="email"
                               required
                               maxlength="190"
                               autocomplete="email"
                               autocapitalize="off"
                               spellcheck="false"
                               inputmode="email"
                               dir="ltr"
                               aria-describedby="footer-newsletter-msg"
                               placeholder="{{ __('footer.newsletter_email') }}">

                        <button type="submit" class="d-news__btn" title="{{ __('footer.subscribe') }}">
                            <span class="visually-hidden">@lang('footer.subscribe')</span>
                            <i class="fas fa-paper-plane" aria-hidden="true"></i>
                        </button>
                    </div>

                    <p class="d-news__msg {{ $nlOk ? 'is-ok' : ($nlError ? 'is-err' : '') }}"
                       id="footer-newsletter-msg"
                       role="status"
                       aria-live="polite">{{ $nlOk ?: $nlError }}</p>

                    <p class="d-news__note">@lang('footer.newsletter_note')</p>
                </form>
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
    grid-template-columns: minmax(0, 1.55fr) minmax(0, .8fr) minmax(0, 1.3fr) minmax(0, 1.35fr);
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
    max-width: 160px;
}

.d-footer__desc {
    margin: 0 0 24px;
    max-width: 48ch;
    line-height: 1.75;
    color: var(--f-text);
}

/* ---------- Intro + popover ---------- */
.d-intro {
    position: relative;
    margin-bottom: 24px;
}

.d-intro .d-footer__desc {
    margin-bottom: 12px;
    display: -webkit-box;
    -webkit-line-clamp: 5;
    -webkit-box-orient: vertical;
    overflow: hidden;
    cursor: default;
}

.d-intro__btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    min-height: 36px;
    padding: 0 16px;
    font: inherit;
    font-size: .8rem;
    font-weight: 600;
    color: #fff;
    background: rgba(255, 255, 255, .06);
    border: 1px solid var(--f-line);
    border-radius: 999px;
    cursor: pointer;
    transition: background .2s ease, border-color .2s ease;
}

.d-intro__btn:hover {
    background: rgba(255, 255, 255, .12);
    border-color: rgba(255, 255, 255, .3);
}

.d-intro__btn i {
    font-size: .65rem;
    color: var(--f-orange);
    transition: transform .25s ease;
}

.d-intro.is-open .d-intro__btn i {
    transform: rotate(180deg);
}

.d-pop {
    position: absolute;
    z-index: 30;
    inset-inline: -16px;
    top: -14px;
    max-height: min(360px, 70vh);
    overflow-y: auto;
    padding: 18px 20px;
    color: var(--f-ink);
    background: var(--f-surface);
    border: 1px solid var(--f-pop-line);
    border-radius: 14px;
    box-shadow: 0 24px 60px -16px rgba(10, 5, 60, .65), 0 2px 0 var(--f-orange) inset;
    opacity: 0;
    visibility: hidden;
    transform: translateY(8px) scale(.98);
    transform-origin: top;
    transition: opacity .2s ease, transform .2s ease, visibility 0s linear .2s;
}

.d-pop p {
    margin: 0;
    font-size: .92rem;
    line-height: 1.75;
}

.d-pop__close {
    display: none;
    margin-top: 14px;
    min-height: 40px;
    padding: 0 20px;
    font: inherit;
    font-size: .8rem;
    font-weight: 700;
    color: #fff;
    background: var(--f-deep);
    border: 0;
    border-radius: 999px;
    cursor: pointer;
}

.d-intro.is-open .d-pop {
    opacity: 1;
    visibility: visible;
    transform: none;
    transition-delay: 0s;
}

.d-intro.is-open .d-pop__close {
    display: inline-flex;
    align-items: center;
}

@media (hover: hover) {
    .d-intro:hover .d-pop {
        opacity: 1;
        visibility: visible;
        transform: none;
        transition-delay: 0s;
    }

    .d-intro:hover .d-intro__btn i {
        transform: rotate(180deg);
    }
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

/* ---------- Newsletter ---------- */
.d-news {
    max-width: 440px;
}

.d-news__row {
    display: flex;
    align-items: stretch;
    gap: 8px;
}

.d-news input[type="email"] {
    flex: 1;
    min-width: 0;
    height: 48px;
    padding: 0 16px;
    font: inherit;
    color: #fff;
    background: rgba(255, 255, 255, .08);
    border: 1px solid var(--f-line);
    border-radius: 999px;
    transition: border-color .2s ease, background .2s ease, box-shadow .2s ease;
}

.d-news input[type="email"]::placeholder {
    color: rgba(255, 255, 255, .62);
}

.d-news input[type="email"]:focus,
.d-news input[type="email"]:focus-visible {
    outline: none;
    border-color: var(--f-orange);
    background: rgba(255, 255, 255, .12);
    box-shadow: 0 0 0 3px rgba(227, 147, 116, .25);
}

.d-news__btn {
    position: relative;
    flex: 0 0 48px;
    height: 48px;
    display: grid;
    place-items: center;
    padding: 0;
    border: 0;
    border-radius: 50%;
    color: var(--f-mauve);
    background: var(--f-orange);
    cursor: pointer;
    transition: background .2s ease, transform .2s ease;
}

.d-news__btn:hover {
    background: #eda78c;
    transform: translateY(-2px);
}

.d-news__btn:active {
    transform: translateY(0);
}

.d-news__btn:disabled {
    cursor: progress;
    opacity: .85;
}

[dir="rtl"] .d-news__btn i {
    transform: scaleX(-1);
}

/* loading spinner */
.d-news.is-loading .d-news__btn i {
    opacity: 0;
}

.d-news.is-loading .d-news__btn::after {
    content: "";
    position: absolute;
    width: 18px;
    height: 18px;
    border: 2px solid rgba(42, 31, 110, .25);
    border-top-color: var(--f-mauve);
    border-radius: 50%;
    animation: d-spin .7s linear infinite;
}

@keyframes d-spin {
    to { transform: rotate(360deg); }
}

/* status message */
.d-news__msg {
    position: relative;
    margin: 12px 0 0;
    padding-inline-start: 26px;
    font-size: .86rem;
    font-weight: 500;
    line-height: 1.5;
}

.d-news__msg:empty {
    display: none;
}

.d-news__msg::before {
    content: "";
    position: absolute;
    inset-inline-start: 0;
    top: .2em;
    width: 18px;
    height: 18px;
    background: center / contain no-repeat;
}

.d-news__msg.is-ok {
    color: #9be7b5;
}

.d-news__msg.is-ok::before {
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%239be7b5' stroke-width='3' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M5 12.5l4.5 4.5L19 7.5'/%3E%3C/svg%3E");
}

.d-news__msg.is-err {
    color: #ffb4a8;
}

.d-news__msg.is-err::before {
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%23ffb4a8' stroke-width='3' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M12 6v8M12 18h.01'/%3E%3C/svg%3E");
}

.d-news__note {
    margin: 12px 0 0;
    font-size: .82rem;
    line-height: 1.5;
    color: var(--f-muted);
}

.d-footer__newsletter {
    scroll-margin-top: 96px;
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
    .d-footer__newsletter {
        grid-column: 1 / -1;
    }

    .d-footer__desc {
        max-width: 60ch;
    }

    /* newsletter becomes a card */
    .d-footer__newsletter {
        padding: 28px;
        border: 1px solid var(--f-line);
        border-radius: 18px;
        background: linear-gradient(160deg, rgba(255, 255, 255, .09), rgba(255, 255, 255, .03));
        box-shadow: 0 18px 40px -24px rgba(10, 5, 60, .8);
    }

    .d-footer__newsletter .d-footer__desc {
        margin-bottom: 18px;
    }

    .d-news {
        max-width: 520px;
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

    /* popover = full-width bottom-sheet-like card, tap to open */
    .d-pop {
        inset-inline: -8px;
        max-height: 60vh;
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

    .d-footer__newsletter {
        padding: 22px 18px;
        border-radius: 16px;
    }

    .d-news {
        max-width: none;
    }

    /* 16px+ prevents iOS Safari from zooming on focus */
    .d-news input[type="email"] {
        height: 52px;
        font-size: 1rem;
    }

    .d-news__btn {
        flex-basis: 52px;
        height: 52px;
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

<script>
(function () {
    'use strict';

    /* ------------------------------------------------------------------
       1) Intro popover — hover is pure CSS; this handles tap / keyboard
       ------------------------------------------------------------------ */
    var intro = document.querySelector('[data-footer-intro]');

    if (intro) {
        var btn   = intro.querySelector('.d-intro__btn');
        var text  = intro.querySelector('.d-footer__desc');
        var close = intro.querySelector('.d-pop__close');

        var setOpen = function (open) {
            intro.classList.toggle('is-open', open);
            if (btn) { btn.setAttribute('aria-expanded', open ? 'true' : 'false'); }
        };

        if (btn) {
            btn.addEventListener('click', function (e) {
                e.stopPropagation();
                setOpen(!intro.classList.contains('is-open'));
            });

            if (text) {
                text.addEventListener('click', function (e) {
                    e.stopPropagation();
                    setOpen(!intro.classList.contains('is-open'));
                });
            }

            if (close) {
                close.addEventListener('click', function (e) {
                    e.stopPropagation();
                    setOpen(false);
                    btn.focus();
                });
            }

            document.addEventListener('click', function (e) {
                if (!intro.contains(e.target)) { setOpen(false); }
            });

            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape' && intro.classList.contains('is-open')) {
                    setOpen(false);
                    btn.focus();
                }
            });
        }
    }

    /* ------------------------------------------------------------------
       2) Newsletter — fetch when JS is available, classic POST otherwise
       ------------------------------------------------------------------ */
    var form = document.getElementById('footer-newsletter-form');

    if (form && window.fetch && window.FormData) {
        var msg    = document.getElementById('footer-newsletter-msg');
        var submit = form.querySelector('.d-news__btn');

        var show = function (text, type) {
            msg.textContent = text || '';
            msg.className = 'd-news__msg' + (type ? ' is-' + type : '');
        };

        form.addEventListener('submit', function (e) {
            e.preventDefault();

            if (!form.checkValidity()) {
                form.reportValidity();
                return;
            }

            var generic = form.getAttribute('data-error') || '';

            show('', '');
            submit.disabled = true;
            form.classList.add('is-loading');

            fetch(form.action, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: new FormData(form),
                credentials: 'same-origin'
            })
            .then(function (res) {
                return res.json().catch(function () { return {}; }).then(function (data) {
                    return { status: res.status, ok: res.ok, data: data };
                });
            })
            .then(function (r) {
                if (r.ok && r.data && r.data.ok) {
                    show(r.data.message, 'ok');
                    form.reset();
                } else if (r.status === 422 && r.data && r.data.errors && r.data.errors.email) {
                    show(r.data.errors.email[0], 'err');
                } else {
                    show(generic, 'err');
                }
            })
            .catch(function () {
                show(generic, 'err');
            })
            .then(function () {
                submit.disabled = false;
                form.classList.remove('is-loading');
            });
        });
    }
})();
</script>