<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Contracts\OtpChannel;
use App\Enums\MembershipStatus;
use App\Enums\OrderStatus;
use App\Enums\OtpDriver;
use App\Enums\OtpPurpose;
use App\Models\Country;
use App\Models\Edition;
use App\Models\Order;
use App\Models\OtpCode;
use App\Models\User;
use App\Notifications\PromoCodeAssigned;
use App\Support\Money;
use Database\Seeders\EditionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Notification;
use PragmaRX\Google2FAQRCode\Google2FA;
use Tests\TestCase;

/**
 * Registration, verification, sign-in and the account page, end to end.
 *
 * PublicPagesTest renders the auth *forms*; nothing asserted what happens when
 * they are submitted. That gap is why two live defects survived: the account
 * page read `$order->total_amount` (a column that has never existed) and
 * `__()`-interpolated `order.status.*` against a translation file that was
 * never created. Both only appear for a user who has actually registered and
 * ordered, which no test had done.
 */
class AccountFlowTest extends TestCase
{
    use RefreshDatabase;

    private CapturingOtpChannel $channel;

    protected function setUp(): void
    {
        parent::setUp();

        Edition::forgetCurrent();

        config([
            'otp.driver' => 'log',
            'otp.length' => 6,
            'otp.resend_cooldown_seconds' => 0,
            'otp.max_sends_per_hour' => 20,
            'otp.rate_limit_per_phone' => 20,
        ]);

        // The log channel would write the plaintext code to the log file. Swapping
        // in a capturing channel keeps the real OtpService (so hashing, throttling
        // and single-use are all still exercised) while letting the test read the
        // code it needs to submit.
        $this->channel = new CapturingOtpChannel;
        $this->app->instance(OtpChannel::class, $this->channel);
    }

    protected function tearDown(): void
    {
        Edition::forgetCurrent();

        parent::tearDown();
    }

    // ---------------------------------------------------------------- register

    public function test_registration_is_only_persisted_after_totp_confirmation(): void
    {
        Notification::fake();
        $country = $this->country();

        $response = $this->post('/register', [
            'first_name' => 'Amina',
            'last_name' => 'Bennani',
            'email' => 'amina@example.test',
            'phone_code' => '+212',
            'phone' => '0612345678',
            'password' => 'Correct-Horse!9',
            'password_confirmation' => 'Correct-Horse!9',
            'organisation' => 'IIA Casablanca',
            'country_id' => $country->getKey(),
            'terms' => '1',
            'locale' => 'fr',
        ]);

        $response->assertRedirect(route('totp.setup'));

        $this->assertDatabaseMissing('users', ['email' => 'amina@example.test']);
        $this->assertGuest();
        Notification::assertNothingSent();

        $pending = json_decode(Crypt::decryptString(session('pending_registration')), true, flags: JSON_THROW_ON_ERROR);
        $this->get(route('totp.setup'))->assertOk();
        $this->post(route('totp.confirm'), [
            'code' => app(Google2FA::class)->getCurrentOtp($pending['secret']),
        ])->assertRedirect(route('pricing').'#tariffs');

        $user = User::query()->where('email', 'amina@example.test')->firstOrFail();

        // The phone is stored canonicalised, so the unique index and the sign-in
        // lookup both see one value rather than three spellings of it.
        $this->assertSame('+212612345678', $user->phone);
        $this->assertNotNull($user->terms_accepted_at);

        $this->assertAuthenticatedAs($user);
        $this->assertTrue($user->hasConfirmedTotp());
        $this->assertTrue($user->canRegister());
        Notification::assertSentTo($user, PromoCodeAssigned::class);

        $this->assertSame(0, OtpCode::query()->count());
    }

    public function test_abandoning_registration_does_not_create_an_account(): void
    {
        $this->post('/register', $this->validRegistration())->assertRedirect(route('totp.setup'));

        $this->get(route('totp.setup'))->assertOk();

        $this->assertDatabaseMissing('users', ['email' => 'valid@example.test']);
        $this->assertGuest();
    }

