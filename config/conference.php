<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Cart
    |--------------------------------------------------------------------------
    |
    | A basket is session-scoped and expires. Expiry is the fix for the 2024
    | behaviour where a single leftover 'encours' row in `iia_panier` blocked
    | that person from ever ordering again.
    |
    */

    'ttl_minutes' => (int) env('CART_TTL_MINUTES', 120),

    /*
    |--------------------------------------------------------------------------
    | Quotas
    |--------------------------------------------------------------------------
    |
    | The 2026 brief caps the event at 300 delegates, so sales must stop when
    | the room is full rather than when someone remembers to close the form.
    | `capacity` counts paid participants, not orders.
    |
    */

    'capacity' => (int) env('CONFERENCE_CAPACITY', 300),

    /*
    |--------------------------------------------------------------------------
    | Pricing
    |--------------------------------------------------------------------------
    |
    | Currencies come from the ticket type. The member rate is granted only for
    | a participant matched to an active Membership record, never because the
    | form said so.
    |
    */

    'default_currency' => env('CONFERENCE_CURRENCY', 'MAD'),

    'currencies' => ['MAD', 'USD'],

    /*
    |--------------------------------------------------------------------------
    | Feature switches
    |--------------------------------------------------------------------------
    |
    | speaker_submissions_open is false at launch: the brief defers the public
    | call-for-speakers and presentation-upload forms to the end of the
    | conference. Off means the route 404s, not that it silently accepts input.
    |
    */

    'speaker_submissions_open' => (bool) env('SPEAKER_SUBMISSIONS_OPEN', false),

    'presentation_uploads_open' => (bool) env('PRESENTATION_UPLOADS_OPEN', false),

    /*
    |--------------------------------------------------------------------------
    | Payment rehearsal
    |--------------------------------------------------------------------------
    |
    | One account may raise a real gateway order for a token amount so the CMI
    | round trip can be exercised end to end. Restricted to a single email so a
    | live-price order can never be created for 1 MAD by anyone else; the amount
    | is in minor units (100 = 1,00 MAD).
    |
    */

    'payment_test_user' => env('PAYMENT_TEST_USER', 'admin@arabcia.test'),

    'payment_test_amount' => (int) env('PAYMENT_TEST_AMOUNT', 100),

    /*
    | The same rehearsal in euros: 1500 minor units is 15,00 EUR, charged as
    | about 166,67 MAD at the settlement rate — enough to exercise the
    | international-card path and the gateway's non-MAD currency display with a
    | real amount.
    */

    'payment_test_amount_eur' => (int) env('PAYMENT_TEST_AMOUNT_EUR', 1500),

    /*
    | The half-dirham rehearsal: 50 minor units is 0,50 MAD, the smallest
    | amount a rehearsal can raise.
    */

    'payment_test_amount_half' => (int) env('PAYMENT_TEST_AMOUNT_HALF', 50),

];
