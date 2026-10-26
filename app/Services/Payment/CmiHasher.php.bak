<?php

declare(strict_types=1);

namespace App\Services\Payment;

/**
 * CMI "ver3" signature: sha512 over the sorted values, base64 encoded.
 *
 * Rules (from CMI's reference implementation):
 *  - sort the parameter NAMES case-insensitively
 *  - skip HASH and encoding
 *  - escape "\" then "|" inside every value
 *  - join the values with "|" and append the escaped store key
 *  - base64(sha512(plain, raw binary))
 */
class CmiHasher
{
    public function hash(array $fields, string $storeKey): string
    {
        return base64_encode(hash('sha512', $this->plainText($fields, $storeKey), true));
    }

    public function verify(array $fields, string $storeKey, ?string $received): bool
    {
        if ($received === null || $received === '') {
            return false;
        }

        return hash_equals($this->hash($fields, $storeKey), $received);
    }

    /**
     * The exact string CMI signs, before hashing.
     *
     * Exposed so a rejected signature can be reproduced by hand and so the
     * sorting and escaping rules stay directly testable — the final digest
     * alone gives no clue which side built a different string.
     *
     * @param  array<string, mixed>  $fields
     */
    public function canonicalise(array $fields, string $storeKey): string
    {
        return $this->plainText($fields, $storeKey);
    }

    private function plainText(array $fields, string $storeKey): string
    {
        $keys = array_keys($fields);
        natcasesort($keys);

        $plain = '';

        foreach ($keys as $key) {
            $lower = strtolower((string) $key);

            if ($lower === 'hash' || $lower === 'encoding') {
                continue;
            }

            $value = $fields[$key];
            $value = is_scalar($value) || $value === null ? (string) $value : '';

            $plain .= $this->escape(trim($value)).'|';
        }

        return $plain.$this->escape($storeKey);
    }

    private function escape(string $value): string
    {
        return str_replace('|', '\\|', str_replace('\\', '\\\\', $value));
    }
}
