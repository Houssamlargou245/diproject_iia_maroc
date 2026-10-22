<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\Locale;
use App\Http\Requests\Auth\UpdatePasswordRequest;
use App\Http\Requests\Auth\UpdateProfileRequest;
use App\Models\Country;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

/**
 * The signed-in delegate's own area.
 *
 * Deliberately thin: profile editing, the verification state and the order
 * history. The registration, order and payment work all happens elsewhere, and
 * every action here is scoped through a route model binding or `$request->user()`
 * rather than an id from the request, because the 2024 build trusted `$_GET`
 * user ids and let any visitor read any participant's order.
 *
 * The two write actions take the account from `$request->user()` and never from
 * the request body. There is no `user_id` field on either form, so there is
 * nothing for a caller to tamper with.
 */
class AccountController extends Controller
{
    public function show(Request $request): View
    {
        $user = $request->user();

        return view('account.show', [
            'user' => $user,
            'locale' => Locale::parse($user->locale),
            // Eager loaded: the order list renders each order's items and total,
            // and the status tag is derived from the order's payments, so a lazy
            // relation here is one query per order.
            'orders' => $user->orders()
                ->with(['items', 'payments'])
                ->latest('id')
                ->limit(20)
                ->get(),
            'membership' => $user->memberships()
                ->orderByDesc('id')
                ->first(),
            'canOrder' => $user->canRegister(),
            // The same check twice: `canOrder` gates the whole gate notice, and
            // `termsAccepted` is what says *which* of the two steps is missing.
            // Passing only one would force the view to recompute the user's state
            // to draw a row on the checklist.
            'termsAccepted' => $user->terms_accepted_at !== null,
        ]);
    }

    /**
     * The profile form.
     *
     * A separate page rather than a panel on `show()`, because this is a form
     * with thirteen fields and `show()` is a dashboard: putting an edit form on
     * the screen the delegate lands on after signing in buries the orders under
     * it, and the two have different jobs.
     */
    public function edit(Request $request): View
    {
        return view('account.edit', [
            'user' => $request->user(),
            'locale' => Locale::parse($request->user()->locale),
            // Eager loaded: the country select renders every row's name, and a
            // lazy relation here is one query per country in the list.
            //
            // `name_<locale>` rather than `name`: `countries` stores one column
            // per language and has no plain `name`, so ordering by `name` is a
            // 42S22 against a column the table has never had. Ordered in the
            // reader's own language so the list reads alphabetically to them —
            // the same thing RegisterController::create() does.
            'countries' => Country::query()
                ->where('is_active', true)
                ->orderBy('name_'.app()->getLocale())
                ->get(),
            'currentRoute' => 'account',
        ]);
    }

    /**
     * Save the identity fields.
     *
     * Changing the email re-opens verification rather than carrying the old
     * confirmation over. Silently keeping `email_verified_at` would leave the
     * account able to pass an "is this address proven" check on an address
     * nobody has proven, which is the one thing that check exists to prevent.
     */
    public function update(UpdateProfileRequest $request): RedirectResponse
    {
        $user = $request->user();

        $attributes = $request->profileAttributes();

        if ($request->emailChanged()) {
            $attributes['email_verified_at'] = null;
        }

        $user->forceFill($attributes)->save();

        if ($request->emailChanged()) {
            // Sent rather than assumed: the delegate has to be able to read this
            // address before it is treated as proven, and an unverified address
            // blocks the order the change was made *for*.
            $user->sendEmailVerificationNotification();
        }

        return redirect()->route('account.edit')
            ->with('status', __('account.profile_saved'))
            // Its own flash rather than a sentence appended to the success
            // message: it is a different kind of information — one says the
            // change worked, the other says there is now an outstanding action,
            // and burying the second inside the first is how a delegate ends up
            // waiting on a confirmation that was already sent.
            ->with('email_status', $request->emailChanged()
                ? __('account.email_reverify')
                : null);
    }

    /**
     * Change the password.
     *
     * The current password is compared here rather than by a validation rule so
     * the failure can say what actually went wrong, and the check runs against
     * the stored hash — a plain string comparison against the input would always
     * fail.
     */
    public function updatePassword(UpdatePasswordRequest $request): RedirectResponse
    {
        $user = $request->user();

        if (! Hash::check($request->string('current_password')->value(), (string) $user->password)) {
            return back()
                ->withErrors(['current_password' => __('account.current_password_wrong')])
                ->withInput($request->except('current_password', 'password', 'password_confirmation'));
        }

        $user->forceFill(['password' => $request->string('password')->value()])->save();

        return redirect()->route('account.edit')
            ->with('status', __('account.password_saved'));
    }

    /**
     * Record acceptance of the terms.
     *
     * `terms_accepted_at` is what `User::canRegister()` reads, so this is the
     * difference between an account that can order and one that cannot — and
     * until this existed nothing in the application ever set the column, which
     * left every TOTP-confirmed delegate permanently blocked at the pricing page.
     *
     * Timestamped only when it is not already set: re-submitting the checkbox
     * must not push the original consent date forward, or a delegate could
     * launder a stale acceptance into a fresh one by clicking twice.
     */
    public function acceptTerms(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($request->boolean('accept')) {
            $user->acceptTerms();
        }

        return redirect()->route('account')
            ->with('status', $user->terms_accepted_at !== null
                ? __('account.terms_accepted')
                : __('account.terms_not_accepted'));
    }

    public function logout(Request $request): RedirectResponse
    {
        return app(SessionController::class)->__invoke($request);
    }
}
