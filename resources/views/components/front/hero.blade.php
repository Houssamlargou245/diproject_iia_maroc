{{--
    The front-office hero.

    One band, used by every public page, and the same band the landing page
    opens with — same background, same brand pattern, same three marks, same
    bloom and sparks, same type scale. The page's own title is the only thing
    that differs.

    That is the point. A reader who lands on the programme from a search result
    and one who arrived from the landing page should not be able to tell from
    the first screen whether they are on the same site: the hero is the site's
    signature, and a second, quieter hero on the inner pages reads as a
    different website with a shared footer.

    It reuses the `h-hero` classes rather than defining `f-hero` ones. The
    landing page's markup is `components/home/hero.blade.php`, which is left
    exactly as it is — this is the same design expressed as a reusable
    component, not a second implementation of it.

    The three facts (when, where, which city) are the ones a delegate checks
    before anything else, and they come from the edition rather than from the
    page, so they read identically everywhere.

    Props are all optional: a page with nothing to say in the lede simply omits
    it rather than passing an empty string, and the band closes up.
--}}
@props([
    'title' => null,
    'eyebrow' => null,
    'lede' => null,
    'crumbs' => [],
    'facts' => [],
    'ctaLabel' => null,
    'ctaUrl' => null,
    'ctaUrlExternal' => false,
    'secondaryLabel' => null,
    'secondaryUrl' => null,
    'image' => 'assets/images/bg/price_bg.jpg',
])

@php
    /* Falls back to the globally shared edition, so a page whose controller
       does not pass one still gets a hero instead of an undefined variable. */
    $edition = $edition ?? $currentEdition ?? null;

    /* The landing page's own component is the source of truth for the marks and
       the lockup dimensions; reading them from it here means a brand refresh
       changes both in one place. */
    $lockup = \App\Support\Brand::logo();

    /* The landing page closes its three facts with the host city. Pages that use
       this component pass when and where only, so the city is appended here —
       once — to keep the two hero bands telling the same story. */
    $facts = (array) $facts;

    if (filled($edition?->city)) {
        $hasCity = false;

        foreach ($facts as $fact) {
            if (($fact['icon'] ?? null) === 'fa-city') {
                $hasCity = true;
                break;
            }
        }

        if (! $hasCity) {
            $facts[] = ['icon' => 'fa-city', 'label' => $edition->cityLine()];
        }
    }
@endphp

