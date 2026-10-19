<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Services\Payment\CmiHasher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CmiCallbackTest extends TestCase
{
    use RefreshDatabase;

    private function signedPayload(Order $order, array $overrides = []): array
    {
        $fields = array_merge([
            'clientid'       => config('cmi.merchant_id'),
            'oid'            => $order->uuid.'-'.dechex(time()),
            'amount'         => '1',            // must equal the order total in MAD
            'ProcReturnCode' => '00',
            'Response'       => 'Approved',
            'TransId'        => 'TEST123456',
            'AuthCode'       => '123456',
            'MaskedPan'      => '5200 00** **** 1111',
        ], $overrides);

        $fields['HASH'] = app(CmiHasher::class)->hash($fields, (string) config('cmi.store_key'));

        return $fields;
    }

    public function test_valid_callback_is_acknowledged(): void
    {
        config(['cmi.merchant_id' => 'TEST', 'cmi.store_key' => 'secret']);
        $order = Order::factory()->create(); // adjust: total must match 'amount'

        $this->post('/payment/cmi/callback', $this->signedPayload($order))
            ->assertOk()
            ->assertSeeText('ACTION=POSTAUTH');
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