    public function test_a_wrong_registration_code_does_not_create_an_account(): void
    {
        $this->post('/register', $this->validRegistration());

        $this->post(route('totp.confirm'), ['code' => '000000'])
            ->assertSessionHasErrors('code');

        $this->assertDatabaseMissing('users', ['email' => 'valid@example.test']);
        $this->assertGuest();
    }

    /**
     * The local +212 form and the international form are the same person. If
     * normalisation did not happen before the unique check, the second
     * registration would pass validation and then fail as a constraint
     * violation — a 500 rather than a field error.
     */
    public function test_a_phone_already_registered_in_another_format_is_a_field_error(): void
    {
        User::factory()->create(['phone' => '+212612345678']);

        $response = $this->withHeaders(['Accept-Language' => 'fr'])
            ->post('/register', $this->validRegistration([
                'phone' => '0612345678',
            ]));

        // Asserting the *expected message*, not just the key: `phone.unique` is
        // mapped in RegisterRequest::messages(), and if that mapping is dropped
        // the visitor is shown the literal key `validation.phone_taken`.
        $response->assertSessionHasErrors(['phone' => __('validation.phone_taken')]);

        // And the key itself is what failed, not some other field.
        $response->assertSessionHasErrors('phone');
    }

    /**
     * A malformed number must produce the `normalised_phone` rule's translated
     * message. The rule is custom, so the message is looked up by rule name in
     * `messages()` rather than from the `validation` file; if that mapping
     * breaks the visitor sees the literal key `validation.normalised_phone`.
     */
    public function test_a_malformed_phone_reports_the_translated_message(): void
    {
        $response = $this->withHeaders(['Accept-Language' => 'fr'])
            ->post('/register', $this->validRegistration([
                'phone' => 'not-a-phone',
            ]));

        $response->assertSessionHasErrors('phone');
        $response->assertSessionHasErrors(['phone' => __('validation.phone')]);
    }

    public function test_registration_requires_accepted_terms(): void
    {
        $payload = $this->validRegistration();
        unset($payload['terms']);

        $this->post('/register', $payload)->assertSessionHasErrors('terms');
    }

    public function test_registration_rejects_a_duplicate_email(): void
    {
        User::factory()->create(['email' => 'taken@example.test']);

        $this->post('/register', $this->validRegistration([
            'email' => 'taken@example.test',
        ]))->assertSessionHasErrors('email');
    }

    // ------------------------------------------------------------- enrolment

    public function test_the_correct_code_enrols_the_authenticator(): void
    {
        $user = User::factory()->unconfirmedTotp()->create();

        $this->actingAs($user)->get('/totp/setup');

        $this->actingAs($user)->post('/totp/setup', [
            'code' => $this->currentTotpCode($user),
        ])->assertRedirect(route('totp.recovery-codes'));

        $this->assertNotNull($user->fresh()->totp_confirmed_at);
    }

    public function test_a_wrong_code_is_rejected_and_the_account_stays_unenrolled(): void
    {
        $user = User::factory()->unconfirmedTotp()->create();

        $this->actingAs($user)->get('/totp/setup');

        $this->actingAs($user)
            ->post('/totp/setup', ['code' => '000000'])
            ->assertSessionHasErrors('code');

        $this->assertNull($user->fresh()->totp_confirmed_at);
        $this->assertFalse($user->fresh()->canRegister());
    }

    /**
     * The confirmation endpoint must take the secret from the account, never from
     * the request. A `secret` field here would let anybody enrol an account they
     * hold no code for, and a `phone` field would be an open relay aimed at a
     * third party.
     */
    public function test_the_confirm_endpoint_ignores_a_secret_supplied_in_the_body(): void
    {
        $user = User::factory()->unconfirmedTotp()->create();

        $this->actingAs($user)->get('/totp/setup');

        $theirSecret = 'MZXW6YTBOI======';

        $this->actingAs($user)
            ->post('/totp/setup', [
                'code' => $this->currentTotpCode($user),
                'secret' => $theirSecret,
                'phone' => '+212699999999',
            ])
            ->assertRedirect(route('totp.recovery-codes'));

        // Enrolled — but on *their own* secret, not the one in the body.
        $this->assertNotSame($theirSecret, $user->fresh()->totp_secret);
        $this->assertNotNull($user->fresh()->totp_confirmed_at);
    }

