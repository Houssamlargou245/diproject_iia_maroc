<?php

declare(strict_types=1);

namespace App\Services\Payment;

use App\Models\Order;

interface PaymentGateway
{
    /** @return array{gateway_url: string, fields: array<string,string>, method: string} */
    public function redirectFor(Order $order, string $returnUrl, string $cancelUrl): array;

    public function verify(array $payload): VerifiedPayment;

    public function resolveOrder(array $payload): ?Order;
}