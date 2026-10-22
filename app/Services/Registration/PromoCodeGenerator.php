<?php

namespace App\Services\Registration;

use App\Models\User;
use Illuminate\Support\Str;
use RuntimeException;

class PromoCodeGenerator
{
    private const SUFFIX_LENGTH = 6;
    private const SUFFIX_CHARSET = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789'; // No O 0 I 1 L
    private const FALLBACK_PREFIX = 'PR';
    private const MAX_ATTEMPTS = 5;

    public function generate(string $firstName): string
    {
        $prefix = $this->prefix($firstName);

        for ($attempt = 0; $attempt < self::MAX_ATTEMPTS; $attempt++) {
            $code = $prefix . '-' . $this->suffix();

            if (! User::where('promo_code', $code)->exists()) {
                return $code;
            }
        }

        throw new RuntimeException(
            'Unable to generate a unique promo code after ' . self::MAX_ATTEMPTS . ' attempts.'
        );
    }

    public function prefix(string $firstName): string
    {
        $ascii   = Str::ascii($firstName);
        $letters = preg_replace('/[^A-Za-z]/', '', $ascii) ?? '';
        $letters = strtoupper($letters);

        if (strlen($letters) < 2) {
            return self::FALLBACK_PREFIX;
        }

        return $letters[0] . $letters[strlen($letters) - 1];
    }

    private function suffix(): string
    {
        $out = '';
        $max = strlen(self::SUFFIX_CHARSET) - 1;

        for ($i = 0; $i < self::SUFFIX_LENGTH; $i++) {
            $out .= self::SUFFIX_CHARSET[random_int(0, $max)];
        }

        return $out;
    }
}