<?php

declare(strict_types=1);

namespace App\Services\Payment;

use App\Exceptions\PaymentException;
use App\Models\Order;
use App\Support\Money;
use RuntimeException;

/**
 * The CMI e-Payment 3D Secure driver.
 *
 * Three requests are involved, and confusing them is the classic failure:
 *
 *   1. **Store -> CMI** (`redirectFor`). The browser posts to
 *      payment.cmi.co.ma. The only request the card details touch, and they
 *      go to CMI, never to us.
 *   2. **CMI -> us, server to server** (the `callbackUrl`). The authoritative
 *      one. The order is marked paid here, because the customer can close the
 *      browser before the return redirect arrives.
 *   3. **CMI -> browser -> us** (okUrl/failUrl). A courtesy redirect that
 *      shows a page. It must never be the thing that marks an order paid.
 *
 * The 2024 build conflated 2 and 3: `Ok-Fail.php` inserted the payment row and
 * redirected, while `callback.php` updated `iia_condidat` — a table the
 * conference flow never wrote to — so orders stayed 'encours' forever. Here the
 * callback is the only writer and the return path only reads.
 */
class CmiGateway implements PaymentGateway
{
    public function __construct(
        private readonly CmiHasher $hasher,
    ) {}

    /**
     * Refuse to build a real payment request with no credentials.
     *
     * Checked here rather than at the controller, so a misconfigured
     * environment fails on the checkout with a clear message instead of
     * POSTing an unsigned form to CMI and getting a gateway rejection with no
     * explanation.
     *
     * @throws RuntimeException
     */
    public function assertConfigured(): void
    {
        $problems = [];

        if (blank((string) config('cmi.merchant_id'))) {
            $problems[] = 'CMI_MERCHANT_ID is empty.';
        }

        if (blank((string) config('cmi.store_key'))) {
            $problems[] = 'CMI_STORE_KEY is empty.';
        }

        if ($problems !== []) {
            throw new RuntimeException(
                "The CMI gateway is not configured:\n - ".implode("\n - ", $problems)
            );
        }
    }

    /**
     * {@inheritDoc}
     */
    public function redirectFor(Order $order, string $returnUrl, string $cancelUrl): array
    {
        $this->assertConfigured();

        $billing = (array) $order->billing;

        $settlement = $this->settlementPlan($order);

        $fields = [
            'clientid' => (string) config('cmi.merchant_id'),
            'amount' => $this->toMajorUnitsString($settlement['minor'], $settlement['currency']),
            'currency' => $this->numericCurrency($settlement['currency']),
            'storetype' => (string) config('cmi.store_type', '3D_PAY_HOSTING'),
            'TranType' => (string) config('cmi.trans_type', 'PreAuth'),
            'okUrl' => $returnUrl,
            'failUrl' => $cancelUrl,
            // The full URL has to be known before the request is built, because
            // it goes into the signed payload and a wrong guess invalidates
            // the hash.
            'callbackUrl' => url('/payment/cmi/callback'),
            'shopurl' => rtrim((string) config('app.url'), '/'),
            'lang' => $this->gatewayLanguage(),
            'hashAlgorithm' => 'ver3',
            'encoding' => 'UTF-8',
            // How long the payment page stays valid before the customer has to
            // restart. CMI's own store accepts 30–2700 seconds; omitting it
            // leaves CMI with a zero-length session, which the customer sees as
            // "Transaction expiré. Merci d'essayer à nouveau (99)" the moment
            // the page opens.
            'sessionTimeout' => (string) config('cmi.session_timeout', '1800'),
            // Ask CMI to run the server-to-server confirmation POST. Without
            // this the callback — the only thing that settles an order — is
            // never sent, and every order stays "awaiting payment".
            'callbackResponse' => (string) config('cmi.callback_response', 'true'),
            // `oid` must be unique per transaction. The order's uuid is used
            // rather than its autoincrement id so a response cannot be used to
            // count orders.
            'oid' => $order->uuid.'-'.dechex(time()),   // 36 + 1 + 8 = 45 chars
            // CMI echoes `rnd` back untouched, so including it in the hash is
            // what stops a captured response being replayed against a
            // different order.
            'rnd' => bin2hex(random_bytes(8)),
            'BillToName' => $this->billToName($order),
            'BillToEmail' => (string) ($billing['email'] ?? $order->user?->email ?? ''),
            'BillToStreet1' => (string) ($billing['address'] ?? ''),
            'BillToCity' => (string) ($billing['city'] ?? ''),
            'BillToCountry' => (string) ($billing['country_iso2'] ?? ''),
            'tel' => (string) ($billing['phone'] ?? $order->user?->phone ?? ''),
        ];

        // A MAD-only contract cannot charge the customer's own currency, so the
        // amount above is the MAD figure and the order's currency is shown
        // alongside it instead of being submitted as `currency`.
        if ($settlement['display'] !== null) {
            $fields['amountCur'] = $this->toMajorUnitsString($order->total, $settlement['display']);
            $fields['symbolCur'] = $settlement['display'];
        }

        $fields['HASH'] = $this->hasher->hash($fields, (string) config('cmi.store_key'));

        return [
            'gateway_url' => (string) config('cmi.gateway_url'),
            'fields' => $fields,
            'method' => 'POST',
        ];
    }