<section class="h-hero f-hero">

    <span class="h-hero__bloom" aria-hidden="true"></span>
    <span class="h-hero__sparks" aria-hidden="true"></span>

    <div class="container">
        <div class="h-hero__inner">

            {{-- The three marks, dark ink turned white rather than swapped for a
                 white variant: the brand ships one artwork per locale, and
                 `brightness(0) invert(1)` maps every pixel to white without
                 touching the file on disk. --}}
            <div class="h-hero__logos">
                <!-- <img src="{{ \App\Support\Brand::logoUrl() }}"
                     width="{{ $lockup['width'] }}"
                     height="{{ $lockup['height'] }}"
                     alt="{{ __('site.site_name') }}"
                     fetchpriority="high"
                     decoding="async"> -->

                <img src="{{ asset('assets/brand/arabic_itihad_logo.png') }}"
                     width="{{ $lockup['width'] }}"
                     height="{{ $lockup['height'] }}"
                     alt="{{ __('site.site_name') }}"
                     decoding="async">

                <img src="{{ asset('assets/brand/arabcia_logo.png') }}"
                     width="{{ $lockup['width'] }}"
                     height="{{ $lockup['height'] }}"
                     alt="{{ __('site.site_name') }}"
                     decoding="async">
            </div>

            {{-- The page's own name is the `h1`. The edition year rides under
                 it as the landing page does, so the two bands are visibly the
                 same band — it is the one place a repeated year earns its
                 place, because it is what tells the reader which event's
                 programme or map they are looking at. --}}
            <h1 class="h-hero__title">
                <span class="h-hero__name">{{ $title ?? $edition?->organiser }}</span>

                @if ($edition?->year)
                    <span class="h-hero__year">{{ $edition->year }}</span>
                @endif
            </h1>

            @if ($eyebrow)
                <p class="h-hero__kicker">{{ $eyebrow }}</p>
            @endif

            <!-- @if ($lede)
                <p class="h-hero__theme">{{ $lede }}</p>
            @endif -->

            {{-- Breadcrumbs, below the copy rather than above the title. A
                 breadcrumb above an `h1` is read before the reader knows what
                 page they are on; here it is the last thing in the band, which
                 is where it is useful — the way back. --}}
            @if ($crumbs !== [])
                <!-- <nav class="f-hero__crumbs" aria-label="{{ __('nav.breadcrumb') }}">
                    <ol>
                        @foreach ($crumbs as $label => $url)
                            <li>
                                @if ($url)
                                    <a href="{{ $url }}">{{ $label }}</a>
                                @else
                                    <span aria-current="page">{{ $label }}</span>
                                @endif
                            </li>
                        @endforeach
                    </ol>
                </nav> -->
            @endif

            @if ($facts !== [])
                <ul class="h-hero__meta">
                    @foreach ($facts as $fact)
               
                        <li>
                            <span class="h-hero__fact-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                     stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                                    @switch($fact['icon'])
                                        @case('fa-calendar-alt')
                                            <rect x="3.5" y="5" width="17" height="15.5" rx="2.5"></rect>
                                            <path d="M8 3v4M16 3v4M3.5 10h17"></path>
                                            @break

                                        @case('fa-map-marker-alt')
                                            <path d="M12 21.5s7-6.6 7-11.5a7 7 0 1 0-14 0c0 4.9 7 11.5 7 11.5Z"></path>
                                            <circle cx="12" cy="10" r="2.6"></circle>
                                            @break

                                        @case('fa-ticket-alt')
                                            <path d="M3.5 9V7.5A2.5 2.5 0 0 1 6 5h12a2.5 2.5 0 0 1 2.5 2.5V9a2.5 2.5 0 0 0 0 5v1.5A2.5 2.5 0 0 1 18 18H6a2.5 2.5 0 0 1-2.5-2.5V14a2.5 2.5 0 0 0 0-5Z"></path>
                                            <path d="M13 5.5v13" stroke-dasharray="2 2.5"></path>
                                            @break

                                        @case('fa-shield-alt')
                                            <path d="M12 21.5s7-3.2 7-9.4V5.6L12 3 5 5.6v6.5c0 6.2 7 9.4 7 9.4Z"></path>
                                            <path d="M12 12.2 8.6 8.8"></path>
                                            <path d="M12 3v9.2"></path>
                                            @break

                                        {{-- An address and a phone number. Without these they fell through to
                                             the default pin, so a page that
                                             passed one was announcing it with a
                                             map marker. --}}
                                        @case('fa-phone')
                                            <path d="M6.2 3.5h3l1.5 4-2 1.4a12 12 0 0 0 5.4 5.4l1.4-2 4 1.5v3a2 2 0 0 1-2.2 2A16.5 16.5 0 0 1 4.2 5.7a2 2 0 0 1 2-2.2Z"></path>
                                            @break

                                        @case('fa-envelope')
                                            <rect x="3" y="5.5" width="18" height="13" rx="2.5"></rect>
                                            <path d="m3.8 7.4 7.1 5.3a2 2 0 0 0 2.2 0l7.1-5.3"></path>
                                            @break

                                        @case('fa-city')
                                            <path d="M3.5 21h17M5.5 21V6.5L12 3l6.5 3.5V21"></path>
                                            <path d="M9.5 10H11M13 10h1.5M9.5 13.5H11M13 13.5h1.5M10 21v-3.5h4V21"></path>
                                            @break

                                        @default
                                            <path d="M12 21.5s7-6.6 7-11.5a7 7 0 1 0-14 0c0 4.9 7 11.5 7 11.5Z"></path>
                                            <circle cx="12" cy="10" r="2.6"></circle>
                                    @endswitch
                                </svg>
                            </span>
                            <span>{{ $fact['label'] }}</span>
                        </li>
                    @endforeach
                </ul>
            @endif

            @if ($ctaUrl && $ctaLabel)
                <div class="h-hero__actions">
                    <a href="{{ $ctaUrl }}"
                       class="h-btn h-btn--solid"
                       @if ($ctaUrlExternal) rel="noopener noreferrer nofollow" target="_blank" @endif>
                        <span>{{ $ctaLabel }}</span>
                    </a>

                    @if ($secondaryUrl && $secondaryLabel)
                        <a href="{{ $secondaryUrl }}" class="h-btn h-btn--ghost">
                            <span>{{ $secondaryLabel }}</span>
                        </a>
                    @endif
                </div>
            @endif

        </div>
    </div>
</section>