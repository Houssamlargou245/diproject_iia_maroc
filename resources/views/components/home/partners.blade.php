{{--
    Who is backing this.

    With confirmed sponsors on file, their logos run in an infinite marquee.
    With none — which is the state today — the configured placeholder marks run
    instead, in a single row. Both branches render through the same
    `x-logo-marquee`, so publishing the first real sponsor is a data change, not
    a template change.

    Logos sit on white plates inside the marquee: partner marks are usually dark
    line art, and dark line art on a tinted section disappears.
--}}
@props(['sponsors'])

@php
    $sponsors = $sponsors ?? collect();
    $fallback = collect((array) config('brand.homepage.partners_fallback'));

    /* Confirmed sponsors, shaped into marquee items. A sponsor with no artwork
       keeps its name, which the marquee renders as text rather than a broken
       image. */
    $realSponsors = $sponsors->map(static function ($sponsor): array {
        $path = $sponsor->isTopTier()
            ? ($sponsor->logo_path ?: $sponsor->logo_mono_path)
            : ($sponsor->logo_mono_path ?: $sponsor->logo_path);

        return [
            'src'  => $path
                ? (\Illuminate\Support\Str::startsWith($path, 'http') ? $path : asset('storage/'.$path))
                : null,
            'name' => $sponsor->name,
            'url'  => $sponsor->website_url,
        ];
    })->values();

    /* The placeholder marks, shaped the same way and run as one row. */
    $fallbackItems = $fallback->map(static fn (array $partner): array => [
        'src'   => asset($partner['src']),
        'name'  => $partner['name'],
        'class' => $partner['class'] ?? null,
        'style' => $partner['style'] ?? null,
    ])->values();
@endphp

<section class="h-sponsors" aria-labelledby="sponsors-title">
    <div class="container">

        <h2 id="sponsors-title" class="h-sponsors__title">@lang('home.partners_title')</h2>

        @if ($realSponsors->isNotEmpty())
            <x-logo-marquee :items="$realSponsors" :label="__('home.partners_title')" :speed="46" />

            <p class="h-sponsors__cta">
                <a href="{{ route('sponsors') }}" class="h-btn h-btn--outline h-btn--sm">
                    @lang('sponsoring.become_partner')
                </a>
            </p>
        @else
            <x-logo-marquee :items="$fallbackItems" :speed="50" />
        @endif

    </div>
</section>
