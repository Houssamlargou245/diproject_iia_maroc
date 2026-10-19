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

    /*
    |--------------------------------------------------------------------------
    | Gateway host
    |--------------------------------------------------------------------------
    |
    | CMI runs two separate hosts. A merchant id and store key issued for the
    | test kit authenticate only against the test host, and vice versa.
    | Pointing test credentials at production (or production credentials at
    | the test host) makes every authorization fail with "Autorisation de
    | paiement non permise (57)", even for a plain 1 MAD sale.
    |
    |   production  https://payment.cmi.co.ma/fim/est3Dgate
    |   test        https://testpayment.cmi.co.ma/fim/est3Dgate
    |
    */

    'gateway_url' => env('CMI_GATEWAY_URL', 'https://payment.cmi.co.ma/fim/est3Dgate'),

    'store_key' => env('CMI_STORE_KEY'),
    'merchant_id' => env('CMI_MERCHANT_ID'),

    'store_type' => env('CMI_STORE_TYPE', '3D_PAY_HOSTING'),
    'trans_type' => env('CMI_TRANS_TYPE', 'PreAuth'),

    'language' => env('CMI_LANGUAGE', 'fr'),

    /*
    |--------------------------------------------------------------------------
    | Payment-page session
    |--------------------------------------------------------------------------
    |
    | `session_timeout` is how long the CMI payment page stays valid before the
    | customer must restart the payment. CMI accepts 30 to 2700 seconds and the
    | documented default is 1800. Leaving the field out entirely makes CMI apply
    | a zero-length session, which is surfaced as "Transaction expiré. Merci
    | d'essayer à nouveau (99)" as soon as the page opens.
    |
    | `callback_response` makes CMI perform the server-to-server confirmation
    | POST to `callbackUrl`. Without it no callback is ever sent, so no order is
    | ever settled even after a successful authorisation.
    |
    */

    'session_timeout' => (int) env('CMI_SESSION_TIMEOUT', 1800),
    'callback_response' => env('CMI_CALLBACK_RESPONSE', 'true'),

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
    | Settlement currency
    |--------------------------------------------------------------------------
    |
    | This merchant contract settles in MAD only. A non-MAD order is therefore
    | charged in MAD and shown to the customer in its own currency through
    | CMI's conversion display (`amountCur` + `symbolCur`); submitting the
    | foreign ISO code as `currency` is refused with "Devise non autorisée pour
    | ce marchand. (99)".
    |
    | `rates` is MAD per one unit of the foreign currency — the same pricing
    | convention as the public tariffs (7 500 MAD = 750 USD, so USD is 10;
    | 0,09 EUR = 1 MAD, so EUR is about 11,11).
    |
    */

    'settlement_currency' => env('CMI_SETTLEMENT_CURRENCY', 'MAD'),

    'rates' => [
        'USD' => (float) env('CMI_RATE_USD', 10.0),
        'EUR' => (float) env('CMI_RATE_EUR', 11.1111),
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
