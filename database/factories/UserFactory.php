<?php

namespace Database\Factories;

use App\Enums\MembershipStatus;
use App\Models\Membership;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            // Unique across the whole test run: the column is indexed, and a
            // collision would fail as a constraint violation rather than a
            // readable assertion.
            'phone' => '+2126'.fake()->unique()->numerify('########'),
            // A working authenticator by default, so a factory-built delegate is
            // usable out of the box. `unconfirmedTotp()` is the state that has to
            // be refused.
            'totp_secret' => 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567',
            'totp_confirmed_at' => now(),
            'totp_recovery_codes' => null,
            'password' => static::$password ??= Hash::make('password'),
            'terms_accepted_at' => now(),
            'remember_token' => Str::random(10),
            // Registration always records a locale and SetLocale reads it off
            // the user. A DB default is not enough: the in-memory model only
            // carries the attributes the factory set, and strict mode turns a
            // read of anything else into a MissingAttributeException.
            'locale' => 'fr',

            // The rest of the columns the application reads by name. Same
            // reasoning as `locale`, applied across the row.
            //
            // `is_admin` is the important one: `User::isAdmin()` reads it, and
            // `EnsureUserIsAdmin` calls that on every request to /admin. Without
            // it here, a factory-built delegate blows up with
            // MissingAttributeException instead of being refused — so the test
            // that proves members cannot reach the staff area would itself 500.
            'is_admin' => false,
            'country_id' => null,
            'city' => 'Rabat',
            'organisation' => fake()->company(),
            'job_title' => fake()->jobTitle(),
            'avatar_path' => null,
            'preferences' => null,
            'last_login_at' => now(),
            'last_verification_sent_at' => null,

            // Registration always draws a promo code and the account page reads
            // it off the model. Same reasoning as `locale` above: the in-memory
            // model only carries the attributes the factory set, and strict mode
            // turns a read of anything else into a MissingAttributeException.
            // The charset is the generator's, minus the ambiguous characters.
            'promo_code' => 'PR-'.fake()->unique()->regexify('[ABCDEFGHJKMNPQRSTUVWXYZ23456789]{6}'),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * A user who cannot order: no authenticator, no accepted terms.
     *
     * The secret is set but *not* confirmed, because that is the state a real
     * abandoned enrolment leaves behind — and it is the state the gate has to
     * refuse. A state with no secret at all would pass for the wrong reason.
     */
    public function unconfirmedTotp(): static
    {
        return $this->state(fn (array $attributes) => [
            'totp_secret' => 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567',
            'totp_confirmed_at' => null,
            'terms_accepted_at' => null,
        ]);
    }

    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_admin' => true,
        ]);
    }

    public function member(): static
    {
        return $this->afterCreating(function (User $user): void {
            Membership::factory()->create(['user_id' => $user->id, 'status' => MembershipStatus::Active]);
        });
    }
}
