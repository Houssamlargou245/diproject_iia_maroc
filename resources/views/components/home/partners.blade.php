{{--
    Who is backing this.

    Partners are grouped by tier (platinum / gold / silver), each tier with its
    own pill label and a row of logo cards. With no confirmed sponsors, the
    configured placeholder marks run through the same markup, so publishing the
    first real sponsor is a data change, not a template change.

    Logos sit on white cards: partner marks are usually dark line art, and dark
    line art on a tinted section disappears.
--}}
@props(['sponsors'])

@php
    $sponsors = $sponsors ?? collect();

    $tierLabels = [
        'platinum' => __('sponsoring.tier_platinum'), // "Platinum Partner"
        'gold'     => __('sponsoring.tier_gold'),     // "Gold Partners"
        'silver'   => __('sponsoring.tier_silver'),   // "Silver Partners"
    ];

    $toSrc = static fn (?string $path): ?string => $path
        ? (\Illuminate\Support\Str::startsWith($path, 'http') ? $path : asset('storage/'.$path))
        : null;

    /* Confirmed sponsors, shaped into card items. A sponsor with no artwork
       keeps its name, rendered as text rather than a broken image. */
    $realItems = $sponsors->map(function ($sponsor) use ($toSrc): array {
        $path = $sponsor->isTopTier()
            ? ($sponsor->logo_path ?: $sponsor->logo_mono_path)
            : ($sponsor->logo_mono_path ?: $sponsor->logo_path);

        return [
            'tier'  => $sponsor->tier,
            'src'   => $toSrc($path),
            'name'  => $sponsor->name,
            'url'   => $sponsor->website_url,
            'class' => null,
            'style' => null,
        ];
    });

    /* Placeholder marks, shaped the same way. */
    $fallbackItems = collect((array) config('brand.homepage.partners_fallback'))
        ->map(static fn (array $partner): array => [
            'tier'  => $partner['tier'] ?? 'silver',
            'src'   => asset($partner['src']),
            'name'  => $partner['name'],
            'url'   => null,
            'class' => $partner['class'] ?? null,
            'style' => $partner['style'] ?? null,
        ]);

    $hasReal = $realItems->isNotEmpty();
    $groups  = ($hasReal ? $realItems : $fallbackItems)->groupBy('tier');
@endphp

<section class="h-sponsors" aria-labelledby="sponsors-title" style="background-image: url('{{ asset('assets/parteners/slide0.png') }}');">
    <div class="container">

        <h2 id="sponsors-title" class="h-sponsors__title">@lang('home.partners_title')</h2>

        @foreach ($tierLabels as $tier => $label)
            @continue(! $groups->has($tier))

            <div class="h-sponsors__tier h-sponsors__tier--{{ $tier }}">
                <span class="h-sponsors__pill h-sponsors__pill--{{ $tier }}">{{ $label }}</span>

                <ul class="h-sponsors__row" role="list">
                    @foreach ($groups[$tier] as $item)
                        <li class="h-sponsors__card">
                            @if ($item['url'])
                                <a href="{{ $item['url'] }}" target="_blank" rel="noopener sponsored"
                                   class="h-sponsors__link" aria-label="{{ $item['name'] }}">
                            @else
                                <span class="h-sponsors__link">
                            @endif

                            @if ($item['src'])
                                <img src="{{ $item['src'] }}"
                                     alt="{{ $item['name'] }}"
                                     loading="lazy"
                                     class="h-sponsors__logo {{ $item['class'] }}"
                                     @if ($item['style']) style="{{ $item['style'] }}" @endif>
                            @else
                                <span class="h-sponsors__name">{{ $item['name'] }}</span>
                            @endif

                            @if ($item['url'])
                                </a>
                            @else
                                </span>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </div>
        @endforeach

        @if ($hasReal)
            <p class="h-sponsors__cta">
                <a href="{{ route('sponsors') }}" class="h-btn h-btn--outline h-btn--sm">
                    @lang('sponsoring.become_partner')
                </a>
            </p>
        @endif

    </div>
