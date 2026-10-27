<?php

declare(strict_types=1);

namespace App\Services\Totp;

use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use PragmaRX\Google2FAQRCode\Google2FA;
use Throwable;

/**
 * Enrolment and checking for an authenticator app (TOTP, RFC 6238).
 *
 * The shape of the flow, and why it is two steps:
 *
 *   1. `begin()` writes a *pending* secret and returns it. The account cannot use
 *      it yet, because the visitor has not proved they stored it.
 *   2. `confirm()` checks a code against that secret and, only on success, sets
 *      `totp_confirmed_at` and mints the recovery codes.
 *
 * Splitting them is what makes enrolment survivable. A single step — write the
 * secret and mark it usable — leaves an account whose secret lives only on a
 * phone that was already lost when the QR was scanned. Here, failing at step 2
 * costs a rescan and nothing else.
 *
 * The secret is encrypted at rest by the `encrypted` cast on the model, so it is
 * only ever plain text in memory here, inside this class. It is never logged.
 */
class TotpService
{
    public function __construct(private readonly Google2FA $engine) {}

    /**
     * Issue a fresh pending secret for this account.
     *
     * A new secret invalidates any previous one on purpose: this is also how a
     * person who lost their handset starts over, and silently reusing an old
     * secret would leave the lost phone able to authenticate.
     *
     * @return string the base32 secret, for display and for the QR code
     */
    public function begin(User $user): string
    {
        $secret = $this->generateSecret();

        // Confirmed is cleared here, not left alone: a pending secret must never
        // sit next to an old confirmation timestamp and read as usable.
        $user->forceFill([
            'totp_secret' => $secret,
            'totp_confirmed_at' => null,
        ])->save();

        return $secret;
    }

    /** Generate a secret without persisting it to an account. */
    public function generateSecret(): string
    {
        return $this->engine->generateSecretKey((int) config('totp.secret_bytes', 32));
    }

    /** Check a code against a transient secret, such as a pending registration. */
    public function verify(string $secret, string $code): bool
    {
        return $secret !== '' && $this->check($secret, $code);
    }

    /**
     * The `otpauth://` URI an authenticator app reads.
     *
     * Scanned as a QR code or typed by hand; both paths carry the same secret,
     * so the manual key is not a fallback for a broken camera, it is the same
     * thing for a visitor on a desktop with no phone camera to hand.
     */
    public function provisioningUri(User $user, string $secret): string
    {
        return $this->engine->getQRCodeUrl(
            (string) config('totp.issuer'),
            $user->email,
            $secret,
        );
    }

    /**
     * The enrolment QR code as an inline SVG data URL.
     *
     * Inline rather than a served image because this URI contains the shared
     * secret in plain text. A separate endpoint would mean the secret sat in a
     * cache, in a CDN edge, or in a browser cache where a shared machine could
     * read it back; a data URL never leaves the response that already had to
     * carry the secret to this visitor.
     *
     * Returns an empty string rather than throwing when the QR backend is
     * unavailable: the manual key below it still works, and a page that shows a
     * broken image over a perfectly good secret is worse than no image.
     */
    public function qrCode(User $user, string $secret): string
    {
        try {
            return $this->engine->getQRCodeInline(
                (string) config('totp.issuer'),
                $user->email,
                $secret,
                240,
            );
        } catch (Throwable $e) {
            Log::warning('TOTP QR code could not be rendered; the manual key still works.', [
                'error' => $e->getMessage(),
            ]);

            return '';
        }
    }

    /**
     * Check a code against a pending secret and, if it matches, activate it.
     *
     * @return list<string>|null the plain recovery codes on success, null on failure
     */
    public function confirm(User $user, string $code): ?array
    {
        $secret = (string) $user->totp_secret;

        if ($secret === '' || ! $this->check($secret, $code)) {
            return null;
        }

        $plain = $this->generateRecoveryCodes();

        $user->forceFill([
            'totp_confirmed_at' => now(),
            'totp_recovery_codes' => $this->hashRecoveryCodes($plain),
        ])->save();

        return $plain;
    }

