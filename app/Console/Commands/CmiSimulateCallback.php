<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Order;
use App\Services\Payment\CmiHasher;
use App\Services\Payment\SettlementPlan;
use App\Support\Money;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class CmiSimulateCallback extends Command
{
    protected $signature = 'cmi:simulate {order : Order uuid}
                            {--amount= : Amount in major units (default: the settlement amount CMI signs)}
                            {--fail : Send ProcReturnCode=99}
                            {--tamper : Change the amount after signing}';

    protected $description = 'Post a signed fake CMI callback to this app\'s callback URL';

    public function handle(CmiHasher $hasher, SettlementPlan $plan): int
    {
        $order = Order::where('uuid', $this->argument('order'))->firstOrFail();

        // The default imitates the gateway: CMI signs the settlement figure,
        // so a USD order is simulated with its MAD amount.
        $settlement = $plan->forOrder($order);
        $amount = $this->option('amount')
            ?? Money::decimal($settlement['minor'], $settlement['currency']);

        $fields = [
            'clientid' => (string) config('cmi.merchant_id'),
            'oid' => $order->uuid.'-'.dechex(time()),
            'amount' => (string) $amount,
            'ProcReturnCode' => $this->option('fail') ? '99' : '00',
            'Response' => $this->option('fail') ? 'Declined' : 'Approved',
            'TransId' => 'SIM'.random_int(100000, 999999),
            'AuthCode' => (string) random_int(100000, 999999),
            'MaskedPan' => '5200 00** **** 1111',
        ];

        $fields['HASH'] = $hasher->hash($fields, (string) config('cmi.store_key'));

        if ($this->option('tamper')) {
            $fields['amount'] = '999';
        }

        $url = url('/payment/cmi/callback');
        $this->info("POST {$url}");

        $response = Http::asForm()->post($url, $fields);

        $this->line('HTTP '.$response->status());
        $this->line('Body: '.$response->body());

        return self::SUCCESS;
    }
}
