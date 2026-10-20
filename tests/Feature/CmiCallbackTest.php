<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Services\Payment\CmiHasher;
use App\Services\Payment\SettlementPlan;
use App\Support\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class CmiCallbackTest extends TestCase
{
    use RefreshDatabase;

    private function signedPayload(Order $order, array $overrides = []): array
    {
        $settlement = app(SettlementPlan::class)->forOrder($order);

        $fields = array_merge([
            'clientid' => config('cmi.merchant_id'),
            'oid' => $order->uuid.'-'.dechex(time()),
            // The signed amount is what the gateway was asked to charge: the
            // settlement figure, in the settlement currency.
            'amount' => Money::decimal($settlement['minor'], $settlement['currency']),
            'ProcReturnCode' => '00',
            'Response' => 'Approved',
            'TransId' => 'TEST123456',
            'AuthCode' => '123456',
            'MaskedPan' => '5200 00** **** 1111',
        ], $overrides);

        $fields['HASH'] = app(CmiHasher::class)->hash($fields, (string) config('cmi.store_key'));

        return $fields;
    }

    public function test_valid_callback_is_acknowledged(): void
    {
        config(['cmi.merchant_id' => 'TEST', 'cmi.store_key' => 'secret', 'cmi.trans_type' => 'PreAuth']);
        $order = Order::factory()->create();

        $this->post('/payment/cmi/callback', $this->signedPayload($order))
            ->assertOk()
            ->assertSeeText('ACTION=POSTAUTH');
    }

    public function test_a_foreign_currency_order_settles_on_the_mad_amount_that_was_charged(): void
    {
        Mail::fake();
        config(['cmi.merchant_id' => 'TEST', 'cmi.store_key' => 'secret', 'cmi.trans_type' => 'PreAuth']);

        // A 750 USD order. The store's MAD-only contract makes CMI charge
        // 7 500 MAD, and `amount` is signed in MAD — so a callback compared
        // against `$order->total` (75 000 USD cents) read it as five times the
        // price, rejected a payment that was processed correctly, and left the
        // customer on CMI's notification page having been charged.
        $order = Order::factory()->create([
            'currency' => 'USD',
            'currency_numeric' => 840,
            'total' => 75000,
        ]);

        $this->post('/payment/cmi/callback', $this->signedPayload($order))
            ->assertOk()
            ->assertSeeText('ACTION=POSTAUTH');

        $this->assertSame(OrderStatus::Paid, $order->refresh()->status);
    }

    public function test_tampered_hash_is_rejected(): void
    {
        config(['cmi.merchant_id' => 'TEST', 'cmi.store_key' => 'secret']);
        $order = Order::factory()->create();

        $payload = $this->signedPayload($order);
        $payload['amount'] = '999'; // changed after signing

        $this->post('/payment/cmi/callback', $payload)
            ->assertOk()
            ->assertSeeText('FAILURE');
    }
}
