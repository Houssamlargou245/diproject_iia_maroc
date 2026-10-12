@extends('layouts.app')

@section('title', __('venue.title'))
@section('description', $edition->introduction)

@section('content')

<div class="d-page d-page--venue">

    {{-- The venue page is the page a delegate opens on a phone, standing at a
         taxi rank, so "when, where, and how do I get there" is answered in the
         hero band itself rather than after a scroll. --}}
    <x-front.hero
        :title="__('venue.title')"
        :eyebrow="$edition->identityLabel()"
        :lede="__('venue.venue_lede', [
            'venue' => $edition->venueLine($locale),
            'city' => $edition->cityLine($locale),
        ])"
        :crumbs="[__('nav.venue') => null]"
        :facts="[
            ['icon' => 'fa-calendar-alt', 'label' => $edition->dateLine($locale)],
            ['icon' => 'fa-map-marker-alt',  'label' => $edition->venueLine($locale)],
        ]"
        :cta-label="$edition->mapUrl() ? __('venue.map_cta') : null"
        :cta-url="$edition->mapUrl()"
        :cta-url-external="(bool) $edition->mapUrl()"
        :secondary-label="__('programme.title')"
        :secondary-url="route('programme')"
        image="assets/images/bg/map_bg.png" />

    {{-- Venue details and map. --}}
    <section class="d-section" aria-labelledby="venue-heading">
        <div class="container">
            <div class="d-venue">

                <div class="ux-reveal">
                    <x-design.section-head
                        id="venue-heading"
                        :eyebrow="__('venue.venue_name')"
                        :title="$edition->venueLine($locale)"
                        :lede="$edition->venueLine($locale)" />

                    {{-- An <address> element, so a screen reader announces this as a
                         location rather than as three unrelated strings. The
                         attribution it implies is correct: this is the venue's own
                         postal address. --}}
                    @if ($edition->venue_address)
                        <address class="d-address">{!! nl2br(e($edition->venueAddressLine($locale))) !!}</address>
                    @endif

                    <ul class="d-facts">
                        <li>
                            <span class="d-facts__label">@lang('venue.date')</span>
                            <span class="d-facts__value">{{ $edition->dateLine($locale) }}</span>
                        </li>

                        <li>
                            <span class="d-facts__label">@lang('venue.city')</span>
                            <span class="d-facts__value">
                                {{ $edition->cityLine($locale) }}
                                <span class="d-facts__sub">{{ $edition->country_iso2 }}</span>
                            </span>
                        </li>

                        @if ($edition->contact_phone)
                            {{-- A real telephone link. A delegate reading this on a phone
                                 can tap it, which is the entire reason the number is here. --}}
                            <li>
                                <span class="d-facts__label">@lang('contact.phone')</span>
                                <span class="d-facts__value">
                                    <a href="tel:{{ preg_replace('/[^0-9+]/', '', $edition->contact_phone) }}" dir="ltr">
                                        {{ $edition->contact_phone }}
                                    </a>
                                </span>
                            </li>
                        @endif
                    </ul>
                </div>

                <div class="ux-reveal">
                    @if ($edition->mapUrl())
                        {{-- The map is a link, not an embedded iframe. A third-party
                             iframe costs a cookie banner and several hundred
                             kilobytes before the visitor can read the address they
                             came for, and it leaks the referrer off-site. The link
                             offers the same destination and neither cost.

                             The destination is derived from the stored coordinates
                             rather than read from `venue_map_url`: a hand-typed map
                             URL and a lat/lng pair drift apart, and the map is the
                             one thing on this site a delegate will act on. --}}
                        <a href="{{ $edition->mapUrl() }}"
                           rel="noopener noreferrer nofollow"
                           target="_blank"
                           class="d-venue__map"
                           aria-label="{{ __('venue.open_map') }}">
                            <img src="{{ asset('assets/images/bg/map_bg.png') }}"
                                 alt="{{ __('venue.map_title') }}"
                                 width="720"
                                 height="480"
                                 loading="lazy"
                                 decoding="async">

                            <span class="d-venue__pin" aria-hidden="true">
                                <i class="fas fa-map-marker-alt"></i>
                            </span>

                            <span class="d-btn d-btn--primary d-venue__map-cta">
                                <span>@lang('venue.map_cta')</span>
                            </span>
                        </a>
                    @else
                        {{-- No map configured: say so, rather than showing an empty
                             frame that suggests one was meant to be there. --}}
                        <div class="d-empty">
                            <i class="fas fa-map-marker-alt d-empty__icon" aria-hidden="true"></i>
                            <p>@lang('state.not_available')</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </section>

    {{-- Rooms. Only rendered when the edition has any: an empty list here tells a
         visitor nothing at all about the venue. --}}
    @if ($rooms->isNotEmpty())
        <section class="d-section d-section--alt" aria-labelledby="rooms-heading">
            <div class="container">

                <x-design.section-head
                    id="rooms-heading"
                    :eyebrow="__('programme.room')"
                    :title="__('venue.rooms')"
                    :lede="__('venue.rooms_lede')"
                    align="center" />

                <ul class="d-cards" data-ux-stagger="60">
                    @foreach ($rooms as $room)
                        <li>
                            <div class="d-card d-card--hover d-feature">
                                <span class="d-feature__icon" aria-hidden="true">
                                    <i class="fas fa-door-open"></i>
                                </span>

                                <h3 class="d-feature__title">{{ $room->name }}</h3>

                                <p class="d-feature__tags">
                                    {{-- The code and the name are both shown: the code is what
                                         is printed on the programme, the name is what staff
                                         will say to you. --}}
                                    @if ($room->code)
                                        <span class="d-tag">{{ $room->code }}</span>
                                    @endif

                                    @if ($room->capacity || ! is_null($room->floor))
                                        @if ($room->capacity)
                                            <span class="d-tag">
                                                <i class="fas fa-users" aria-hidden="true"></i>
                                                {{ trans_choice('venue.capacity', $room->capacity, ['count' => $room->capacity]) }}
                                            </span>
                                        @endif

                                        @if (! is_null($room->floor))
                                            {{-- The level is stored as a number, so the wording is
                                                 translated here rather than baked into the row. --}}
                                            <span class="d-tag">
                                                <i class="fas fa-layer-group" aria-hidden="true"></i>
                                                {{ __('venue.floor', ['level' => $room->floor]) }}
                                            </span>
                                        @endif
                                    @endif
                                </p>
                            </div>
                        </li>
                    @endforeach
                </ul>
            </div>
        </section>
    @endif

    {{-- Practical information. The tiles are built from an array rather than
         written out three times, so adding a fourth (a WhatsApp line, a hotel
         partner) is one entry and not a copy-paste. --}}
    @php
        $contactTiles = array_values(array_filter([
            [
                'icon'  => 'fa-map-marker-alt',
                'label' => __('venue.venue_name'),
                'value' => $edition->venueLine($locale),
                'href'  => null,
            ],
            [
                'icon'  => 'fa-envelope-open-text',
                'label' => __('contact.email'),
                'value' => $edition->contact_email,
                'href'  => $edition->contact_email ? 'mailto:'.$edition->contact_email : null,
            ],
            [
                'icon'  => 'fa-phone',
                'label' => __('contact.phone'),
                'value' => $edition->contact_phone,
                'href'  => $edition->contact_phone
                    ? 'tel:'.preg_replace('/[^0-9+]/', '', $edition->contact_phone)
                    : null,
            ],
        ], fn (array $tile): bool => filled($tile['value'])));
    @endphp

    @if ($contactTiles !== [])
        <section class="d-section" aria-labelledby="practical-heading">
            <div class="container">

                <x-design.section-head
                    id="practical-heading"
                    :eyebrow="__('venue.practical')"
                    :title="__('venue.practical')"
                    :lede="__('venue.practical_lede')"
                    align="center" />

                <ul class="d-cards" data-ux-stagger="80">
                    @foreach ($contactTiles as $tile)
                        <li class="ux-reveal">
                            <div class="d-card d-card--hover d-contact-card">
                                <span class="d-feature__icon" aria-hidden="true">
                                    <i class="fas {{ $tile['icon'] }}"></i>
                                </span>

                                <span class="d-facts__label">{{ $tile['label'] }}</span>

                                {{-- `dir="ltr"` on the two machine-formatted values: an email
                                     address and a phone number are both reordered by the bidi
                                     algorithm on the Arabic page if left to it. --}}
                                <p class="d-contact-card__value"
                                   @if ($tile['href']) dir="ltr" @endif>
                                    @if ($tile['href'])
                                        <a href="{{ $tile['href'] }}">{{ $tile['value'] }}</a>
                                    @else
                                        {{ $tile['value'] }}
                                    @endif
                                </p>
                            </div>
                        </li>
                    @endforeach
                </ul>

                <p class="text-center mt-5">
                    <a href="{{ route('contact') }}" class="d-action">
                        @lang('contact.reach_us')
                        <i class="fas fa-arrow-right" aria-hidden="true"></i>
                    </a>
                </p>
            </div>
        </section>
    @endif

    <x-design.cta-band
        :title="__('pricing.title')"
        :text="__('pricing.currency_note')"
        :primary-label="$edition->registration_open ? __('pricing.register') : null"
        :primary-url="$edition->registration_open ? (auth()->check() ? route('pricing') : route('register')) : null"
        :secondary-label="__('nav.contact')"
        :secondary-url="route('contact')" />

</div>

@endsection