    /**
     * {@inheritDoc}
     *
     * @throws PaymentException when the signature does not verify, or when the
     *                          signed amount is not the order's own
     */
    public function verify(array $payload): VerifiedPayment
    {
        $this->assertConfigured();

        // CMI returns the signature under several spellings depending on the
        // endpoint, so all are accepted. None of them are part of the hash,
        // which is why `HASH` can safely be excluded from it.
        $received = $payload['HASH'] ?? $payload['hash'] ?? $payload['Hash'] ?? null;

        if (! $this->hasher->verify(
            $payload,
            (string) config('cmi.store_key'),
            is_string($received) ? $received : null,
        )) {
            // Thrown rather than returned: the caller must not be able to
            // mistake this for "the payment simply did not succeed".
            throw PaymentException::invalidSignature();
        }

        // Only now, on a verified payload, are these fields trusted enough to
        // act on. Reading them before this point is what would let a forged
        // callback choose which order to mark paid.
        $order = $this->resolveOrder($payload);
        $returnCode = isset($payload['ProcReturnCode']) ? (string) $payload['ProcReturnCode'] : null;

        if ($returnCode !== '00') {
            return VerifiedPayment::failed(
                returnCode: $returnCode ?? 'absent',
                message: $this->stringOrNull($payload['ErrMsg'] ?? null)
                    ?? __('order.payment.generic_failure'),
                // Carried on a failure too, so the settlement service can attach
                // the decline to the order it belongs to instead of dropping it
                // and leaving that order looking abandoned.
                reference: $this->stringOrNull($payload['oid'] ?? null),
                transactionId: $this->stringOrNull($payload['TransId'] ?? null),
            );
        }

        // Amount cross-check. CMI echoes what it processed; if that differs
        // from the order's own total, the transaction is not one we may settle
        // even though it is genuinely signed. Accepting it would let a
        // customer pay 100 MAD against a 7 500 MAD order and receive a place.
        //
        // Compared against the *settlement* figure, not the order's own total:
        // on a MAD-only contract the gateway charges the converted MAD amount,
        // so a USD order legitimately echoes MAD and would otherwise look like
        // a mismatch on a correct payment.
        if ($order !== null && isset($payload['amount'])) {
            $settlement = $this->settlementPlan($order);
            $claimed = $this->fromMajorUnitsString((string) $payload['amount'], $settlement['currency']);

            if ($claimed !== $settlement['minor']) {
                throw PaymentException::amountMismatch($settlement['minor'], $claimed);
            }
        }

        return VerifiedPayment::authorised(
            returnCode: $returnCode,
            transactionId: $this->stringOrNull($payload['TransId'] ?? null),
            reference: $this->stringOrNull($payload['oid'] ?? null),
            maskedPan: $this->stringOrNull($payload['MaskedPan'] ?? $payload['maskedCreditCard'] ?? null),
            cardBrand: $this->stringOrNull($payload['EXTRA_CARDBRAND'] ?? null),
            cardIssuer: $this->stringOrNull($payload['EXTRA_CARDISSUER'] ?? null),
            authCode: $this->stringOrNull($payload['AuthCode'] ?? null),
            amount: $order?->total,
        );
    }

    /**
     * {@inheritDoc}
     */
public function resolveOrder(array $payload): ?Order
{
    $oid = $payload['oid'] ?? $payload['OrderId'] ?? null;

    if (! is_string($oid) || strlen($oid) < 36) {
        return null;
    }

    return Order::query()->where('uuid', substr($oid, 0, 36))->first();
}
    // --- Amount handling -------------------------------------------------

