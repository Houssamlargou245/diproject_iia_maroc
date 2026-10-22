<?php

declare(strict_types=1);

namespace App\Services\Payment;

use App\Models\Order;
use App\Support\Money;
use RuntimeException;

/**
 * What an order is expected to be charged, and in which currency.
 *
 * One calculation, shared by the driver that builds the request, the driver
 * that verifies the response and the service that settles the order, because
 * all three must agree on a single figure.
 *
 * They cannot simply use `$order->total`: this merchant contract settles in
 * MAD only, so a USD order is charged in MAD and CMI signs `amount` as a MAD
 * number while `$order->total` stays in USD. Comparing the two directly
 * rejects every correctly processed foreign-currency payment — the gateway
 * takes the card, we answer `FAILURE`, and the customer is left on CMI's
 * "cliquez ici pour retourner au site marchand" page having been charged.
 */
final class SettlementPlan
{
    /**
     * The figure the gateway charges in minor units, its currency, and the
     * order's own currency when it differs (CMI's `amountCur`/`symbolCur`).
     *
     * @return array{minor: int, currency: string, display: ?string}
     */
    public function forOrder(Order $order): array
    {
        $settlement = strtoupper((string) config('cmi.settlement_currency', 'MAD'));
        $orderCurrency = strtoupper((string) $order->currency);

        if ($orderCurrency === '' || $orderCurrency === $settlement) {
            return ['minor' => $order->total, 'currency' => $settlement, 'display' => null];
        }

        return [
            'minor' => $this->converted($order->total, $orderCurrency, $settlement),
            'currency' => $settlement,
            'display' => $orderCurrency,
        ];
    }

    /**
     * Minor units of `$from` expressed in minor units of `$to`, through
     * `cmi.rates.<from>` (MAD per one unit of the foreign currency).
     *
     * @throws RuntimeException when no rate is configured for `$from` — a
     *                          missing rate must stop the checkout rather
     *                          than charge a figure nobody chose
     */
    private function converted(int $minorUnits, string $from, string $to): int
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
}
