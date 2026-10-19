<?php

declare(strict_types=1);

namespace App\Services\Payment;

use App\Exceptions\PaymentException;
use App\Models\Order;
use App\Support\Money;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * The CMI e-Payment 3D Secure driver.
 *
 *   1. Store -> CMI (redirectFor): the browser posts to payment.cmi.co.ma.
 *   2. CMI -> us, server to server (callbackUrl): the only thing that settles an order.
 *   3. CMI -> browser -> us (okUrl/failUrl): a courtesy page, never settles.
 */
class CmiGateway implements PaymentGateway
{
    public function __construct(
        private readonly CmiHasher $hasher,
    ) {}

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
            'callbackUrl' => url('/payment/cmi/callback'),
            'shopurl' => rtrim((string) config('app.url'), '/'),
            'lang' => $this->gatewayLanguage(),
            'hashAlgorithm' => 'ver3',
            'encoding' => 'UTF-8',
            'sessionTimeout' => (string) config('cmi.session_timeout', '1800'),
            // Ask CMI to run the server-to-server confirmation. Without it no
            // callback is ever sent, so an authorised payment never settles.
            'callbackResponse' => (string) config('cmi.callback_response', 'true'),
            // Return the customer straight to okUrl/failUrl when the payment is
            // processed, instead of parking them on CMI's "Cliquez ici pour
            // retourner au site marchand" notification page.
            'autoRedirect' => (string) config('cmi.auto_redirect', 'true'),
            // Unique per attempt: order uuid (36 chars) + "-" + hex timestamp.
            'oid' => $order->uuid.'-'.dechex(time()),
            'rnd' => bin2hex(random_bytes(8)),
            'BillToName' => $this->billToName($order),
            'email' => (string) ($billing['email'] ?? $order->user?->email ?? ''),
            'BillToCompany' => (string) ($billing['company'] ?? '-'),
            'BillToStreet1' => (string) ($billing['address'] ?? '-'),
            'BillToCity' => (string) ($billing['city'] ?? '-'),
            'BillToStateProv' => (string) ($billing['state'] ?? '-'),
            'BillToPostalCode' => (string) ($billing['postal_code'] ?? '-'),
            'BillToCountry' => (string) ($billing['country_iso2'] ?? ''),
            'tel' => (string) ($billing['phone'] ?? $order->user?->phone ?? ''),
        ];

        if ($settlement['display'] !== null) {
            $fields['amountCur'] = $this->toMajorUnitsString($order->total, $settlement['display']);
            $fields['symbolCur'] = $settlement['display'];
        }

        // Drop empty fields: the form posted to CMI must contain exactly the
        // fields that were hashed.
        $fields = array_filter($fields, fn ($v) => $v !== '' && $v !== null);

        $fields['HASH'] = $this->hasher->hash($fields, (string) config('cmi.store_key'));

        // TEMPORARY: remove once payments work. Contains no secret.
        \Log::info('cmi.out', collect($fields)->except('HASH')->all());

        return [
            'gateway_url' => (string) config('cmi.gateway_url'),
            'fields' => $fields,
            'method' => 'POST',
        ];
    }

    /**
     * @throws PaymentException when the signature does not verify, or when the
     *                          signed amount is not the order's own
     */
    public function verify(array $payload): VerifiedPayment
    {
        $this->assertConfigured();

        $received = $payload['HASH'] ?? $payload['hash'] ?? $payload['Hash'] ?? null;

        if (! $this->hasher->verify(
            $payload,
            (string) config('cmi.store_key'),
            is_string($received) ? $received : null,
        )) {
            // TEMPORARY: remove once the gateway round trip is confirmed. A
            // rejected signature is otherwise undiagnosable — the payload and
            // both digests are needed to see which value the two sides signed
            // differently. No card number is present; CMI sends only the masked
            // form, and the store key itself is never logged.
            Log::warning('cmi.signature_mismatch', [
                'fields' => collect($payload)->except(['HASH', 'hash', 'Hash'])->all(),
                'computed' => $this->hasher->hash($payload, (string) config('cmi.store_key')),
                'received' => is_string($received) ? $received : null,
            ]);

            throw PaymentException::invalidSignature();
        }

        $order = $this->resolveOrder($payload);
        $returnCode = isset($payload['ProcReturnCode']) ? (string) $payload['ProcReturnCode'] : null;

        if ($returnCode !== '00') {
            return VerifiedPayment::failed(
                returnCode: $returnCode ?? 'absent',
                message: $this->stringOrNull($payload['ErrMsg'] ?? null)
                    ?? __('order.payment.generic_failure'),
                reference: $this->stringOrNull($payload['oid'] ?? null),
                transactionId: $this->stringOrNull($payload['TransId'] ?? null),
            );
        }

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
     * The oid is "<order uuid>-<hex time>", so the order is the first 36 chars.
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

    /** @return array{minor: int, currency: string, display: ?string} */
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

    private function toSettlementMinor(int $minorUnits, string $from, string $to): int
    {
        $rate = (float) config("cmi.rates.{$from}", 0);

        if ($rate <= 0) {
            throw new RuntimeException(
                "No CMI settlement rate is configured for {$from}; add cmi.rates.{$from}."
            );
        }

        $majorUnits = $minorUnits / (10 ** Money::exponent($from));

        return (int) round($majorUnits * $rate * (10 ** Money::exponent($to)));
    }

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

    private function gatewayLanguage(): string
    {
        $locale = app()->getLocale();

        return in_array($locale, ['fr', 'en', 'ar'], true) ? $locale : 'fr';
    }

    private function billToName(Order $order): string
    {
        $billing = (array) $order->billing;

        $name = trim(($billing['first_name'] ?? '').' '.($billing['last_name'] ?? ''));

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
