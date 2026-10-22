<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Edition;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TmpDebugTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app['env'] = 'local';
        config(['logging.default' => 'single', 'session.driver' => 'database']);

        \Illuminate\Support\Facades\DB::listen(function ($query): void {
            if (! str_contains($query->sql, 'sessions')) {
                return;
            }
            $trace = [];
            foreach (debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 14) as $frame) {
                $trace[] = ($frame['class'] ?? '').($frame['type'] ?? '').$frame['function'];
            }
            \Illuminate\Support\Facades\Log::info('sql', ['sql' => $query->sql, 'trace' => $trace]);
        });
    }

    public function test_debug(): void
    {
        Edition::factory()->create(['year' => 2026, 'is_current' => true, 'status' => 'published']);
        $user = User::factory()->unconfirmedTotp()->create();

        $this->actingAs($user)
            ->withHeaders(['referer' => route('totp.setup')])
            ->post(route('totp.confirm'), ['code' => '123456']);

        $store = $this->app['session.store'];
        dump([
            'driver' => config('session.driver'),
            'has_session_expired' => $store->get('session_expired'),
            'flash' => $store->get('_flash'),
            'keys' => array_keys($store->all()),
            'same_as_request' => spl_object_id($store) === spl_object_id(\Illuminate\Support\Facades\Request::instance()->session()),
        ]);

        $store->save();

        dump([
            'after_manual_save' => $store->get('session_expired'),
            'flash_after' => $store->get('_flash'),
        ]);
    }
}
