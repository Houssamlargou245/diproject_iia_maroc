<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Order;
use App\Services\Payment\CmiGateway;
use DOMDocument;
use Illuminate\Console\Command;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

/**
 * Ask the configured CMI gateway whether it accepts our signed hand-off.
 *
 * Posts the form a buyer's browser would post — built by the real driver,
 * signed with the configured store key — and reads the page CMI answers with.
 * CMI's card form means the merchant id, the store key and the signature were
 * accepted; an error page names what was refused. That is the one part of the
 * round trip that can be checked without a card: authorisation needs a card
 * and 3-D Secure, and the callback needs a public URL.
 *
 * The order is built in memory and never saved, and no card is entered, so
 * nothing is charged and nothing is written to the database.
 */
class CmiCheck extends Command
{
    protected $signature = 'cmi:check
                            {--amount= : Amount in minor units (default: PAYMENT_TEST_AMOUNT)}';

    protected $description = 'Post a signed payment request to the configured CMI gateway and report whether it is accepted';

    /** Field names CMI's card form has used for the card number. */
    private const CARD_FIELDS = ['pan', 'cardnumber', 'card_number', 'ccnumber', 'cc_number', 'ecom_payment_card_number'];

    public function handle(CmiGateway $gateway): int
    {
        try {
            $gateway->assertConfigured();
        } catch (Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $order = $this->throwawayOrder();

        $request = $gateway->redirectFor(
            $order,
            route('payment.return', ['order' => 0]),
            route('payment.cancel', ['order' => 0]),
        );

        $this->line('Gateway:  '.$request['gateway_url']);
        $this->line('Merchant: '.$request['fields']['clientid']);
        $this->line('Amount:   '.$request['fields']['amount'].' ('.$request['fields']['currency'].')');
        $this->line('Type:     '.$request['fields']['TranType']);
        $this->line('oid:      '.$request['fields']['oid']);
        $this->newLine();

        try {
            $response = Http::asForm()->timeout(30)->post($request['gateway_url'], $request['fields']);
        } catch (ConnectionException $e) {
            $this->error('Could not reach the gateway: '.$e->getMessage());

            return self::FAILURE;
        }

        $path = storage_path('logs/cmi-check.html');
        file_put_contents($path, $response->body());

        $this->line('HTTP '.$response->status().', page saved to '.$path);

        [$inputs, $text] = $this->readPage($response->body());

        $error = $this->gatewayError($inputs);

        if ($error !== null) {
            $this->error('CMI refused the request: '.$error);

            return self::FAILURE;
        }

        if (array_intersect(self::CARD_FIELDS, array_map('strtolower', array_keys($inputs))) !== []) {
            $this->info('CMI accepted the request and showed its card form: the merchant id, store key and signature are valid.');

            return self::SUCCESS;
        }

        $this->warn('CMI answered with a page this command does not recognise. Open the saved page to read it.');
        $this->line(Str::limit($text, 400));

        return self::FAILURE;
    }

    /**
     * A 1 MAD order shaped like a real one, never persisted.
     */
    private function throwawayOrder(): Order
    {
        $minor = (int) ($this->option('amount') ?? config('conference.payment_test_amount', 100));

        return (new Order)->forceFill([
            'uuid' => (string) Str::uuid(),
            'total' => $minor,
            'currency' => 'MAD',
            'billing' => [
                'first_name' => 'CMI',
                'last_name' => 'Check',
                'email' => (string) config('conference.payment_test_user', 'admin@arabcia.test'),
                'phone' => '+212600000000',
                'address' => '-',
                'city' => 'Rabat',
                'country_iso2' => 'MA',
            ],
        ]);
    }

    /**
     * The page's form fields and its visible text.
     *
     * @return array{array<string, string>, string}
     */
    private function readPage(string $html): array
    {
        $document = new DOMDocument;
        @$document->loadHTML($html);

        $inputs = [];

        foreach ($document->getElementsByTagName('input') as $input) {
            $name = $input->getAttribute('name');

            if ($name !== '') {
                $inputs[$name] = $input->getAttribute('value');
            }
        }

        $text = trim((string) preg_replace('/\s+/', ' ', (string) $document->textContent));

        return [$inputs, $text];
    }

    /**
     * CMI reports a refused request by posting the verdict on to failUrl, so
     * the refusal arrives as the hidden fields of an auto-submitting form.
     *
     * @param  array<string, string>  $inputs
     */
    private function gatewayError(array $inputs): ?string
    {
        $message = trim($inputs['ErrMsg'] ?? '');
        $code = trim($inputs['ProcReturnCode'] ?? '');

        if ($message === '' && strcasecmp($inputs['Response'] ?? '', 'Error') !== 0 && ($code === '' || $code === '00')) {
            return null;
        }

        return trim(($message !== '' ? $message : 'no message').($code !== '' ? " (code {$code})" : ''));
    }
}
