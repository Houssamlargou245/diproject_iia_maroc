<?php

namespace App\Models;

use App\Casts\TranslatedList;
use App\Casts\TranslatedString;
use App\Casts\TranslationPayload;
use App\Enums\EditionStatus;
use App\Enums\Locale;
use Database\Factories\EditionFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * One edition of the conference.
 *
 * The 2024 build hardcoded 2024 into page titles, meta tags, PDFs and email
 * bodies, which is why the brief's "replace all mentions" row is a real
 * engineering task. Here, the year, venue, theme and dates live in this table
 * and every page reads from it, so the 2027 edition is a data insert.
 *
 * @property int $id
 * @property int $year
 * @property string $code
 * @property string $organiser
 * @property string $host_institute
 * @property EditionStatus $status
 * @property bool $is_current
 * @property bool $registration_open
 */
class Edition extends Model
{
    /** @use HasFactory<EditionFactory> */
    use HasFactory;

    /**
     * @var self|null memoised per request by {@see self::current()}
     */
    protected static ?self $current = null;

    protected $fillable = [
        'year', 'code', 'organiser', 'host_institute',
        'title', 'theme', 'introduction',
        'city', 'country_iso2', 'venue_name', 'venue_address',
        'venue_lat', 'venue_lng', 'venue_map_url',
        'starts_on', 'ends_on', 'languages', 'target_audience',
        'status', 'is_current', 'registration_open',
        'contact_email', 'contact_phone',
        'organiser_contact_name', 'organiser_contact_email',
        'host_contact_name', 'host_contact_email',
        'hero_image_path', 'logo_path', 'archive_note',
    ];

    protected function casts(): array
    {
        return [
            'title' => TranslatedString::class,
            'theme' => TranslatedString::class,
            'introduction' => TranslatedString::class,
            'target_audience' => TranslatedList::class,
            'languages' => 'array',
            'status' => EditionStatus::class,
            'is_current' => 'boolean',
            'registration_open' => 'boolean',
            'starts_on' => 'date',
            'ends_on' => 'date',
            'venue_lat' => 'float',
            'venue_lng' => 'float',
        ];
    }

    // --- Relations -------------------------------------------------------

    /** @return HasMany<Organisation, $this> */
    public function organisations(): HasMany
    {
        return $this->hasMany(Organisation::class);
    }

    /** @return HasMany<Track, $this> */
    public function tracks(): HasMany
    {
        return $this->hasMany(Track::class)->orderBy('sort_order');
    }

    /** @return HasMany<Room, $this> */
    public function rooms(): HasMany
    {
        return $this->hasMany(Room::class);
    }

    /** @return HasMany<Speaker, $this> */
    public function speakers(): HasMany
    {
        return $this->hasMany(Speaker::class);
    }

    /** @return HasMany<ConferenceSession, $this> */
    public function sessions(): HasMany
    {
        return $this->hasMany(ConferenceSession::class);
    }

    /** @return HasMany<Sponsor, $this> */
    public function sponsors(): HasMany
    {
        return $this->hasMany(Sponsor::class);
    }

    /** @return HasMany<TicketType, $this> */
    public function ticketTypes(): HasMany
    {
        return $this->hasMany(TicketType::class);
    }

    /** @return HasMany<Order, $this> */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /** @return HasMany<TeamMember, $this> */
    public function teamMembers(): HasMany
    {
        return $this->hasMany(TeamMember::class);
    }

    /** @return HasMany<Document, $this> */
    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    // --- Scopes ----------------------------------------------------------
    /** @param  Builder<Edition>  $query */
    public function scopeCurrent(Builder $query): void
    {
        $query->where('is_current', true)->where('status', EditionStatus::Published);
    }

    /** @param  Builder<Edition>  $query */
    public function scopePublished(Builder $query): void
    {
        $query->where('status', EditionStatus::Published);
    }

    /** @param  Builder<Edition>  $query */
    public function scopeArchived(Builder $query): void
    {
        $query->where('status', EditionStatus::Archived);
    }

    // --- Accessors -------------------------------------------------------

    /** Resolved title for a specific locale, independent of the current one. */
    public function titleIn(Locale|string $locale): string
    {
        $locale = $locale instanceof Locale ? $locale : Locale::parse($locale);

        // getRawOriginal() returns the JSON string, not a decoded array, so
        // indexing it directly with a locale string raises a TypeError. The
        // cast is bypassed on purpose here: a custom cast resolves for the
        // *active* locale only, and this method must be able to ask for another.
        return TranslationPayload::resolve(
            TranslationPayload::toArray($this->getRawOriginal('title')),
            $locale->value
        ) ?? "{$this->organiser} {$this->year}";
    }

    /**
     * "16 & 17 December 2026 - Rabat" style date line, in the requested locale.
     *
     * Deliberately built from the date columns rather than a hardcoded string,
     * because the 2024 site carried the literal string "juin 2024 - Casablanca"
     * in the language files.
     *
     * The default is the page's active locale rather than a literal, so a
     * caller that forgets to pass one still speaks the language of the page it
     * is rendered on.
     */
    public function dateLine(Locale|string|null $locale = null): string
    {
        $locale = $this->resolveLocale($locale);

        $format = 'j F Y';

        $start = $this->localisedDate($this->starts_on, $locale, $format);
        $end = $this->localisedDate($this->ends_on, $locale, $format);

        if ($this->starts_on->isSameDay($this->ends_on)) {
            return $start;
        }

        // Same month: "16 & 17 décembre 2026" reads better than repeating the
        // month, and matches how the brief writes the dates.
        if ($this->starts_on->month === $this->ends_on->month) {
            $conjunction = match ($locale) {
                Locale::English => 'And',
                Locale::Arabic => 'و',
                default => 'et',
            };

            return $this->starts_on->format('j').' '.$conjunction.' '.$end;
        }

        return "{$start} & {$end}";
    }

