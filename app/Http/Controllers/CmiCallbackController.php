<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Exceptions\PaymentException;
use App\Services\Payment\CmiGateway;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CmiCallbackController
{
    public function __invoke(Request $request, CmiGateway $gateway): Response
    {
        $payload = $request->all();

        Log::info('cmi.callback.in', collect($payload)->except(['HASH', 'hash'])->all());

        try {
            // 1. Signature check (throws PaymentException if the hash is invalid
            //    or the amount doesn't match the order).
            $verified = $gateway->verify($payload);

            // 2. Find the order from the oid ("<uuid>-<hex time>").
            $order = $gateway->resolveOrder($payload);

            if ($order === null) {
                Log::warning('cmi.callback.unknown_order', ['oid' => $payload['oid'] ?? null]);

                return $this->reply('FAILURE');
            }

            // 3. Settle idempotently: CMI may call this more than once.
            DB::transaction(function () use ($order, $payload) {
                $order = $order->newQuery()->whereKey($order->getKey())->lockForUpdate()->first();

                if ($order->status === 'paid') { // adapt to your Order status/enum
                    return;
                }

                if (($payload['ProcReturnCode'] ?? null) === '00') {
                    // TODO: your real settlement logic, e.g.
                    // $this->checkout->markPaid($order, $payload['TransId'] ?? null);
                    // dispatch invoice/ticket jobs here (queued, keep this fast)
                } else {
                    // TODO: mark the attempt as failed, e.g.
                    // $this->checkout->markFailed($order, $payload['ErrMsg'] ?? null);
                }
            });

            // CMI expects exactly this body, plain text, HTTP 200.
            return $this->reply('ACTION=POSTAUTH');
        } catch (PaymentException $e) {
            Log::warning('cmi.callback.rejected', ['error' => $e->getMessage()]);

            return $this->reply('FAILURE');
        } catch (\Throwable $e) {
            report($e);

            return $this->reply('FAILURE');
        }
    }

    private function reply(string $body): Response
    {
        return response($body, 200)->header('Content-Type', 'text/plain');
    }
}