    /**
     * What CMI is actually charged, and what the customer is shown.
     *
     * The contract settles MAD-only, so only a MAD order is submitted as-is.
     * Any other currency is charged in MAD — via `cmi.rates` — and shown to the
     * customer in its own currency through CMI's conversion display. Sending
     * the foreign ISO code as `currency` is refused with "Devise non autorisée
     * pour ce marchand. (99)".
     *
     * @return array{minor: int, currency: string, display: ?string}
     */
    private function settlementPlan(Order $order): array
    {
        $settlement = strtoupper((string) config('cmi.settlement_currency', 'MAD'));
        $orderCurrency = strtoupper((string) $order->currency);

        if ($orderCurrency === '' || $orderCurrency === $settlement) {
            return ['minor' => $order->total, 'currency' => $settlement, 'display' => null];
        }

        return [
            'minor' => $this->toSettlementMinor($order->total, $orderCurrency, $settlement),
            'currency' => $settlement,
            'display' => $orderCurrency,
        ];
    }

    /**
     * Convert a foreign amount to the settlement currency.
     *
     * Thrown rather than silently defaulted when a rate is missing: charging
     * the customer an unconverted figure would be worse than a checkout that
     * plainly refuses to start.
     */
    private function toSettlementMinor(int $minorUnits, string $from, string $to): int
    {
        $rate = (float) config("cmi.rates.{$from}", 0);

        if ($rate <= 0) {
            throw new RuntimeException(
                "No CMI settlement rate is configured for {$from}; add cmi.rates.{$from}."
            );
        }

        $majorUnits = $minorUnits / (10 ** Money::exponent($from));
        $settled = $majorUnits * $rate;

        return (int) round($settled * (10 ** Money::exponent($to)));
    }

    /**
     * CMI expects the amount in major units (dirhams, not centimes) as a plain
     * number with no grouping character: 7 500,00 MAD is "7500" — never
     * "750000" — and 0,09 EUR is "0.09". A fractional amount must keep its
     * decimal part; truncating it sent "0" for a 9-cent order, which CMI
     * rejected with 3D-1008 ("Montant est manquant ou en format invalide")
     * after the gateway had already shown the customer "0 euro".
     *
     * CMI accepts "." or "," as the separator and exactly two decimals
     * (`^\d+(\.\d{2})?$`), which is what every supported currency's exponent
     * produces here. Money is stored in minor units throughout this
     * application, so the conversion happens here and nowhere else.
     */
    private function toMajorUnitsString(int $minorUnits, string $currency): string
    {
        $exponent = Money::exponent($currency);
        $divisor = 10 ** $exponent;

        $whole = intdiv($minorUnits, $divisor);
        $fraction = $minorUnits % $divisor;

        if ($fraction === 0) {
            return (string) $whole;
        }

        return $whole.'.'.str_pad((string) $fraction, $exponent, '0', STR_PAD_LEFT);
    }

    /**
     * Inverse of {@see toMajorUnitsString()}.
     *
     * Rounded rather than truncated: a gateway that sent "7500.4" for a
     * 750 040-cent order should match, while truncating here would report a
     * mismatch on a correct payment.
     *
     * The separator is normalised first because CMI echoes the amount in the
     * format it was sent and accepts either "." or ","; without this a
     * comma-formatted echo such as "0,09" would cast to 0.0 and be reported as
     * an amount mismatch on a correct payment.
     */
    private function fromMajorUnitsString(string $major, string $currency): int
    {
        $normalised = str_replace(',', '.', trim($major));

        return (int) round(((float) $normalised) * (10 ** Money::exponent($currency)));
    }

    private function numericCurrency(string $currency): string
    {
        $currency = strtoupper($currency);

        return (string) (config("cmi.currencies.{$currency}.numeric")
            ?? config('cmi.currencies.MAD.numeric', '504'));
    }

    /**
     * CMI accepts fr/en/ar. Anything else is sent through and rendered by the
     * gateway in a language the customer did not choose.
     */
    private function gatewayLanguage(): string
    {
        $locale = app()->getLocale();

        return in_array($locale, ['fr', 'en', 'ar'], true) ? $locale : 'fr';
    }

    private function billToName(Order $order): string
    {
        $billing = (array) $order->billing;

        $name = trim(
            ($billing['first_name'] ?? '').' '.($billing['last_name'] ?? '')
        );

        return $name !== ''
            ? $name
            : (string) ($order->user?->displayName() ?? '');
    }

    private function stringOrNull(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $string = trim((string) $value);

        return $string === '' ? null : $string;
    }
}