</section>
<style>
    .h-sponsors {
    padding: 72px 0 80px;
   background-image: url('/assets/parteners/slide0.png');
     background-image: url('/assets/parteners/slide0.png');
    background-size: cover;
    background-position: center;
    background-repeat: no-repeat;
}

.h-sponsors__title {
    margin: 0 0 56px;
    text-align: center;
    font-size: clamp(1.75rem, 3vw, 2.25rem);
    font-weight: 800;
    letter-spacing: .02em;
    text-transform: uppercase;
    color: #2a1a8f;
}

/* ---- Tier blocks ---- */
.h-sponsors__tier {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 22px;
    margin-bottom: 36px;
}

/* ---- Pills ---- */
.h-sponsors__pill {
    padding: 6px 22px;
    border-radius: 999px;
    font-size: .72rem;
    font-weight: 700;
    letter-spacing: .22em;
    text-transform: uppercase;
    color: #fff;
    box-shadow: 0 4px 12px rgba(30, 20, 120, .18);
}
.h-sponsors__pill--platinum { background: linear-gradient(90deg, #2a1a8f, #6a4df0); }
.h-sponsors__pill--gold     { background: linear-gradient(90deg, #a87420, #e8c26a 50%, #a87420); }
.h-sponsors__pill--silver   { background: linear-gradient(90deg, #7b7b8c, #c4c4d0 50%, #7b7b8c); }

/* ---- Rows ---- */
.h-sponsors__row {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    gap: 14px;
    width: 100%;
    margin: 0;
    padding: 0;
    list-style: none;
}

/* ---- White logo cards ---- */
.h-sponsors__card {
    display: flex;
    background: #fff;
    border: 1px solid #e4e4ef;
    border-radius: 16px;
    box-shadow: 0 6px 18px rgba(60, 50, 140, .07);
    transition: transform .2s ease, box-shadow .2s ease;
}
.h-sponsors__card:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 24px rgba(60, 50, 140, .13);
}

.h-sponsors__link {
    display: flex;
    flex: 1;
    align-items: center;
    justify-content: center;
    padding: 16px 20px;
    color: inherit;
    text-decoration: none;
}

.h-sponsors__logo {
    display: block;
    max-width: 100%;
    max-height: 100%;
    object-fit: contain;
}

.h-sponsors__name {
    font-weight: 700;
    color: #2b2b3c;
    text-align: center;
}

/* Platinum: one wide card */
.h-sponsors__tier--platinum .h-sponsors__card { width: min(480px, 100%); height: 96px; }
.h-sponsors__tier--platinum .h-sponsors__logo { max-height: 56px; }

/* Gold: four across */
.h-sponsors__tier--gold .h-sponsors__row { max-width: 1100px; }
.h-sponsors__tier--gold .h-sponsors__card {
    flex: 1 1 220px;
    max-width: 300px;
    height: 98px;
}
.h-sponsors__tier--gold .h-sponsors__logo { max-height: 62px; }

/* Silver: five across, slightly smaller */
.h-sponsors__tier--silver .h-sponsors__row { max-width: 1100px; }
.h-sponsors__tier--silver .h-sponsors__card {
    flex: 1 1 170px;
    max-width: 230px;
    height: 92px;
}
.h-sponsors__tier--silver .h-sponsors__logo { max-height: 82px; }

.h-sponsors__cta { margin-top: 40px; text-align: center; }

@media (max-width: 640px) {
    .h-sponsors { padding: 48px 0; }
    .h-sponsors__title { margin-bottom: 36px; }
    .h-sponsors__tier--gold .h-sponsors__card,
    .h-sponsors__tier--silver .h-sponsors__card { flex-basis: calc(50% - 7px); max-width: none; }
}
[dir="rtl"] .h-sponsors__pill,
[dir="rtl"] .h-sponsors__title {
    letter-spacing: 0;
}
</style>