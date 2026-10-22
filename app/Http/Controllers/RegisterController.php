<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\Locale;
use App\Http\Middleware\EnsureTotpIsConfirmed;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\Country;
use App\Models\User;
use App\Services\Registration\PromoCodeGenerator;
use App\Services\Security\SecurityEventLogger;
use App\Notifications\PromoCodeAssigned;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Account creation, gated by an authenticator app.
 *
 * The shape of the flow is the important part:
 *
 *   1. POST /register  -> the account is created and the visitor is signed in
 *                         immediately, but `totp_confirmed_at` stays null.
 *   2. POST /totp/setup -> they scan a QR code, prove it works, and the number
 *                         of an authenticator they hold is on the account.
 *
 * Signing the person in at step 1 rather than after step 2 is deliberate: a
 * half-finished registration should be resumable, and forcing them to re-enter
 * the form after a failed code would be a support burden for no security gain,
 * because {@see EnsureTotpIsConfirmed} already blocks
 * ordering until enrolment is finished.
 *
 * What is *not* deliberate is reading the phone number from the request and
 * trusting it as proof of anything. It is stored as contact data and nothing
 * more; the number on the account is never evidence that the person owns it.
 */
class RegisterController extends Controller
{
    public function __construct(
        private readonly SecurityEventLogger $events,
    ) {}

    public function create(Request $request): View
    {
        return view('auth.register', [
            'countries' => Country::query()
                ->where('is_active', true)
                ->orderBy('name_'.app()->getLocale())
                ->get(),
            'locale' => Locale::parse(app()->getLocale()),
        ]);
    }

    public function store(RegisterRequest $request, PromoCodeGenerator $promoCodeGenerator): RedirectResponse
{
    $user = DB::transaction(function () use ($request, $promoCodeGenerator): User {
        // 1. Get the attributes as usual
        $attributes = $request->accountAttributes();

        // 2. Generate and append the promo code safely inside the transaction
        $attributes['promo_code'] = $promoCodeGenerator->generate(
            (string) ($attributes['first_name'] ?? '')
        );

        // 3. Create the user with the new attributes
        $user = User::create($attributes);

        // Consent is recorded at the moment the box was ticked, not when the
        // authenticator was confirmed, so the audit trail shows when the
        // person agreed rather than when they finished setting up.
        $user->forceFill(['terms_accepted_at' => now()])->save();

        return $user;
    });

    Auth::login($user, remember: true);
    $request->session()->regenerate();
    // Send the welcome email with the promo code
        $user->notify(new PromoCodeAssigned($user->promo_code));
    $this->events->log('auth.registered', [
        'user_id' => $user->getKey(),
        'ip' => $request->ip(),
        'context' => [
            'locale' => $user->locale,
            'country_id' => $user->country_id,
        ],
    ]);

    // Straight to enrolment. There is nothing to send and nothing to fail
    // here — no gateway, no per-message cost, no delivery timeout to handle —
    // so the awkward case the SMS path had to plan for, a code that never
    // arrived, simply does not exist.
    return redirect()->route('totp.setup');
}
}
