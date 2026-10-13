{{--
    Infinite logo marquee.

    A continuous, seamless horizontal loop for partner logos — the "wall" of
    backers most conference sites run. It is deliberately dependency-free: the
    motion is a single CSS keyframe on a duplicated track, so it cannot fight
    the templates' jQuery/Swiper runtimes for the same element, and it costs no
    main-thread work once started.

    How the seam is hidden: the set is repeated until one "half" is wider than
    any container it can land in, then that half is mirrored verbatim. Animating
    the track by exactly -50% lands on a frame identical to the first, so the
    loop has no jump, no gap and no reflow.

    Progressive by construction. With CSS blocked, JavaScript off, or
    `prefers-reduced-motion: reduce`, the same markup renders as a scrollable row
    of logos that simply sits still — the partners never disappear behind the
    decoration. The mirrored half is hidden from assistive tech, and a decorative
    marquee (no `label`) is removed from the tab order entirely, because a
    continuously moving target is not something a keyboard user can reach.

    Props
      items   : list of ['src', 'name', 'url'?, 'class'?, 'style'?]. A missing
                `src` renders `name` as text, so a partner with no artwork still
                shows up.
      speed   : seconds for one full loop. Higher is slower.
      reverse : scroll the other way — use it to counter-scroll a second row.
      label   : accessible group name. Omit for a purely decorative band.
--}}
@props([
    'items' => [],
    'speed' => 45,
    'reverse' => false,
    'label' => null,
])

@php
    $items = collect($items)->values();

    if ($items->isEmpty()) {
        return;
    }

    /* Repeat to a comfortable width, then mirror it. Eight is enough to outrun
       the widest container on the site; a longer real list is used as-is. */
    $half = collect();
    $cursor = 0;
    $target = max(8, $items->count());

    while ($half->count() < $target) {
        $half->push($items[$cursor % $items->count()]);
        $cursor++;
    }

    $track = $half->concat($half);
    $halfCount = $half->count();
@endphp

<div @class(['lm-marquee', 'is-reverse' => $reverse])
     style="--lm-speed: {{ (int) $speed }}s"
     @if ($label) role="group" aria-label="{{ $label }}" @else aria-hidden="true" @endif>
    <ul class="lm-marquee__track">
        @foreach ($track as $i => $item)
            @php $clone = $i >= $halfCount; @endphp
            <li class="lm-marquee__item" @if ($label && $clone) aria-hidden="true" @endif>
                @if (! empty($item['url']))
                    <a class="lm-marquee__card"
                       href="{{ $item['url'] }}"
                       rel="noopener noreferrer sponsored"
                       target="_blank"
                       title="{{ $item['name'] ?? '' }}"
                       @if (! $label || $clone) tabindex="-1" @endif>
                @else
                    <span class="lm-marquee__card">
                @endif

                    @if (! empty($item['src']))
                        <img class="lm-marquee__img {{ $item['class'] ?? '' }}"
                             src="{{ $item['src'] }}"
                             alt="{{ $item['name'] ?? '' }}"
                             style="{{ $item['style'] ?? '' }}"
                             loading="lazy"
                             decoding="async">
                    @else
                        <span class="lm-marquee__name">{{ $item['name'] ?? '' }}</span>
                    @endif

                @if (! empty($item['url']))
                    </a>
                @else
                    </span>
                @endif
            </li>
        @endforeach
    </ul>
</div>
