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
        private readonly SettlementPlan $plan,
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
        $settlement = $this->plan->forOrder($order);

        $fields = [
            'clientid' => (string) config('cmi.merchant_id'),
            'amount' => Money::decimal($settlement['minor'], $settlement['currency']),
            'currency' => Money::numericCode($settlement['currency']),
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
            // The billing snapshot stores the organisation under
            // `organisation`; reading `company` here sent every payment with
            // an empty company, which is one more field the issuer had to
            // treat as unverifiable.
            'BillToCompany' => (string) ($billing['organisation'] ?? $billing['company'] ?? '-'),
            'BillToStreet1' => (string) ($billing['address'] ?? '-'),
            'BillToCity' => (string) ($billing['city'] ?? '-'),
            'BillToStateProv' => (string) ($billing['state'] ?? '-'),
            'BillToPostalCode' => (string) ($billing['postal_code'] ?? '-'),
            'BillToCountry' => (string) ($billing['country_iso2'] ?? ''),
            'tel' => (string) ($billing['phone'] ?? $order->user?->phone ?? ''),
        ];

        if ($settlement['display'] !== null) {
            $fields['amountCur'] = Money::decimal($order->total, $settlement['display']);
            $fields['symbolCur'] = $settlement['display'];
        }

        // Drop empty fields: the form posted to CMI must contain exactly the
        // fields that were hashed.
        $fields = array_filter($fields, fn ($v) => $v !== '' && $v !== null);

        $fields['HASH'] = $this->hasher->hash($fields, (string) config('cmi.store_key'));

        // Operational fields only. The request itself carries the payer's
        // name, email and phone, and this line is read by whoever answers a
        // payment question — the amount, the currency, the order id and the
        // country are what a support question is actually answered from.
        Log::info('cmi.out', [
            'oid' => $fields['oid'] ?? null,
            'amount' => $fields['amount'] ?? null,
            'currency' => $fields['currency'] ?? null,
            'tran_type' => $fields['TranType'] ?? null,
            'store_type' => $fields['storetype'] ?? null,
            'lang' => $fields['lang'] ?? null,
            'country' => $fields['BillToCountry'] ?? null,
            'ok_url' => $fields['okUrl'] ?? null,
        ]);

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
            // A rejected signature is otherwise undiagnosable: the payload and
            // both digests are needed to see which value the two sides signed
            // differently. The payer's identity is left out of it — the name,
            // the email, the phone and the address are redacted, and no card
            // number is present either, since CMI sends only the masked form
            // and the store key itself is never logged.
            Log::warning('cmi.signature_mismatch', [
                'fields' => collect($payload)->except([
                    'HASH', 'hash', 'Hash',
                    'email', 'tel',
                    'BillToName', 'BillToCompany', 'BillToStreet1', 'BillToCity',
                    'BillToStateProv', 'BillToPostalCode',
                ])->all(),
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
            $settlement = $this->plan->forOrder($order);
            // CMI signs the settlement figure, so it is read in the settlement
            // currency — never in the order's own.
            $claimed = Money::parse((string) $payload['amount'], $settlement['currency']) ?? 0;

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
