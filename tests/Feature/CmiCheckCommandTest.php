<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Services\Payment\CmiHasher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * `cmi:check` against a faked gateway: what it posts, and how it reads the
 * two answers CMI gives.
 */
class CmiCheckCommandTest extends TestCase
{
    use RefreshDatabase;

    private const GATEWAY_URL = 'https://testpayment.cmi.co.ma/fim/est3Dgate';

    protected function setUp(): void
    {
        parent::setUp();

        config()->set([
            'cmi.gateway_url' => self::GATEWAY_URL,
            'cmi.merchant_id' => '600000000',
            'cmi.store_key' => 'check-store-key',
        ]);
    }

    public function test_the_card_form_reports_the_credentials_as_accepted(): void
    {
        Http::fake([
            self::GATEWAY_URL => Http::response('<form><input name="pan"><input name="cv2"></form>'),
        ]);

        $this->artisan('cmi:check')
            ->expectsOutputToContain('CMI accepted the request')
            ->assertSuccessful();

        Http::assertSent(function (Request $request): bool {
            $fields = $request->data();

            return $fields['clientid'] === '600000000'
                && $fields['amount'] === '1'
                && (new CmiHasher)->verify($fields, 'check-store-key', $fields['HASH']);
        });

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_a_refusal_is_reported_with_the_gateway_message(): void
    {
        Http::fake([
            self::GATEWAY_URL => Http::response(
                '<form><input type="hidden" name="Response" value="Error">'
                .'<input type="hidden" name="ErrMsg" value="Hash value mismatch"></form>'
            ),
        ]);

        $this->artisan('cmi:check')
            ->expectsOutputToContain('CMI refused the request: Hash value mismatch')
            ->assertFailed();
    }
}
