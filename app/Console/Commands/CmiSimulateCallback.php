<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Order;
use App\Services\Payment\CmiHasher;
use App\Support\Money;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class CmiSimulateCallback extends Command
{
    protected $signature = 'cmi:simulate {order : Order uuid}
                            {--amount= : Amount in major units (default: order total)}
                            {--fail : Send ProcReturnCode=99}
                            {--tamper : Change the amount after signing}';

    protected $description = 'Post a signed fake CMI callback to this app\'s callback URL';

    public function handle(CmiHasher $hasher): int
    {
        $order = Order::where('uuid', $this->argument('order'))->firstOrFail();

        $currency = strtoupper((string) ($order->currency ?: 'MAD'));
        $amount = $this->option('amount')
            ?? (string) ($order->total / (10 ** Money::exponent($currency)));

        $fields = [
            'clientid'       => (string) config('cmi.merchant_id'),
            'oid'            => $order->uuid.'-'.dechex(time()),
            'amount'         => (string) $amount,
            'ProcReturnCode' => $this->option('fail') ? '99' : '00',
            'Response'       => $this->option('fail') ? 'Declined' : 'Approved',
            'TransId'        => 'SIM'.random_int(100000, 999999),
            'AuthCode'       => (string) random_int(100000, 999999),
            'MaskedPan'      => '5200 00** **** 1111',
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