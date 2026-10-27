<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\Auth\ConfirmTotpRequest;
use App\Models\User;
use App\Notifications\PromoCodeAssigned;
use App\Services\Registration\PromoCodeGenerator;
use App\Services\Security\SecurityEventLogger;
use App\Services\Totp\TotpService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Enrolling an authenticator app, and proving it works.
 *
 * Replaces the SMS verification flow. The shape is the two-step idea the service
 * describes — issue a secret, then prove the visitor kept it — but the proof is a
 * code their own phone computes rather than one a gateway sends.
 *
 * Existing accounts use their persisted pending secret; new registrations use
 * an encrypted session payload until the first valid code is supplied.
 */
class TotpController extends Controller
{
    public function __construct(
        private readonly TotpService $totp,
        private readonly SecurityEventLogger $events,
    ) {}

    /**
     * The enrolment page: a QR code, the same secret as a typeable key, and one
     * field to prove the app is working.
     */
    public function show(Request $request): View|RedirectResponse
    {
        $user = $request->user();

        if ($user === null) {
            $pending = $this->pendingRegistration($request);
            $user = new User($pending['attributes']);
            $user->forceFill(['totp_secret' => $pending['secret']]);

            return $this->setupView($user, $pending['secret']);
        }

        if ($user->hasConfirmedTotp()) {
            return redirect()->route('home');
        }

        // A pending secret is reused rather than reissued on every page view.
        // Regenerating on each GET would invalidate the QR code the visitor is
        // halfway through scanning, and hand a page refresh the power to quietly
        // break an enrolment in progress.
        $secret = filled($user->totp_secret)
            ? (string) $user->totp_secret
            : $this->totp->begin($user);

        return $this->setupView($user, $secret);
    }

    private function setupView(User $user, string $secret): View
    {
        return view('auth.totp-setup', [
            'user' => $user,
            'secret' => $secret,
            // The manual key is the same secret, not a second one. A visitor on a
            // desktop with no phone camera to hand has to be able to get this
            // done, and the failure mode of a QR-only flow is a support call.
            'manualKey' => $this->chunks($secret),
            'qrCode' => $this->totp->qrCode($user, $secret),
            'issuer' => (string) config('totp.issuer'),
            'codeLength' => (int) config('totp.digits', 6),
        ]);
    }

    /**
     * Check the first code and, if it matches, switch the authenticator on.
     */
    public function confirm(ConfirmTotpRequest $request): RedirectResponse
    {
        $user = $request->user();

        if ($user === null) {
            return $this->confirmRegistration($request);
        }

        if ($user->hasConfirmedTotp()) {
            return redirect()->route('home');
        }

        $recoveryCodes = $this->totp->confirm($user, $request->code());

        if ($recoveryCodes === null) {
            // Thrown as a field error, not a flash: the message belongs beside
            // the input, the visitor keeps their place, and the reason stays
            // generic so "wrong code" and "expired code" are indistinguishable.
            throw ValidationException::withMessages([
                'code' => __('totp.invalid_code'),
            ]);
        }

        $this->events->log('totp.enrolled', [
            'user_id' => $user->getKey(),
            'ip' => $request->ip(),
            'context' => ['issuer' => (string) config('totp.issuer')],
        ]);

        // The session identifier is rotated at the privilege change, so a
        // session id captured before enrolment is worthless afterwards.
        $request->session()->regenerate();
        Auth::login($user, remember: true);

        // The plain codes are handed to exactly one response, here, and never
        // stored in the session: a flash bag would leave them in the session
        // store on disk, where they would outlive the page that should have been
        // their only appearance.
        return redirect()
            ->route('pricing')
            ->withFragment('tariffs') 
            ->with('totp_recovery_codes', $recoveryCodes);
    }

