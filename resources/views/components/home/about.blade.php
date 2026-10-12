    {{--
        Why this conference exists.

        The argument the whole page is making, so it gets the one band where the
        surface goes dark and the copy runs to two full paragraphs.

        The collage leads in DOM order as well as visual order. That is a
        deliberate reversal of the usual "copy first" rule, and it costs nothing
        in reading order: the section is labelled by its own heading, the figure
        carries alt text, and on a phone the picture above the words is the order
        the eye wants anyway. Keeping the collage first is also what lets the grid
        mirror with `grid-template-columns` alone — copy first would need an
        `order`, and `order` is physical in exactly the way this codebase avoids.

        Both photographs come from config('brand.homepage.*'), so dropping the real
        ones in is a config edit rather than a template edit.
    --}}
    @props(['edition'])

    @php
    $main = (array) config('brand.homepage.about_main');
    $inset = (array) config('brand.homepage.about_inset');
    $year = $edition->year;
    @endphp

    <section class="h-why" aria-labelledby="why-title">
        <div class="container">
            <div class="h-why__grid">

                <div class="h-why__figure">
                    @if (! empty($main['src']))
                    <img class="h-why__photo"
                        src="{{ asset($main['src']) }}"
                        width="{{ $main['width'] ?? 1200 }}"
                        height="{{ $main['height'] ?? 900 }}"
                        alt="{{ __('home.landing.why.image_alt') }}"
                        loading="lazy"
                        decoding="async">
                    @endif

                    {{-- The overlapping inset. A <figure>/<figcaption> rather than a
                        div with text in it, so the caption inside the image is a
                        caption and not a stray paragraph a screen reader reads
                        between the two photographs. --}}
                    @if (! empty($inset['src']))
                    <figure class="h-why__inset">
                        <img src="{{ asset($inset['src']) }}"
                            width="{{ $inset['width'] ?? 800 }}"
                            height="{{ $inset['height'] ?? 600 }}"
                            alt="{{ __('home.landing.why.inset_alt') }}"
                            loading="lazy"
                            decoding="async">
                        <figcaption>
                            <span class="h-why__inset-title">
                                @lang('home.landing.why.inset_title', ['year' => $year])
                            </span>
                            <span class="h-why__inset-sub">
                                @lang('home.landing.why.inset_subtitle')
                            </span>
                        </figcaption>
                    </figure>
                    @endif
                </div>

                <div class="h-why__body">
                    <h2 class="h-why__eyebrow">@lang('home.landing.why.eyebrow')</h2>

                    <h2 id="why-title" class="h-why__title">
                        @lang('home.landing.why.title')
                    </h2>

                    {{-- Justified, as the reference sets it. `text-wrap: pretty` on
                        top keeps the last line of each paragraph from being a
                        single stretched word, which is the usual cost of
                        justification and the reason it is usually left off. --}}
                    <p class="h-why__para">
                        @lang('home.landing.why.lede', ['year' => $year])
                    </p>
                    <p class="h-why__para">
                        @lang('home.landing.why.body')
                    </p>
                </div>

            </div>
        </div>
    </section>
<style>
    /* French / LTR only — Arabic keeps its existing rules. */
    :root:not([dir="rtl"]) .h-why__figure {
        --nw: 52%;                          /* notch width (where the inset sits) */
        --nh: clamp(9rem, 19vw, 13.5rem);   /* notch height */

        padding-block-end: 3.5rem;          /* room for the inset to hang below the photo */
        position: relative;
    }

    /* Photo with the bottom-left corner carved out:
       a full-width top block + a right block reaching the bottom. */
    :root:not([dir="rtl"]) .h-why__photo {

   
        /* Position second block to the right end (100% 0) so the left side is cut out */
        -webkit-mask-position: 0 0, 100% 0;
                mask-position: 0 0, 100% 0;
        -webkit-mask-size: 100% calc(100% - var(--nh)), calc(100% - var(--nw)) 100%;
                mask-size: 100% calc(100% - var(--nh)), calc(100% - var(--nw)) 100%;
    }

    /* Inset card sits in the carved corner, bottom-left. */
    :root:not([dir="rtl"]) .h-why__inset {
        inset-block-end: 0;
        inset-inline-start: 0;
        inset-inline-end: auto;            /* cancels any right-anchored value */
        margin: 0;
        position: absolute;
        width: calc(var(--nw) - 1.25rem);   /* leaves a gap to the photo edge */
    }
</style>