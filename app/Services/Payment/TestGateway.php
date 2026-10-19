<?php

declare(strict_types=1);

namespace App\Services\Payment;

use App\Exceptions\PaymentException;
use App\Models\Order;
use App\Support\Money;

/**
 * A local stand-in for the real gateway.
 *
 * Walks the identical state machine without a card, so the checkout, the
 * callback, the return redirect and the reconciliation can all be rehearsed
 * offline. That is the point: the 2024 build could only be tested by moving
 * real money, which is how it shipped a payment path nobody had ever watched
 * succeed.
 *
 * The simulated page posts to our own callback, signed with the same hasher
 * and the same store key as the real driver, so switching `PAYMENT_DRIVER`
 * from `test` to `cmi` changes the destination URL and nothing else. A test
 * driver that faked the signature would prove nothing about the real one.
 */
class TestGateway implements PaymentGateway
{
    public function __construct(
        private readonly CmiHasher $hasher,
    ) {}

    /** A key used only to sign the rehearsal, never a production secret. */
    private function storeKey(): string
    {
        return (string) (config('cmi.store_key') ?: 'test-driver-key');
    }

    /**
     * {@inheritDoc}
     */
    public function redirectFor(Order $order, string $returnUrl, string $cancelUrl): array
    {
        $currency = (string) $order->currency;
        $divisor = 10 ** Money::exponent($currency);

        $fields = [
            'clientid' => 'TESTMERCHANT',
            'amount' => (string) intdiv($order->total, $divisor),
            'currency' => (string) (config('cmi.currencies.'.strtoupper($currency).'.numeric') ?? '504'),
            'storetype' => (string) config('cmi.store_type', '3D_PAY_HOSTING'),
            'TranType' => (string) config('cmi.trans_type', 'PreAuth'),
            'okUrl' => $returnUrl,
            'failUrl' => $cancelUrl,
            'callbackUrl' => url('/payment/cmi/callback'),
            'shopurl' => rtrim((string) config('app.url'), '/'),
            'lang' => app()->getLocale(),
            'hashAlgorithm' => 'ver3',
            'encoding' => 'UTF-8',
            // Kept identical to the real driver so the two hand-offs differ
            // only by destination, which is the whole point of this stand-in.
            'sessionTimeout' => (string) config('cmi.session_timeout', '1800'),
            'callbackResponse' => (string) config('cmi.callback_response', 'true'),
            'oid' => $order->uuid,
            'rnd' => bin2hex(random_bytes(8)),
            'BillToName' => (string) ($order->billing['first_name'] ?? $order->user?->displayName() ?? ''),
            'tel' => (string) ($order->billing['phone'] ?? ''),
        ];

        $fields['HASH'] = $this->hasher->hash($fields, $this->storeKey());

        return [
            // A local rehearsal page, not CMI. It renders buttons for
            // "approved" and "declined" and posts a correctly signed payload
            // to the real callback route.
            'gateway_url' => url('/payment/test/'.$order->uuid),
            'fields' => $fields,
            'method' => 'POST',
        ];
    }

    /**
     * {@inheritDoc}
     *
     * @throws PaymentException
     */
    public function verify(array $payload): VerifiedPayment
    {
        $received = $payload['HASH'] ?? $payload['hash'] ?? null;

        if (! $this->hasher->verify($payload, $this->storeKey(), is_string($received) ? $received : null)) {
            throw PaymentException::invalidSignature();
        }

        $returnCode = isset($payload['ProcReturnCode']) ? (string) $payload['ProcReturnCode'] : null;

        if ($returnCode !== '00') {
            return VerifiedPayment::failed(
                returnCode: $returnCode ?? 'absent',
                message: $this->stringOrNull($payload['ErrMsg'] ?? null)
                    ?? __('order.payment.generic_failure'),
                // Carried on a failure too, so the settlement service can attach
                // the decline to the order it belongs to instead of dropping it.
                reference: $this->stringOrNull($payload['oid'] ?? null),
                transactionId: $this->stringOrNull($payload['TransId'] ?? null),
            );
        }

        return VerifiedPayment::authorised(
            returnCode: $returnCode,
            transactionId: $this->stringOrNull($payload['TransId'] ?? null),
            reference: $this->stringOrNull($payload['oid'] ?? null),
            maskedPan: '************2585',
            cardBrand: 'VISA',
            authCode: 'AUTHTEST',
        );
    }

    /**
     * {@inheritDoc}
     */
    public function resolveOrder(array $payload): ?Order
    {
        $oid = $payload['oid'] ?? null;

        if (! is_string($oid) || $oid === '') {
            return null;
        }

        return Order::query()->where('uuid', $oid)->first();
    }

    /**
     * Build a signed payload for a rehearsal, either outcome.
     *
     * Used by the local gateway page. Exposed as a method rather than built
     * inline in a Blade template so the signature is produced by exactly the
     * code the callback verifies with.
     *
     * @return array<string, string>
     */
    public function simulate(Order $order, bool $approved): array
    {
        $divisor = 10 ** Money::exponent((string) $order->currency);

        $payload = [
            'oid' => $order->uuid,
            'amount' => (string) intdiv($order->total, $divisor),
            'ProcReturnCode' => $approved ? '00' : '05',
            'TransId' => 'TEST'.strtoupper(substr(str_replace('-', '', $order->uuid), 0, 8)),
            'MaskedPan' => $approved ? '************2585' : '',
            'EXTRA_CARDBRAND' => $approved ? 'VISA' : '',
            'AuthCode' => $approved ? 'AUTHTEST' : '',
            'ErrMsg' => $approved ? '' : 'Refused by the issuing bank',
            'storeKey' => $this->storeKey(),
        ];

        $payload['HASH'] = $this->hasher->hash($payload, $this->storeKey());

        return $payload;
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
