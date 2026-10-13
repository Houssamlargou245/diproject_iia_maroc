{{--
    Partner logo slider.

    A Swiper-powered loop for partner logos — infinite wrap, arrow controls and
    touch/drag. The order is shuffled on every render, so the wall never reads
    the same way twice.

    Progressive by construction. Swiper is loaded globally in the layout, but if
    it (or JS entirely) is missing the `.is-ready` hook is never added and the
    same markup degrades to a scrollable row of logos — nothing disappears
    behind the decoration. Reduced-motion drops the autoplay but keeps the
    arrows and drag.

    Props
      items : list of ['src', 'name', 'url'?, 'class'?, 'style'?]. A missing
              `src` renders `name` as text, so a partner with no artwork still
              shows up.
      speed : autoplay delay in milliseconds between two cards. Higher is
              slower. Ignored under `prefers-reduced-motion`.
      label : accessible group name.
--}}
@props([
    'items' => [],
    'speed' => 4500,
    'label' => null,
])

@php
    /* Randomised each render. On a wall of backers nobody memorises positions,
       so a fresh order is free variety — and no partner is permanently buried. */
    $items = collect($items)->shuffle()->values();

    if ($items->isEmpty()) {
        return;
    }

    /* A loop needs more slides than fit on screen; repeat the set until a
       comfortable width, so wrap-around never exposes a gap. */
    $loop = collect();
    $cursor = 0;
    $target = max(10, $items->count());

    while ($loop->count() < $target) {
        $loop->push($items[$cursor % $items->count()]);
        $cursor++;
    }
@endphp

<div class="lm-swiper"
     dir="ltr"
     data-logo-marquee
     data-logo-speed="{{ (int) $speed }}"
     @if ($label) role="group" aria-label="{{ $label }}" @endif>
    <div class="swiper lm-swiper__viewport">
        <div class="swiper-wrapper">
            @foreach ($loop as $item)
                <div class="swiper-slide lm-swiper__slide">
                    @if (! empty($item['url']))
                        <a class="lm-swiper__card"
                           href="{{ $item['url'] }}"
                           rel="noopener noreferrer sponsored"
                           target="_blank"
                           title="{{ $item['name'] ?? '' }}">
                    @else
                        <span class="lm-swiper__card">
                    @endif

                        @if (! empty($item['src']))
                            <img class="lm-swiper__img {{ $item['class'] ?? '' }}"
                                 src="{{ $item['src'] }}"
                                 alt="{{ $item['name'] ?? '' }}"
                                 style="{{ $item['style'] ?? '' }}"
                                 loading="lazy"
                                 decoding="async">
                        @else
                            <span class="lm-swiper__name">{{ $item['name'] ?? '' }}</span>
                        @endif

                    @if (! empty($item['url']))
                        </a>
                    @else
                        </span>
                    @endif
                </div>
            @endforeach
        </div>
    </div>

    <button type="button"
            class="lm-swiper__nav lm-swiper__nav--prev"
            data-logo-prev
            aria-label="@lang('action.previous')">
        <i class="fas fa-chevron-left" aria-hidden="true"></i>
    </button>

    <button type="button"
            class="lm-swiper__nav lm-swiper__nav--next"
            data-logo-next
            aria-label="@lang('action.next')">
        <i class="fas fa-chevron-right" aria-hidden="true"></i>
    </button>
</div>