    // ------------------------------------------------------------------ sign in

    public function test_sign_in_accepts_the_email(): void
    {
        $user = User::factory()->create(['email' => 'signin@example.test']);

        $this->post('/login', [
            'identifier' => 'signin@example.test',
            'password' => 'password',
        ])->assertRedirect();

        $this->assertAuthenticatedAs($user);
    }

    /** A verified number is a valid identifier, which is what phone-first implies. */
    public function test_sign_in_accepts_the_phone_in_either_format(): void
    {
        $user = User::factory()->create(['phone' => '+212612345678']);

        $this->post('/login', [
            'identifier' => '0612345678',
            'password' => 'password',
        ])->assertRedirect();

        $this->assertAuthenticatedAs($user);
    }

    /**
     * "No such account" and "wrong password" must be indistinguishable, or the
     * form becomes an account-enumeration oracle.
     */
    public function test_an_unknown_identifier_and_a_wrong_password_give_the_same_error(): void
    {
        $user = User::factory()->create(['email' => 'real@example.test']);

        $this->post('/login', [
            'identifier' => 'real@example.test',
            'password' => 'wrong-password',
        ])->assertSessionHasErrors('identifier');

        $wrongPassword = session('errors')->getBag('default')->first('identifier');

        $this->post('/login', [
            'identifier' => 'ghost@example.test',
            'password' => 'wrong-password',
        ])->assertSessionHasErrors('identifier');

        $unknownUser = session('errors')->getBag('default')->first('identifier');

        $this->assertSame($wrongPassword, $unknownUser);
        $this->assertGuest();
    }

    public function test_sign_out_invalidates_the_session(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/logout')->assertRedirect(route('home'));

        $this->assertGuest();
    }

    // ----------------------------------------------------------- account pages

    public function test_the_account_page_requires_authentication(): void
    {
        $this->get('/account')->assertRedirect(route('login'));
    }

    public function test_the_account_page_renders_for_a_new_user_with_no_orders(): void
    {
        $user = $this->register();

        $this->french()->actingAs($user)
            ->get('/account')
            ->assertOk()
            ->assertSee(__('account.title'))
            ->assertSee(__('order.no_orders'));
    }

    /**
     * The regression this file exists for: the account page used
     * `$order->total_amount`, which is not a column, and `Money::format()`
     * type-hints `int`, so any user with a single order got a 500. It also
     * rendered the raw `order.status.<value>` key.
     */
    public function test_the_account_page_renders_orders_with_labels_and_amounts(): void
    {
        $this->seed(EditionSeeder::class);
        Edition::forgetCurrent();

        // An order belongs to the edition the buyer registered for, and to that
        // edition's ticket type. Letting the factories invent their own would
        // create a second edition whose `code` can collide with the seeded one.
        $edition = Edition::query()->where('year', 2026)->firstOrFail();

        $user = $this->register();
        $user->forceFill(['totp_confirmed_at' => now()])->save();

        Order::factory()->forEdition($edition)->create([
            'user_id' => $user->getKey(),
            'ticket_type_id' => $edition->ticketTypes()->firstOrFail()->getKey(),
            'reference' => 'ARB-2026-0001',
            'status' => OrderStatus::Paid,
            'total' => 850000,
            'currency' => 'MAD',
        ]);

        $response = $this->french()->actingAs($user)->get('/account');

        $response->assertOk();
        $response->assertSee('ARB-2026-0001');
        $response->assertSee(OrderStatus::Paid->label('fr'));

        // Formatted from `total`, and formatted in the active locale.
        $response->assertSee(Money::format(850000, 'MAD', 'fr'));

        // No untranslated key may reach the page.
        $response->assertDontSee('order.status.');
        $response->assertDontSee('validation.');
    }