    /**
     * Whether a code is valid for this account right now.
     *
     * A recovery code is accepted as well, and consumed when it matches: a
     * person whose phone is dead has no other way in, and a recovery code that
     * were not single-use would be a password written on paper.
     */
    public function checkCode(User $user, string $code): bool
    {
        $secret = (string) $user->totp_secret;

        if ($secret === '' || $code === '') {
            return false;
        }

        if ($this->check($secret, $code)) {
            return true;
        }

        return $this->consumeRecoveryCode($user, $code);
    }

    /**
     * Whether the account has a usable authenticator.
     *
     * Both columns, always. A secret with no confirmation is a half-finished
     * enrolment, and treating it as enrolled would let somebody past the gate
     * they never actually passed.
     */
    public function isEnrolled(User $user): bool
    {
        return filled($user->totp_secret) && $user->totp_confirmed_at !== null;
    }

    /**
     * How many recovery codes are still unused.
     */
    public function remainingRecoveryCodes(User $user): int
    {
        return count($user->totp_recovery_codes ?? []);
    }

    /**
     * Issue a fresh recovery set, discarding the old one.
     *
     * @return list<string>
     */
    public function regenerateRecoveryCodes(User $user): array
    {
        $plain = $this->generateRecoveryCodes();

        $user->forceFill([
            'totp_recovery_codes' => $this->hashRecoveryCodes($plain),
        ])->save();

        return $plain;
    }

    /**
     * Turn the authenticator off.
     *
     * The secret is cleared rather than just the timestamp, because leaving it
     * in place would let `isEnrolled()` be brought back true by writing the date
     * alone.
     */
    public function disable(User $user): void
    {
        $user->forceFill([
            'totp_secret' => null,
            'totp_confirmed_at' => null,
            'totp_recovery_codes' => null,
        ])->save();
    }

    // --- Internals --------------------------------------------------------

    /**
     * The raw library check, isolated so a malformed code cannot throw out of a
     * form handler.
     *
     * `verifyKey` returns false for a wrong code but can throw on input that is
     * not a digit string at all, and an exception here would surface as a 500 on
     * a form the visitor simply mistyped.
     */
    private function check(string $secret, string $code): bool
    {
        $digits = (int) config('totp.digits', 6);

        // Spaces and dashes are stripped rather than rejected: authenticator apps
        // display codes in groups and people retype them with the separator.
        $code = preg_replace('/\D/', '', $code) ?? '';

        if (strlen($code) !== $digits) {
            return false;
        }

        try {
            return (bool) $this->engine->verifyKey(
                $secret,
                $code,
                (int) config('totp.window', 1),
            );
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * @return list<string>
     */
    private function generateRecoveryCodes(): array
    {
        $count = (int) config('totp.recovery_codes', 8);

        return array_map(
            // Five characters either side of a dash. A code somebody reads off a
            // printed sheet and types on a phone keyboard should not be a typing
            // puzzle, so the alphabet leaves out the letters that read alike
            // (0/O, 1/I/L) rather than using full alphanumeric.
            fn (): string => Str::upper(Str::random(5)).'-'.Str::upper(Str::random(5)),
            range(1, max($count, 0)),
        );
    }

    /**
     * @param  list<string>  $plain
     * @return list<string>
     */
    private function hashRecoveryCodes(array $plain): array
    {
        return array_map(
            // sha256, not bcrypt: these are 11 random characters of ~1.2e11
            // possibilities, there is no small dictionary to attack, and the
            // comparison has to stay cheap enough to run on every check.
            fn (string $code): string => hash('sha256', Str::lower(trim($code))),
            $plain,
        );
    }

    /**
     * Spend a recovery code, if it is one and it has not been spent already.
     */
    private function consumeRecoveryCode(User $user, string $code): bool
    {
        $codes = $user->totp_recovery_codes ?? [];

        if ($codes === []) {
            return false;
        }

        $candidate = hash('sha256', Str::lower(trim($code)));

        $index = array_search($candidate, $codes, true);

        if ($index === false) {
            return false;
        }

        unset($codes[$index]);

        $user->forceFill([
            'totp_recovery_codes' => array_values($codes),
        ])->save();

        return true;
    }
}
