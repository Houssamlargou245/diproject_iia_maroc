<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Order;
use App\Services\Payment\CmiGateway;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * What the CMI driver tells the gateway about the payer.
 *
 * The issuing bank judges a foreign payment on the billing block: the country
 * the payer states, the address, the city, the phone. A payment sent with an
 * empty country is a foreign payment with nothing to verify, and a strict
 * bank declines it — which is what a Saudi issuer did to a Moroccan merchant
 * while accepting the same card from Morocco.
 */
class CmiGatewayTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_gateway_is_told_who_the_payer_is(): void
    {
        config(['cmi.merchant_id' => 'TEST', 'cmi.store_key' => 'secret']);

        $order = Order::factory()->create([
            'billing' => [
                'first_name' => 'Turki',
                'last_name' => 'Alkheliwi',
                'organisation' => 'Arab CIIA',
                'address' => '12 Avenue Ibn Sina',
                'city' => 'Rabat',
                'country_iso2' => 'SA',
                'email' => 'turki@example.test',
                'phone' => '+966512345678',
            ],
        ]);

        $request = app(CmiGateway::class)->redirectFor(
            $order,
            'https://iiamaroc.org/conference/paiement/retour/1',
            'https://iiamaroc.org/conference/paiement/annule/1',
        );

        $fields = $request['fields'];

        $this->assertSame('Turki Alkheliwi', $fields['BillToName']);
        $this->assertSame('Arab CIIA', $fields['BillToCompany']);
        $this->assertSame('12 Avenue Ibn Sina', $fields['BillToStreet1']);
        $this->assertSame('Rabat', $fields['BillToCity']);
        $this->assertSame('SA', $fields['BillToCountry']);
        $this->assertSame('turki@example.test', $fields['email']);
        $this->assertSame('+966512345678', $fields['tel']);
    }

    public function test_a_billing_block_without_a_country_is_not_sent_as_one(): void
    {
        config(['cmi.merchant_id' => 'TEST', 'cmi.store_key' => 'secret']);

        $order = Order::factory()->create([
            'billing' => [
                'first_name' => 'Ahmed',
                'last_name' => 'Benali',
                'email' => 'ahmed@example.test',
                'phone' => '+212600000001',
            ],
        ]);

        $request = app(CmiGateway::class)->redirectFor(
            $order,
            'https://iiamaroc.org/conference/paiement/retour/1',
            'https://iiamaroc.org/conference/paiement/annule/1',
        );

        // An empty country is dropped rather than sent as an empty field: the
        // issuer reads a blank country as a payer who did not say where they
        // are, which is what it scores a foreign payment on.
        $this->assertArrayNotHasKey('BillToCountry', $request['fields']);
    }
}
