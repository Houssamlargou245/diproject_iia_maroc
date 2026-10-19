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
    /**
     * Sign an outgoing request.
     *
     * The value transform here is `trim()` alone, matching the request side of
     * the 2024 implementation (`cmi/SendData.php`) and, in practice, accepted by
     * CMI. The response side is different: CMI HTML-encodes some echoed values,
     * so verification decodes before hashing — see `verify()`.
     */
    public function hash(array $fields, string $storeKey): string
    {
        return $this->digest($fields, $storeKey, decode: false);
    }

    public function verify(array $fields, string $storeKey, ?string $received): bool
    {
        if ($received === null || $received === '') {
            return false;
        }

        return hash_equals($this->digest($fields, $storeKey, decode: true), $received);
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
    public function canonicalise(array $fields, string $storeKey, bool $decode = false): string
    {
        return $this->plainText($fields, $storeKey, $decode);
    }

    private function digest(array $fields, string $storeKey, bool $decode): string
    {
        return base64_encode(hash('sha512', $this->plainText($fields, $storeKey, $decode), true));
    }

    private function plainText(array $fields, string $storeKey, bool $decode): string
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

            // A response carries values CMI HTML-encoded when they were echoed
            // back; the 2024 verifier decoded them before signing and the
            // gateway hashes the decoded form, so we must do the same or every
            // callback whose payload holds an entity is rejected.
            $value = trim($decode ? html_entity_decode($value, ENT_QUOTES, 'UTF-8') : $value);

            $plain .= $this->escape($value).'|';
        }

        return $plain.$this->escape($storeKey);
    }

    private function escape(string $value): string
    {
        return str_replace('|', '\\|', str_replace('\\', '\\\\', $value));
    }
}