    /**
     * The venue's name, in the requested locale.
     *
     * The database keeps one locale-neutral spelling; the translated name lives
     * in `venue.venue_full`. When that key is absent the stored value is used,
     * so a caller never renders a raw translation key.
     */
    public function venueLine(Locale|string|null $locale = null): string
    {
        $locale = $this->resolveLocale($locale);

        return $this->localisedValue('venue.venue_full', (string) $this->venue_name, $locale);
    }

    /**
     * The venue's postal address, in the requested locale.
     *
     * Same rule as the name: a translated address under `venue.address_full`,
     * falling back to the stored value.
     */
    public function venueAddressLine(Locale|string|null $locale = null): string
    {
        $locale = $this->resolveLocale($locale);

        return $this->localisedValue('venue.address_full', (string) $this->venue_address, $locale);
    }

    /**
     * The host city, in the requested locale.
     *
     * "Rabat" reads as "الرباط" on the Arabic pages while the database keeps a
     * single, locale-neutral spelling.
     */
    public function cityLine(Locale|string|null $locale = null): string
    {
        $locale = $this->resolveLocale($locale);

        return $this->localisedValue('venue.city_full', (string) $this->city, $locale);
    }

    /**
     * Resolve a locale a caller asked for, defaulting to the page's active
     * locale rather than the configured site default. A helper called with no
     * argument must speak the language of the page it is rendered on, or an
     * Arabic default leaks onto the French and English pages.
     */
    private function resolveLocale(Locale|string|null $locale): Locale
    {
        return $locale === null
            ? Locale::parse(app()->getLocale())
            : Locale::parse($locale);
    }

    /**
     * Resolve a translated value for the given key, returning the stored
     * fallback when the translation is missing so no raw key is ever shown.
     */
    private function localisedValue(string $key, string $fallback, Locale $locale): string
    {
        if ($fallback === '') {
            return $fallback;
        }

        $translated = trans($key, [], $locale->value);

        return $translated === $key ? $fallback : (string) $translated;
    }

    /**
     * A map link for the venue, derived from the stored coordinates.
     *
     * Derived rather than seeded so there is exactly one source of truth for
     * where the venue is: a hand-typed map URL and a lat/lng pair drift apart,
     * and the map is the one thing on the site a delegate will actually act on.
     *
     * Returns null when either coordinate is missing, which is what the venue
     * page checks before rendering a "open the map" call to action.
     */
    public function mapUrl(): ?string
    {
        if (! filled($this->venue_lat) || ! filled($this->venue_lng)) {
            return null;
        }

        return 'https://www.google.com/maps/search/?api=1'
            .'&query='.rawurlencode($this->venue_lat.','.$this->venue_lng);
    }

    /**
     * Month names come from the app locale, so Arabic renders a real Arabic
     * month rather than the English one with an Arabic page around it.
     */
    private function localisedDate(Carbon $date, Locale $locale, string $format): string
    {
        $previous = app()->getLocale();
        app()->setLocale($locale->value);

        try {
            return $date->locale($locale->value)->translatedFormat($format);
        } finally {
            app()->setLocale($previous);
        }
    }

    /** The public identity string required everywhere: "Conférence ARABCIA 2026". */
    public function identityLabel(): string
    {
        return __('edition.identity', [
            'organiser' => $this->organiser,
            'year' => $this->year,
        ]);
    }

    /**
     * The downloadable programme PDF for a language.
     *
     * Each language ships its own file, so a delegate downloads the programme in
     * the language they are reading. A language whose translated file is not in
     * `public/` yet falls back to the French edition rather than to a link that
     * 404s; only when no programme file exists at all does this return null, so
     * the caller can fall back to the programme page itself rather than offer a
     * dead download.
     */
    public function programmePdfUrl(Locale|string|null $locale = null): ?string
    {
        $locale = $locale instanceof Locale ? $locale : Locale::parse($locale);

        $files = match ($locale) {
            Locale::Arabic => ['programme_arabic.pdf', 'programme.pdf'],
            Locale::English => ['programme_english.pdf', 'programme.pdf'],
            default => ['programme.pdf'],
        };

        foreach ($files as $file) {
            if (is_file(public_path($file))) {
                return asset($file);
            }
        }

        return null;
    }

    // --- Lookups ---------------------------------------------------------

    /**
     * The edition the site is currently presenting, or null.
     *
     * Memoised for the request: this is called by nearly every view, the
     * navigation and the pricing logic alike, and a single extra query per call
     * is exactly the N+1 the model guards are there to catch.
     */
    public static function current(): ?self
    {
        return static::$current ??= static::query()->current()->first();
    }

    public static function forYear(int|string $year): ?self
    {
        return static::query()->where('year', (int) $year)->first();
    }

    /** Every edition except the current one, newest first: powers the archive. */
    public static function archive(): Collection
    {
        return static::query()
            ->whereKeyNot(static::current()?->getKey() ?? 0)
            ->orderByDesc('year')
            ->get();
    }

    /** Drop the memoised instance; call after switching edition in tests/admin. */
    public static function forgetCurrent(): void
    {
        static::$current = null;
    }
}
