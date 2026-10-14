{{--
    The hero. The one screen that has to earn attention in three seconds.

    Composed rather than illustrated. The artwork on the inline-end side is
    built from the brand's own pattern tile, stacked as three translucent
    panels and lit from the upper corner, so it stays sharp at any pixel
    density and there is no binary to re-export when the brand refreshes. The
    corner sparkles are the same idea at a smaller scale.

    The copy column is first in both DOM and visual order, which is what
    `margin-inline-start: auto` on `.h-hero__inner` buys: LTR puts the copy on
    the left and the panels on the right, RTL swaps the two, and neither
    direction is a special case anywhere in the markup.

    The date line is deliberately not `dir="ltr"`-ed. `Edition::dateLine()`
    already renders in the active locale — an Arabic month name with Latin
    digits for an Arabic reader — and forcing an LTR run over that reorders a
    correct date into a wrong one.

    Pass `compact` to get a shorter hero (used on the home page only).
--}}
@props(['edition', 'locale', 'compact' => false])

@php
    $lockup = \App\Support\Brand::logo();
@endphp

<section class="h-hero {{ $compact ? 'h-hero--compact' : '' }}" data-hero>

    <span class="h-hero__bloom" aria-hidden="true"></span>
    <span class="h-hero__sparks" aria-hidden="true"></span>

    <div class="container">
        <div class="h-hero__inner">

            {{-- The two marks, dark ink turned white rather than swapped for a
                 white variant: the brand ships one artwork per locale, and
                 `brightness(0) invert(1)` maps every pixel of it to white
                 without touching the file on disk. --}}
            <div class="h-hero__logos">

               
               
<img src="{{ asset('assets/brand/logo-iia-maroc.png') }}"
     alt="{{ __('site.site_name') }}"
     style="width: 300px; height: 300px; object-fit: contain;"
     fetchpriority="high"
     decoding="async">

 <img src="{{ asset('assets/brand/arabic_itihad_logo.png') }}"
                     width="{{ $lockup['width'] }}"
                     height="{{ $lockup['height'] }}"
                     alt="{{ __('site.site_name') }}"
                     fetchpriority="high"
                     decoding="async">
                       <img src="{{ asset('assets/brand/arabcia_logo.png') }}"
                     width="{{ $lockup['width'] }}"
                     height="{{ $lockup['height'] }}"
                     alt="{{ __('site.site_name') }}"
                     fetchpriority="high"
                     decoding="async">
            </div>

            <h1 class="h-hero__title">
                <span class="h-hero__name">{{ $edition->organiser }}</span>
                <span class="h-hero__year">{{ $edition->year }}</span>
            </h1>

            <p class="h-hero__kicker">@lang('home.landing.hero.kicker')</p>

            <p class="h-hero__theme">{{ $edition->theme }}</p>

            {{-- The three things a delegate checks before anything else: when,
                 where, and which country. Inline SVG rather than the icon
                 font, so the stroke weight matches the band at every density
                 and nothing degrades to a tofu box when the font fails. --}}
            <ul class="h-hero__meta">
                <li>
                    <span class="h-hero__fact-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3.5" y="5" width="17" height="15.5" rx="2.5"></rect>
                            <path d="M8 3v4M16 3v4M3.5 10h17"></path>
                        </svg>
                    </span>
                    <span>{{ $edition->dateLine($locale) }}</span>
                </li>
                <li>
                        <span class="h-hero__fact-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M3.5 21h17M5.5 21V6.5L12 3l6.5 3.5V21"></path>
                            <path d="M9.5 10H11M13 10h1.5M9.5 13.5H11M13 13.5h1.5M10 21v-3.5h4V21"></path>
                        </svg>
                    </span>
                  
                    <span>{{ $edition->venueLine($locale) }}</span>
                </li>
                <li>
                      <span class="h-hero__fact-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M12 21.5s7-6.6 7-11.5a7 7 0 1 0-14 0c0 4.9 7 11.5 7 11.5Z"></path>
                            <circle cx="12" cy="10" r="2.6"></circle>
                        </svg>
                    </span>
                
                    <span>{{ $edition->cityLine($locale) }}</span>
                </li>
            </ul>

            <div class="h-hero__actions">
                @php
                    $registerUrl = $edition->registration_open
                        ? (auth()->check() ? route('pricing') : route('register'))
                        : null;
                @endphp

            

                <a href="{{ route('programme') }}" class="h-btn h-btn--ghost">
                    @lang('home.landing.hero.cta_programme')
                </a>
                    @if ($registerUrl)
                    <a href="{{ $registerUrl }}" class="h-btn h-btn--solid">
                        @lang('home.landing.hero.cta_register')
                    </a>
                @endif
                <a href="{{ route('sponsors') }}" class="h-btn h-btn--ghost">
                    @lang('home.landing.hero.cta_sponsor')
                </a>
            </div>

        </div>
    </div>
</section>
<style>
    /* ---------- Compact hero (home page only) ---------- */
.h-hero--compact {
    min-height: 0;                 /* drop any 100vh / 80vh height */
    padding-block: .5rem;
}

.h-hero--compact .h-hero__inner {
    padding-block: 0;
    min-height: 0;
}

.h-hero--compact .h-hero__logos {
    margin-block-end: 1rem;
}

.h-hero--compact .h-hero__logos img {
    max-height: 48px;              /* smaller logos; adjust to taste */
    width: auto;
}

.h-hero--compact .h-hero__title {
    margin-block: 0.5rem;
    font-size: clamp(2rem, 4.5vw, 3.25rem);
}

.h-hero--compact .h-hero__kicker {
    margin-block: 0.25rem;
}

.h-hero--compact .h-hero__theme {
    margin-block: 0.5rem 1rem;
}

.h-hero--compact .h-hero__meta {
    margin-block: 1rem;
    gap: 0.5rem 1.5rem;
}

.h-hero--compact .h-hero__actions {
    margin-block-start: 1.25rem;
}

/* Scale down the decorative artwork so it matches the shorter section */
.h-hero--compact .h-hero__bloom,
.h-hero--compact .h-hero__sparks {
    transform: scale(0.8);
    transform-origin: top;
}

@media (min-width: 992px) {
    .h-hero--compact {
        padding-block: 3.5rem;
    }
}

@media (max-width: 575.98px) {
    .h-hero--compact {
        padding-block: 2rem;
    }
}
</style>