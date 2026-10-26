<!DOCTYPE html>
{{--
    The base layout.

    This is the 2024 site's own shell, rebuilt on the template's class names so
    the stylesheet in assets/css/style.css applies as written. Three things here
    are load-bearing rather than cosmetic:

    1. `dir` and `lang` come from the resolved Locale, not from a hardcoded
       string, and `rtl` is put on <body> as well because the template's own
       RTL rules are all scoped to `.rtl` rather than to `[dir=rtl]`. Getting
       either wrong leaves a page that reads left-to-right with the text ordered
       right-to-left, which is the exact failure the brief asks to avoid.

    2. The language switcher posts the current URL back to the server rather
       than guessing a counterpart path in JavaScript, so switching works with
       scripting disabled and the resulting URL is real.

    3. The stylesheets are linked from /assets rather than compiled by Vite.
       Bootstrap 5's reboot and Tailwind's preflight both reset the same
       properties, and loading both makes the rendered result depend on which
       stylesheet wins a tie — which is not a thing worth discovering in a
       browser. The template is the design; nothing else competes with it.
--}}
<html lang="{{ $currentLocale->value }}"
      dir="{{ $currentLocale->direction() }}"
      class="no-js">
<head>
    <meta charset="utf-8">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', $currentEdition?->titleIn($currentLocale) ?? __('site.site_name'))</title>
    <meta name="description" content="@yield('description', __('meta.description', ['year' => $currentEdition?->year ?? date('Y')]))">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">

    {{-- The three language variants are the same page, and the default language —
         Arabic — is the unprefixed, canonical one.

         The alternates are generated from the current route's canonical name
         rather than from url()->current(), because the current URL is by
         definition the wrong language for two of the three entries. The `.ar`
         suffix is stripped: it is there only to keep route:list readable, and
         passing it to route() would ask for a URL that does not exist.

         The default language's alternate has to name the *unprefixed* route
         explicitly. `route('contact', ['locale' => 'ar'])` would emit
         /ar/contact, which is not an address on this site — and worse, on a
         prefixed page `URL::defaults()` would win over an empty value and
         produce /fr/contact, i.e. the hreflang would claim the French page is
         the Arabic one. Naming the unprefixed route sidesteps both.

         Skipped on POST, where there is no GET address for the page and a
         referer-derived URL would be a guess. --}}
    @php
        $currentRouteName = request()->route()?->getName();
        $defaultSuffix = '.'.\App\Enums\Locale::default()->value;
        $canonicalRouteName = $currentRouteName ? str_ends_with($currentRouteName, $defaultSuffix)
            ? substr($currentRouteName, 0, -\strlen($defaultSuffix))
            : $currentRouteName
            : null;
        $unprefixedRouteName = $canonicalRouteName ? $canonicalRouteName.$defaultSuffix : null;

        // The current route's own parameters, minus the locale.
        //
        // A translated page is the same page in another language, so the
        // alternates have to carry whatever identifies *which* page it is — an
        // order id, an edition year. Without this, a route with a parameter
        // generated `route('orders.show')`, which threw "Missing required
        // parameter" and took the whole page down with it. The locale is dropped
        // because the unprefixed route has no such segment: passing it would
        // silently append `?locale=fr` to the Arabic address.
        $routeParameters = array_diff_key(
            request()->route()?->parameters() ?? [],
            ['locale' => null],
        );
    @endphp
    @if ($currentEdition && $canonicalRouteName && request()->isMethod('GET'))
        <link rel="canonical" href="{{ url()->current() }}">

        {{-- `x-default` points at the unprefixed address: the language-neutral
             entry search engines fall back to when a visitor's language matches
             none of the alternatives. --}}
        @if (Route::has($unprefixedRouteName))
            <link rel="alternate" hreflang="x-default" href="{{ route($unprefixedRouteName, $routeParameters) }}">
        @endif

        @foreach (array_keys($availableLocales ?? []) as $code)
            @php
                $isDefaultLocale = $code === \App\Enums\Locale::default()->value;
                $alternateRoute = $isDefaultLocale ? $unprefixedRouteName : $canonicalRouteName;
                $alternateParameters = $isDefaultLocale
                    ? $routeParameters
                    : ['locale' => $code] + $routeParameters;
            @endphp
            @if ($code !== $currentLocale->value && Route::has($alternateRoute))
                <link rel="alternate" hreflang="{{ $code }}"
                      href="{{ route($alternateRoute, $alternateParameters) }}">
            @endif
        @endforeach
    @endif

    {{-- Favicons, carried over from the 2024 build. --}}
    <link rel="shortcut icon" type="image/x-icon" href="{{ asset('assets/images/favicon/favicon.ico') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('assets/images/favicon/apple-touch-icon.png') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('assets/images/favicon/favicon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('assets/images/favicon/favicon-16x16.png') }}">

    {{-- ------------------------------------------------------------------
        Web fonts.

        Cairo for Arabic, Archivo for Latin display. Cairo is loaded rather
        than one of the bundled faces because the bundled Arabic faces are
        licensed for display use and are weak below 20px: at body size their
        stroke weight gives way and a paragraph reads as grey. Cairo was
        designed for exactly this range. AvenirArabic and THESANSARABIC stay
        in the stack as local fallbacks, so the page is still readable if the
        CDN is blocked — which matters on a slow connection, not only an offline
        one.

        Aptos is the brand's body face but ships with Microsoft 365 and cannot
        be redistributed, so it is referenced by name in `--f-body` and is not
        requested here. A visitor without Aptos installed resolves to Cairo or
        the bundled Arabic face; that is a documented limitation, not a bug.

        Archivo IS requested, in both branches, because the 2026 brand sets
        Latin headings in Archivo Bold and an Arabic page still contains Latin
        headings — the brand wordmark, the edition year, "ARABCIA", the Roman
        numerals in the programme. It is also loaded on RTL pages for that same
        reason: font fallback in `--f-title` is per-character, so a Latin run
        inside an Arabic heading picks up Archivo without any markup. Loading it
        only in the LTR branch would leave every Latin heading on an Arabic
        page — the default language of this site — silently falling back.

        Only Cairo is requested conditionally, on Arabic pages, where it is
        actually used. `display=swap` means text paints immediately in the
        fallback and swaps when the face arrives, rather than showing an
        invisible page for the length of the request.
    ------------------------------------------------------------------ --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    {{-- Archivo, the brand display face: 400/600/700 so bold headings are a real
         700 weight rather than a synthesised one, which browsers render by
         smearing the 400 and it reads muddy at heading sizes. --}}
    <link rel="stylesheet"
          href="https://fonts.googleapis.com/css2?family=Archivo:wght@400;600;700&display=swap">

    @if ($isRtl ?? false)
        <link rel="stylesheet"
              href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800&display=swap">
    @endif

    {{-- Icon fonts: Font Awesome 5 and the template's own flaticon set. --}}
    <link rel="stylesheet" href="{{ asset('assets/css/plugins/all.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/plugins/flaticon.css') }}">

    {{-- Vendor CSS. --}}
    <link rel="stylesheet" href="{{ asset('assets/css/plugins/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/plugins/swiper-bundle.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/plugins/aos.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/plugins/magnific-popup.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/plugins/lightbox.min.css') }}">

    {{-- The design itself, then the RTL pass, then the application layer. --}}
    <link rel="stylesheet" href="{{ asset('assets/css/style.css') }}">
    @if ($isRtl ?? false)
        <link rel="stylesheet" href="{{ asset('assets/css/style-rtl.css') }}">
    @endif
    <link rel="stylesheet" href="{{ asset('assets/css/app.css') }}">

    {{-- The experience layer, last of all: motion, depth and light. It composes
         on top of the design rather than replacing it, and every rule that moves
         something respects prefers-reduced-motion. --}}
    <link rel="stylesheet" href="{{ asset('assets/css/ux.css') }}">

    {{-- The design system, after everything.

         Order is the whole architecture here: style.css is the 2024 template,
         app.css and ux.css are the layers built on it, and design.css is the
         layer that decides how the site looks. It goes last so its tokens and
         its component rules win on equal specificity without needing `!important`
         anywhere — which is what makes the site restyle from one file, and what
         keeps a template rule from quietly winning again after a later edit. --}}
    <link rel="stylesheet" href="{{ asset('assets/css/design.css') }}">

    {{-- The one value the stylesheet cannot know for itself.

         The landing page's geometric motif is a file on disk whose path lives
         in config/brand.php. Printing it once as a custom property means every
         rule in design.css can reach it with `var(--h-pattern)` and none of the
         templates has to inline a background-image; changing the artwork is a
         config edit, not a find-and-replace across the views. --}}
    <style>
        :root { --h-pattern: url('{{ \App\Support\Brand::pattern('modules') }}'); }
    </style>

    @stack('head')
</head>
{{-- A page pushes here to mark itself printable — the invoice does, so the
     print stylesheet can drop the site chrome and keep the document. See the
     print block at the foot of ux.css. --}}
<body class="{{ ($isRtl ?? false) ? 'rtl' : '' }} @stack('body-class')">

{{-- Skip link. The first focusable thing on the page, so a keyboard user can
     jump the navigation and the header in one keystroke. It is visually hidden
     until focused — `visually-hidden-focusable` rather than `display: none`,
     because an element with `display: none` cannot be focused at all. --}}
<a href="#main" class="skip-link visually-hidden-focusable">@lang('nav.skip_to_content')</a>

{{-- Scroll progress. Sits above everything, including the header, and is driven
     by ux.js through a custom property. It has no content of its own, so it is
     hidden from assistive technology rather than announced on every scroll. --}}
<div class="ux-progress" role="presentation" aria-hidden="true"></div>

<div class="main-wrapper">

    @include('partials.header')

    <main id="main" tabindex="-1">
        @include('partials.flash')

        @yield('content')
    </main>

    @include('partials.footer')

    {{-- Back to top. Fixed to the viewport corner, mirrored by
         `inset-inline-end` in design.css, and given a real name here — an
         unlabelled circle around a ring is announced as "graphic" and tells a
         screen reader user nothing about what pressing it does. --}}
    <button type="button" class="progress-wrap" data-back-to-top>
        <svg class="progress-circle" width="22" height="22" viewBox="0 0 100 100" aria-hidden="true">
            <path d="M50,10 a40,40 0 1,1 -0.01,0" transform="rotate(-90 150 150)"/>
        </svg>
        <span class="visually-hidden">@lang('action.back_to_top')</span>
    </button>

    <x-cookie-banner />

</div>

{{-- Vendor JS. jQuery first: the template's own scripts and the plugins all
     assume it, and several use the 1.x API. --}}
<script src="{{ asset('assets/js/vendor/jquery-1.12.4.min.js') }}"></script>
<script src="{{ asset('assets/js/vendor/modernizr-3.11.2.min.js') }}"></script>
<script src="{{ asset('assets/js/plugins/popper.min.js') }}"></script>
<script src="{{ asset('assets/js/plugins/bootstrap.min.js') }}"></script>
<script src="{{ asset('assets/js/plugins/swiper-bundle.min.js') }}"></script>
<script src="{{ asset('assets/js/plugins/aos.js') }}"></script>
<script src="{{ asset('assets/js/plugins/waypoints.min.js') }}"></script>
<script src="{{ asset('assets/js/plugins/back-to-top.js') }}"></script>
<script src="{{ asset('assets/js/plugins/jquery.counterup.min.js') }}"></script>
<script src="{{ asset('assets/js/plugins/appear.min.js') }}"></script>
<script src="{{ asset('assets/js/plugins/jquery.magnific-popup.min.js') }}"></script>
<script src="{{ asset('assets/js/plugins/lightbox.min.js') }}"></script>
<script src="{{ asset('assets/js/main.js') }}"></script>

{{-- The experience layer. Vanilla, dependency-free, and strictly additive: if
     it fails to load the site is unchanged. It is loaded last so it sees the
     final DOM and so nothing it adds is removed by a plugin that assumes it
     owns the page. --}}
<script src="{{ asset('assets/js/ux.js') }}" defer></script>

{{-- The interaction layer for the rebuilt shell and landing page: sticky
     header, mobile drawer, countdown, count-up, video button. Deferred and
     after ux.js so the two initialise in order. Each feature is wrapped
     independently, so one failure cannot take the rest with it. --}}
<script src="{{ asset('assets/js/design.js') }}" defer></script>

@stack('scripts')
</body>
</html>
