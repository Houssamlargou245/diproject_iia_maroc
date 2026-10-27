<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\Otp\PhoneNumber;
use App\Services\Security\RateLimiter;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * Sign-in and sign-out.
 *
 * Two details that the 2024 build did not have:
 *
 *  - the identifier is matched against both `email` and `phone`, so somebody
 *    who registered with a phone can sign in with the number they verified.
 *  - the throttle is keyed on the submitted identifier *and* the source IP, and
 *    it is not cleared by a successful attempt. Clearing it on success lets an
 *    attacker keep a known password and retry in a tight loop.
 */
class SessionController extends Controller
{
    public function __construct(private readonly RateLimiter $limiter) {}

    public function create(): View
    {
        return view('auth.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'identifier' => ['required', 'string', 'max:190'],
            'password' => ['required', 'string', 'max:4096'],
        ], [], [
            'identifier' => __('login.identifier'),
            'password' => __('register.password'),
        ]);

        $identifier = trim($credentials['identifier']);
        $ip = $request->ip();
        $max = (int) config('security.rate_limit_login', 5);
        $decay = (int) config('security.login_decay_seconds', 300);

        $this->limiter->assertWithin(
            'auth:login',
            mb_strtolower($identifier),
            $ip,
            $max,
            $decay,
            __('login.throttled', ['seconds' => $decay]),
        );

        $user = $this->findUser($identifier);

        // Hash the supplied password even when the user does not exist, so the
        // response time does not reveal which identifiers are registered. The
        // dummy hash below is a real bcrypt cost, not a no-op.
        $hash = $user?->password ?? '$2y$12$'.str_repeat('.', 53);

        if ($user === null || ! Hash::check($credentials['password'], $hash)) {
            $this->limiter->hit('auth:login', mb_strtolower($identifier), $ip);

            // One message for "no such account" and "wrong password".
            throw ValidationException::withMessages([
                'identifier' => __('login.failed'),
            ]);
        }

        $user->forceFill($this->loginAttributes($user, $credentials['password']))->save();

        Auth::login($user, remember: (bool) $request->boolean('remember'));

        // New session id on privilege change, so a pre-authentication session
        // cannot be replayed as the authenticated one.
        $request->session()->regenerate();

        // ✅ ADD THIS CHECK:
        if (! $user->hasConfirmedTotp()) {
            return redirect()->route('totp.setup');
        }

        return redirect()->intended(route('home'));
    }

    public function __invoke(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        // Keep the current locale before destroying the session
        $locale = $request->session()->get('locale', app()->getLocale());

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        // Restore the locale in the new session
        $request->session()->put('locale', $locale);
        app()->setLocale($locale);

        return redirect()->route('home')->with('status', __('auth.logged_out'));

    }

    /**
     * Attributes written on a successful sign-in.
     *
     * A legacy phpass hash is replaced with a bcrypt one the first time the
     * account signs in, so imported rows converge without a forced reset. The
     * plain-text password is handed to the `hashed` cast, which performs the
     * bcrypt hashing. An up-to-date hash only gets the timestamp.
     *
     * @return array<string, mixed>
     */
    private function loginAttributes(User $user, string $password): array
    {
        $attributes = ['last_login_at' => now()];

        if (Hash::needsRehash($user->password)) {
            $attributes['password'] = $password;
        }

        return $attributes;
    }

    private function findUser(string $identifier): ?User
    {
        $needle = mb_strtolower($identifier);

        return User::query()
            ->where('email', $needle)
            ->orWhere('phone', $this->canonicalisePhone($needle))
            ->first();
    }

    /**
     * Accept both `0612345678` and `+212612345678` on the sign-in form. Returns
     * the input untouched when it does not parse, so the lookup falls back to
     * the email comparison rather than matching a malformed value.
     */
    private function canonicalisePhone(string $value): string
    {
        if (! str_starts_with($value, '+') && ! str_starts_with($value, '0')) {
            return $value;
        }

        try {
            return PhoneNumber::normalise($value);
        } catch (\Throwable) {
            return $value;
        }
    }
}
