<?php

namespace App\Support;

/**
 * Money helper.
 *
 * All amounts in this application are integers in minor units (centimes).
 * Floating point never touches a stored amount; conversion to a decimal string
 * happens only at the formatting boundary.
 *
 * The legacy site stored prices as TEXT and did arithmetic in PHP floats, which
 * is how a 7 500 MAD ticket could end up with a total of 7 499.999999.
 */
final class Money
{
    /** Currencies the conference accepts, with their minor-unit exponent. */
    private const EXPONENTS = [
        'MAD' => 2,
        'USD' => 2,
        'EUR' => 2,
    ];

    public static function exponent(string $currency): int
    {
        return self::EXPONENTS[strtoupper($currency)] ?? 2;
    }

    /**
     * Format minor units for display.
     *
     * Uses a non-breaking space as the thousands separator in French, a comma
     * as the decimal mark, and flips both for English. Arabic-Indic digits are
     * left to the browser's own rendering so screen readers and copy/paste
     * keep working.
     */
    public static function format(int $minorUnits, string $currency = 'MAD', ?string $locale = null): string
    {
        $parts = self::parts($minorUnits, $currency, $locale);

        return $parts['amount'].' '.$parts['currency'];
    }

    /**
     * The figure and the currency code as two strings.
     *
     * Split rather than re-parsed: the thousands convention is the part that
     * differs per locale, so a template that wanted "7 500" large and "MAD"
     * small by cutting `format()`'s output apart would get the cut wrong for
     * at least one of them — the separator is a narrow no-break space, not a
     * plain one, so `explode(' ', …)` silently produces a broken string.
     *
     * @return array{amount: string, currency: string}
     */
    public static function parts(int $minorUnits, string $currency = 'MAD', ?string $locale = null): array
    {
        $locale ??= app()->getLocale();
        $exponent = self::exponent($currency);

        $divisor = 10 ** $exponent;
        $whole = intdiv(abs($minorUnits), $divisor);
        $cents = abs($minorUnits) % $divisor;

        $sign = $minorUnits < 0 ? '-' : '';

        $thousands = match ($locale) {
            'en' => ',',
            default => "\u{202F}",   // narrow no-break space (French convention)
        };

        $decimal = match ($locale) {
            'en' => '.',
            default => ',',
        };

        $wholeFormatted = number_format($whole, 0, $decimal, $thousands);

        $result = $cents === 0
            ? $wholeFormatted
            : $wholeFormatted.$decimal.str_pad((string) $cents, $exponent, '0', STR_PAD_LEFT);

        return ['amount' => $sign.$result, 'currency' => strtoupper($currency)];
    }

    /**
     * Parse a human-entered amount ("7 500", "7,500.00", "7500") into minor
     * units. Returns null when the input is not a usable number, so a bad form
     * entry surfaces as a validation error rather than a silently wrong price.
     */
    public static function parse(string|int|float|null $input, string $currency = 'MAD'): ?int
    {
        if ($input === null || $input === '') {
            return null;
        }

        if (is_int($input)) {
            return $input * (10 ** self::exponent($currency));
        }

        if (is_float($input)) {
            return (int) round($input * (10 ** self::exponent($currency)));
        }

        $normalised = preg_replace('/[\s\x{202F}\x{00A0}\x{2009}_]/u', '', $input) ?? '';
        $normalised = str_replace(',', '.', $normalised);

        if (! is_numeric($normalised)) {
            return null;
        }

        return (int) round((float) $normalised * (10 ** self::exponent($currency)));
    }

    /**
     * Multiply minor units by a whole quantity, staying in integer space.
     */
    public static function multiply(int $minorUnits, int $quantity): int
    {
        return $minorUnits * $quantity;
    }

    /**
     * Percentage discount, rounded half-up, in integer space.
     */
    public static function percentageOf(int $minorUnits, float $percentage): int
    {
        return (int) round($minorUnits * ($percentage / 100));
    }

    /** ISO-4217 numeric code required by the CMI gateway. */
    public static function numericCode(string $currency): string
    {
        $currency = strtoupper($currency);

        return (string) (config("cmi.currencies.{$currency}.numeric")
            ?? config('cmi.currencies.MAD.numeric', '504'));
    }
}
