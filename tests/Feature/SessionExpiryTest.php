<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Edition;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * What happens when a form is submitted with a token that no longer matches.
 *
 * A CSRF token belongs to the session that rendered the form. When the session
 * that rendered it is gone — expired while the page sat open, or replaced by a
 * sign-in in another tab — the token no longer matches anything, and Laravel
 * refuses the request with a 419.
 *
 * The default answer is a bare "Page Expired" page: the visitor is told the
 * page is gone, with no link back to the form and no way to resend what they
 * had typed. On the authenticator-app page that is the worst possible moment
 * for it, because the code they had just read off their phone is the one thing
 * they cannot easily reproduce.
 *
 * So a mismatch sends the visitor back to the form instead, with a token that
 * matches the session they are in now, and a notice saying why.
 */
class SessionExpiryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Laravel skips the CSRF check while the application is under test, so
        // the mismatch this test is about cannot happen by itself. Leaving the
        // 'testing' environment is what turns the check back on.
        $this->app['env'] = 'local';

        // The refusal is reported like any other exception. The log is not what
        // this test is about.
        config(['logging.default' => 'null']);
    }

    public function test_a_submission_with_a_stale_token_returns_the_visitor_to_the_form(): void
    {
        $user = $this->unconfirmedUser();

        $response = $this->actingAs($user)
            ->withHeaders(['referer' => route('totp.setup')])
            ->post(route('totp.confirm'), ['code' => '123456']);

        $response->assertRedirect(route('totp.setup'));
        $response->assertSessionHas('session_expired', true);
    }

    /**
     * The point of the redirect: the form is there, and it works.
     *
     * A page that comes back with a token that does not match the session would
     * put the visitor straight back where they started.
     */
    public function test_the_form_the_visitor_lands_on_carries_a_usable_token(): void
    {
        $user = $this->unconfirmedUser();

        $this->actingAs($user)
            ->withHeaders(['referer' => route('totp.setup')])
            ->post(route('totp.confirm'), ['code' => '123456']);

        $response = $this->actingAs($user)->get(route('totp.setup'));

        $response->assertOk();
        $response->assertSee('name="_token" value="'.$this->app['session.store']->token().'"', escape: false);
    }

    /**
     * The notice has to be visible, or the redirect is just a silent bounce.
     */
    public function test_the_visitor_is_told_why_they_were_sent_back(): void
    {
        $user = $this->unconfirmedUser();

        $this->actingAs($user)
            ->withHeaders(['referer' => route('totp.setup')])
            ->post(route('totp.confirm'), ['code' => '123456']);

        $response = $this->actingAs($user)->get(route('totp.setup'));

        $response->assertSee(__('auth.session_expired'));
    }

    /**
     * A refusal is still a refusal for a machine.
     *
     * An API client asking for JSON gets the status code it can act on, not a
     * redirect to a page it cannot read.
     */
    public function test_a_json_request_still_gets_the_419(): void
    {
        $user = $this->unconfirmedUser();

        $response = $this->actingAs($user)
            ->postJson(route('totp.confirm'), ['code' => '123456']);

        $response->assertStatus(419);
    }

    /**
     * The check itself is untouched: a request without a valid token still
     * fails. The redirect is a way back, not a way around.
     */
    public function test_the_code_is_not_accepted_without_a_valid_token(): void
    {
        $user = $this->unconfirmedUser();

        $this->actingAs($user)
            ->withHeaders(['referer' => route('totp.setup')])
            ->post(route('totp.confirm'), ['code' => '123456']);

        $this->assertNull($user->fresh()->totp_confirmed_at);
    }

    // --- Helpers ----------------------------------------------------------

    private function unconfirmedUser(): User
    {
        Edition::factory()->create([
            'year' => 2026,
            'is_current' => true,
            'status' => 'published',
        ]);

        return User::factory()->unconfirmedTotp()->create();
    }
}
