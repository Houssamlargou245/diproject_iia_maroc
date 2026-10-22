<?php

declare(strict_types=1);

/*
|-------------------------------------------------------------------------------
| TOTP (authenticator app)
|-------------------------------------------------------------------------------
|
| How a delegate proves they can get back into their own account, replacing the
| SMS code that asked them to prove a phone number.
|
| The practical difference is cost: an SMS per verification is metered by a
| provider, and this is arithmetic on a shared secret with no per-message charge
| and no third party in the loop. The price is friction — a phone without an
| authenticator app cannot use this, and a delegate who changes handset loses
| their codes unless they saved the recovery set. That trade is stated out loud
| on the enrolment page rather than discovered afterwards.
|
| `required` decides whether an account without a confirmed secret can order. It
| is on: an unconfirmed account is the same state the 2024 build left every
| account in, which is the gap this whole feature exists to close.
|
*/

return [

    'required' => env('TOTP_REQUIRED', true),

    /*
    | The issuer shown in the authenticator app. This is what the person sees
    | beside the entry, so it must be the brand they are registering for and not
    | "Laravel" — a list of six conference codes on one phone is unreadable
    | without it.
    */
    'issuer' => env('TOTP_ISSUER', env('APP_NAME', 'ARABCIA 2026')),

    'digits' => (int) env('TOTP_DIGITS', 6),

    /*
    | How many 30-second steps either side of now are accepted.
    |
    | One either side is the usual choice and covers ordinary clock drift plus
    | the few seconds a person spends reading the code and typing it. Anything
    | wider meaningfully enlarges the replay window: a code observed once stays
    | usable for every step still inside it.
    */
    'window' => (int) env('TOTP_WINDOW', 1),

    /*
    | Recovery codes: single-use stand-ins for the authenticator app, for the
    | handset that was lost or replaced. Shown once, hashed at rest.
    */
    'recovery_codes' => (int) env('TOTP_RECOVERY_CODES', 8),

    /*
    | Bytes of entropy in the shared secret. 32 is the RFC 4226 recommendation
    | and the default of the underlying library; shorter secrets are guessable
    | offline given the key space is tiny.
    */
    'secret_bytes' => (int) env('TOTP_SECRET_BYTES', 32),

    /*
    | Wrong codes allowed before the confirmation form starts refusing. Lower
    | than the six digits would otherwise permit, because there is no resend here
    | to wait out — the only way past a lockout is to restart enrolment.
    */
    'max_attempts' => (int) env('TOTP_MAX_ATTEMPTS', 5),

    /*
    | How long the session may sit idle while the authenticator app is being
    | set up, in minutes. The site-wide session lifetime is two hours, and
    | enrolment is the one step where a visitor legitimately puts the phone
    | down: installing an app, granting it permission, reading the code. A
    | session that expires there turns a half-finished enrolment into a 419
    | with the code they had just typed thrown away.
    */
    'session_lifetime' => (int) env('TOTP_SESSION_LIFETIME', 600),

];