    private function confirmRegistration(ConfirmTotpRequest $request): RedirectResponse
    {
        $pending = $this->pendingRegistration($request);

        if (! $this->totp->verify($pending['secret'], $request->code())) {
            throw ValidationException::withMessages(['code' => __('totp.invalid_code')]);
        }

        $attributes = $pending['attributes'];

        if (User::query()->where('email', $attributes['email'])->exists()) {
            throw ValidationException::withMessages(['email' => __('validation.email_taken')]);
        }

        if (User::query()->where('phone', $attributes['phone'])->exists()) {
            throw ValidationException::withMessages(['phone' => __('validation.phone_taken')]);
        }

        [$user, $recoveryCodes] = DB::transaction(function () use ($attributes, $pending, $request): array {
            $attributes['promo_code'] = app(PromoCodeGenerator::class)->generate(
                (string) ($attributes['first_name'] ?? ''),
            );

            $user = User::create($attributes);
            $user->forceFill([
                'terms_accepted_at' => $pending['terms_accepted_at'],
                'totp_secret' => $pending['secret'],
            ])->save();

            $recoveryCodes = $this->totp->confirm($user, $request->code());

            if ($recoveryCodes === null) {
                throw ValidationException::withMessages(['code' => __('totp.invalid_code')]);
            }

            return [$user, $recoveryCodes];
        });

        $request->session()->forget('pending_registration');
        $request->session()->regenerate();
        Auth::login($user, remember: true);
        $user->notify(new PromoCodeAssigned($user->promo_code));

        $this->events->log('auth.registered', [
            'user_id' => $user->getKey(),
            'ip' => $request->ip(),
            'context' => ['locale' => $user->locale, 'country_id' => $user->country_id],
        ]);
        $this->events->log('totp.enrolled', [
            'user_id' => $user->getKey(),
            'ip' => $request->ip(),
            'context' => ['issuer' => (string) config('totp.issuer')],
        ]);

        return redirect()->route('pricing')->withFragment('tariffs')->with('totp_recovery_codes', $recoveryCodes);
    }

    /** @return array{attributes: array<string, mixed>, secret: string, terms_accepted_at: string} */
    private function pendingRegistration(Request $request): array
    {
        $payload = $request->session()->get('pending_registration');

        abort_unless(is_string($payload), 403);

        try {
            $pending = json_decode(Crypt::decryptString($payload), true, flags: JSON_THROW_ON_ERROR);
        } catch (\Throwable) {
            $request->session()->forget('pending_registration');
            abort(403);
        }

        abort_unless(
            is_array($pending)
                && is_array($pending['attributes'] ?? null)
                && is_string($pending['secret'] ?? null)
                && is_string($pending['terms_accepted_at'] ?? null),
            403,
        );

        return $pending;
    }

    /**
     * The recovery codes, shown once.
     *
     * A separate page from `done` because it is the only moment these exist in
     * plain text. After this response they are hashes and nothing can recover
     * them, which is the point.
     */
    public function recoveryCodes(Request $request): View|RedirectResponse
    {
        $user = $request->user();

        if (! $user->hasConfirmedTotp()) {
            return redirect()->route('totp.setup');
        }

        $codes = $request->session()->get('totp_recovery_codes');

        // Reload is expected: the codes have already been shown and the session
        // has moved on. Say so plainly rather than showing an empty list, which
        // reads as "you have no codes" — the opposite of the truth.
        if (! is_array($codes) || $codes === []) {
            return view('auth.totp-recovery', [
                'user' => $user,
                'codes' => null,
                'remaining' => $this->totp->remainingRecoveryCodes($user),
            ]);
        }

        $request->session()->forget('totp_recovery_codes');

        return view('auth.totp-recovery', [
            'user' => $user,
            'codes' => $codes,
            'remaining' => count($codes),
        ]);
    }

    /**
     * Where the visitor lands once the authenticator is working.
     *
     * Its own URL so a reload after success does not bounce somebody back to a
     * form they no longer need.
     */
    public function done(Request $request): View
    {
        $user = $request->user();

        return view('auth.totp-done', [
            'user' => $user,
            'remaining' => $this->totp->remainingRecoveryCodes($user),
            // Ordering is what enrolment unlocks, so this is where the button
            // goes rather than on a generic dashboard.
            'canOrder' => $user->canRegister(),
        ]);
    }

    /**
     * Split a base32 secret into readable groups.
     *
     * Authenticator apps group codes; a 32-character unbroken string cannot be
     * compared character by character against a screen, so a mistyped secret is
     * invisible until it fails.
     */
    private function chunks(string $secret): string
    {
        return trim(chunk_split($secret, 4, ' '));
    }
}
