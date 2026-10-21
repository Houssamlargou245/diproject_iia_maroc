<?php

namespace App\Models;

use App\Enums\Locale;
use App\Enums\MembershipStatus;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * A conference account.
 *
 * Registration is gated by an authenticator app: an account is only usable for
 * ordering once `totp_confirmed_at` is set. That replaces the SMS code that
 * asked a delegate to prove a phone number, which cost money per verification
 * and needed a metered provider in the loop.
 *
 * `phone_verified_at` still exists as a column because pre-2026 accounts carry
 * it, but nothing reads it any more and nothing writes it. Dropping the column
 * is a separate, reversible decision.
 */
class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory;

    use Notifiable;
    use SoftDeletes;

    protected $fillable = [
        'name', 'first_name', 'last_name', 'email', 'phone', 'password',
        'country_id', 'city', 'organisation', 'job_title',
        'locale', 'is_admin', 'avatar_path', 'preferences', 'promo_code'
    ];

    protected $hidden = [
        'password', 'remember_token', 'two_factor_secret', 'two_factor_recovery_codes',
        // The TOTP secret is the one attribute on this model that must never
        // reach a JSON response, an array export or an API resource by accident.
        // `encrypted` protects it at rest; `$hidden` protects it in transit, and
        // the cast is decrypted on read, so without this a `->toArray()` would
        // hand out a working credential.
        'totp_secret', 'totp_recovery_codes',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'phone_verified_at' => 'datetime',
            // Encrypted, not hashed: the server has to read the secret back to
            // compute the expected code. A hash would make verification
            // impossible, which is why this is one of the few secrets in the
            // application that cannot be stored one-way — and why the column is
            // never selected for a list, a log line or an export.
            'totp_secret' => 'encrypted',
            'totp_recovery_codes' => 'array',
            'totp_confirmed_at' => 'datetime',
            'terms_accepted_at' => 'datetime',
            'last_login_at' => 'datetime',
            'last_verification_sent_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
            'preferences' => 'array',
        ];
    }

    // --- Relations -------------------------------------------------------

    /** @return BelongsTo<Country, $this> */
    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    /** @return HasMany<Order, $this> */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /** @return HasMany<Membership, $this> */
    public function memberships(): HasMany
    {
        return $this->hasMany(Membership::class);
    }

    /** @return HasMany<DownloadGrant, $this> */
    public function downloadGrants(): HasMany
    {
        return $this->hasMany(DownloadGrant::class);
    }

    /** @return HasMany<SpeakerSubmission, $this> */
    public function speakerSubmissions(): HasMany
    {
        return $this->hasMany(SpeakerSubmission::class);
    }

    // --- Scopes ----------------------------------------------------------

    /** @param  Builder<User>  $query */
    public function scopeAdmins(Builder $query): void
    {
        $query->where('is_admin', true);
    }

    /**
     * Accounts with a working authenticator app.
     *
     * Both columns, because a pending secret is an abandoned enrolment.
     *
     * @param  Builder<User>  $query
     */
    public function scopeConfirmedTotp(Builder $query): void
    {
        $query->whereNotNull('totp_confirmed_at')
            ->whereNotNull('totp_secret');
    }

    /**
     * Whether this account has a working authenticator app.
     *
     * Both conditions, always: a secret with no confirmation is an enrolment
     * somebody abandoned halfway, and treating that as done is how a gate gets
     * walked through.
     */
    public function hasConfirmedTotp(): bool
    {
        return filled($this->totp_secret) && $this->totp_confirmed_at !== null;
    }

    public function isAdmin(): bool
    {
        return $this->is_admin;
    }

    /**
     * Whether this user gets the reduced "adhérent" rate.
     *
     * This is the one place the member/standard decision is made, replacing the
     * `iia_adhesion.etat = 'active'` check that was duplicated in
     * inscription.php and process_form.php in the 2024 build.
     */
    public function isActiveMember(): bool
    {
        return $this->memberships()
            ->where('status', MembershipStatus::Active)
            ->exists();
    }

    // --- Presentation helpers ---------------------------------------------

    public function localeEnum(): Locale
    {
        return Locale::fromRequest($this->locale);
    }

    public function displayName(): string
    {
        $full = trim(($this->first_name ?? '').' '.($this->last_name ?? ''));

        return $full !== '' ? $full : $this->name;
    }

    public function maskedPhone(): string
    {
        $phone = (string) $this->phone;

        if (mb_strlen($phone) <= 4) {
            return $phone;
        }

        return str_repeat('•', mb_strlen($phone) - 4).mb_substr($phone, -4);
    }

    public function canAccessPresentations(): bool
    {
        return $this->downloadGrants()
            ->whereNull('revoked_at')
            ->where(fn (Builder $q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))
            ->exists();
    }

    /**
     * Accounts that may place an order.
     *
     * Both conditions, and the second one is a consent record rather than a
     * technicality: a confirmed authenticator says the account is reachable, and
     * the accepted terms are what make an order placed through it binding.
     */
    public function canRegister(): bool
    {
        return $this->hasConfirmedTotp() && $this->terms_accepted_at !== null;
    }

    public function acceptTerms(): void
    {
        $this->forceFill(['terms_accepted_at' => now()])->save();
    }

    /**
     * Whether the account is complete enough to be useful.
     *
     * Used by the cleanup command to find registrations abandoned between
     * "account created" and "authenticator confirmed", which is the state a
     * delegate who never scanned the QR leaves behind.
     */
    public function isRegistrationIncomplete(): bool
    {
        return ! $this->hasConfirmedTotp();
    }
}
