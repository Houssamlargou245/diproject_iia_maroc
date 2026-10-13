{{--
    Primary navigation, rendered twice from one list.

    `variant` decides the element and the classes, nothing else: "bar" is the
    inline row in the header, "drawer" is the same links stacked in the mobile
    panel. Both render from the list below, so the two can never drift apart —
    which is the whole failure mode of a template with a separate hardcoded
    mobile menu, and the reason the 2024 build's phone menu had lost a link.

    Active state comes from `request()->routeIs()`, which asks the router which
    named route actually matched. The 2024 build searched REQUEST_URI for each
    page's own filename; that cannot work here, because this app has two URLs
    per page (`/programme` and `/fr/programme`), and a substring test marks the
    wrong item on both.

    `aria-current="page"` rides alongside the class so the highlight survives a
    high-contrast mode and is announced by a screen reader — the class alone
    carries no meaning to either.
      ['route' => 'venue', 'label' => __('nav.venue')],
              ['route' => 'speakers', 'label' => __('nav.speakers')],
--}}
@php
    $variant = $variant ?? 'bar';
    $isBar = $variant === 'bar';

    $items = array_values(array_filter([
        ['route' => 'home', 'label' => __('nav.home')],
        ['route' => 'presentation', 'label' => __('nav.presentation')],
              ['route' => 'programme', 'label' => __('nav.programme')],
        ['route' => 'sponsors', 'label' => __('nav.sponsors')],
  

        ['route' => 'pricing', 'label' => __('nav.pricing')],
      

        ['route' => 'contact', 'label' => __('nav.contact')],
    ], static fn (array $item): bool => Route::has($item['route'])));
@endphp

{{-- `nav` on the bar is already applied by the wrapper in the header; the
     drawer wraps this in its own dialog, so a second landmark there would be a
     duplicate rather than a useful one. --}}
<ul @class([
    'd-nav' => $isBar,
    'd-drawer__list' => ! $isBar,
])>
    @foreach ($items as $item)
        @php($isCurrent = request()->routeIs($item['route'].'*') || request()->routeIs($item['route']))

        <li>
            <a href="{{ route($item['route']) }}"
                   style="color:#2a1f6es"
               class="{{ $isBar ? 'd-nav__link' : '' }}"
               @if ($isCurrent) aria-current="page" @endif>
                {{ $item['label'] }}
            </a>
        </li>
    @endforeach

    {{-- The archive lives in the drawer only.

         On the bar it would be a ninth item competing with the eight real pages
         for a row that is already tight, and it is something nobody arrives
         looking for. In the drawer it costs one line and is where a returning
         delegate looks for last year's programme. --}}
    @unless ($isBar)
        @if (Route::has('archive'))
            <!-- <li>
                <a href="{{ route('archive') }}"
                style="color:wheat"
                   @if (request()->routeIs('archive*')) aria-current="page" @endif>
                    {{ __('nav.archive') }}
                </a>
            </li> -->
        @endif
    @endunless
</ul>