    public function test_the_account_page_shows_membership_status_as_a_label(): void
    {
        $user = User::factory()->member()->create();

        $response = $this->french()->actingAs($user)->get('/account');

        $response->assertOk();
        $response->assertSee(MembershipStatus::Active->label('fr'));
        $response->assertDontSee('membership.status.');
    }

    public function test_the_account_page_renders_in_arabic(): void
    {
        $user = $this->register();

        // Arabic is the default language and is served unprefixed, so the
        // Arabic account page is `/account`, not `/ar/account`.
        $this->actingAs($user)
            ->get('/account')
            ->assertOk()
            ->assertSee('dir="rtl"', escape: false)
            ->assertSee('lang="ar"', escape: false);
    }

    // ----------------------------------------------------------------- helpers

    /**
     * Force French.
     *
     * The Symfony test client sends `Accept-Language: en-us,en;q=0.5`, which
     * `SetLocale` resolves to English and stores in the session — and the session
     * outranks the header, so pinning the header on a later request is not
     * enough. Priming the session is the explicit way to ask for French.
     */
    private function french(): self
    {
        return $this->withSession(['locale' => 'fr']);
    }

    private function register(): User
    {
        $this->post('/register', $this->validRegistration())->assertRedirect();

        $pending = json_decode(Crypt::decryptString(session('pending_registration')), true, flags: JSON_THROW_ON_ERROR);
        $this->post('/totp/setup', [
            'code' => app(Google2FA::class)->getCurrentOtp($pending['secret']),
        ])->assertRedirect();

        return User::query()->where('email', 'valid@example.test')->firstOrFail();
    }

    /** The code the visitor's phone would be showing at this instant. */
    private function currentTotpCode(User $user): string
    {
        return app(Google2FA::class)
            ->getCurrentOtp((string) $user->fresh()->totp_secret);
    }

    /** @return array<string, mixed> */
    private function validRegistration(array $overrides = []): array
    {
        return array_merge([
            'first_name' => 'Yasmine',
            'last_name' => 'El Idrissi',
            'email' => 'valid@example.test',
            'phone_code' => '+212',
            'phone' => '0661234567',
            'password' => 'Correct-Horse!9',
            'password_confirmation' => 'Correct-Horse!9',
            'country_id' => $this->country()->getKey(),
            'terms' => '1',
            'locale' => 'fr',
        ], $overrides);
    }

    private function country(): Country
    {
        return Country::query()->firstOrCreate(
            ['iso2' => 'MA'],
            [
                'iso3' => 'MAR',
                'phone_code' => '+212',
                'dial_code' => '+212',
                'name_fr' => 'Maroc',
                'name_en' => 'Morocco',
                'name_ar' => 'المغرب',
                'is_active' => true,
            ],
        );
    }
}

/**
 * Counts live OTP rows for an identifier, so a test can assert that a code was
 * never issued to a third party's number.
 */
function OtpCodeCountFor(string $identifier): int
{
    return OtpCode::query()
        ->where('identifier', $identifier)
        ->whereNull('consumed_at')
        ->count();
}

/**
 * Records the plaintext code instead of sending it anywhere.
 *
 * Implements the real contract so the production OtpService runs unchanged:
 * hashing, throttling, expiry and single-use are all still exercised.
 */
class CapturingOtpChannel implements OtpChannel
{
    /** @var list<string> */
    public array $codes = [];

    public function send(string $identifier, string $code, OtpPurpose $purpose, string $locale): string
    {
        $this->codes[] = $code;

        return 'captured-'.count($this->codes);
    }

    public function supports(string $identifier): bool
    {
        return true;
    }

    public function identifierType(): string
    {
        return 'phone';
    }

    public function name(): string
    {
        // `otp_codes.driver` is cast to the OtpDriver enum, so the name has to be
        // a real backing value — the driver this channel stands in for. It
        // replaces the log driver in the container, so `log` is the truth here.
        return OtpDriver::Log->value;
    }

    public function lastCode(): string
    {
        if ($this->codes === []) {
            throw new \RuntimeException('No OTP code was issued during this test.');
        }

        return end($this->codes);
    }
}
