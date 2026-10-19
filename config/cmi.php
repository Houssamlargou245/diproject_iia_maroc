<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Active driver
    |--------------------------------------------------------------------------
    |
    | "cmi"  the real CMI e-Payment gateway (3D Secure, PreAuth then capture)
    | "test" a local stand-in that walks the identical state machine without a
    |        card, so the whole checkout can be rehearsed offline
    |
    | Never point "cmi" at production credentials in a local environment; the
    | real driver refuses to run when APP_ENV is local and the keys are set,
    | which is why CMI_MERCHANT_ID and CMI_STORE_KEY are left blank here.
    |
    */

    'driver' => env('PAYMENT_DRIVER', 'test'),

    'gateway_url' => env('CMI_GATEWAY_URL', 'https://payment.cmi.co.ma/fim/est3Dgate'),

    'store_key' => env('CMI_STORE_KEY'),
    'merchant_id' => env('CMI_MERCHANT_ID'),

    'store_type' => env('CMI_STORE_TYPE', '3D_PAY_HOSTING'),
    'trans_type' => env('CMI_TRANS_TYPE', 'PreAuth'),

    'language' => env('CMI_LANGUAGE', 'fr'),

    /*
    |--------------------------------------------------------------------------
    | Currencies
    |--------------------------------------------------------------------------
    |
    | CMI wants the ISO-4217 numeric code, not the alpha-3 code that is stored
    | on the order. 504 = MAD, 840 = USD, 978 = EUR. The 2024 build passed the
    | string "MAD" and relied on the gateway defaulting, which silently charged
    | the wrong currency for the USD tariff.
    |
    | EUR is not sold as a tariff; it exists so the admin-only payment rehearsal
    | can exercise the international-card path with a token euro amount.
    |
    */

    'currencies' => [
        'MAD' => ['numeric' => env('CMI_CURRENCY_MAD', '504'), 'decimals' => 2],
        'USD' => ['numeric' => env('CMI_CURRENCY_USD', '840'), 'decimals' => 2],
        'EUR' => ['numeric' => env('CMI_CURRENCY_EUR', '978'), 'decimals' => 2],
    ],

    /*
    |--------------------------------------------------------------------------
    | Callback
    |--------------------------------------------------------------------------
    |
    | CMI posts the result back to a URL we choose. The delay is how long an
    | authorisation is honoured before the customer must be asked to retry, and
    | the allowed drift is the clock difference tolerated between the gateway
    | and this server, which matters because the legacy callback compared raw
    | timestamps and rejected valid payments during DST changes.
    |
    */

    'callback_delay_minutes' => (int) env('CMI_CALLBACK_DELAY_MINUTES', 15),

    'callback_drift_seconds' => (int) env('CMI_CALLBACK_DRIFT_SECONDS', 300),

    'invoice_prefix' => env('CMI_INVOICE_PREFIX', 'ARABCIA-2026'),

    /*
    |--------------------------------------------------------------------------
    | Test driver
    |--------------------------------------------------------------------------
    |
    | Appending this suffix to the return URL makes a deliberate test payment
    | impossible to confuse with a real one in the logs or in a bank statement.
    |
    */

    'test_marker' => env('CMI_TEST_MARKER', '_test'),

];
