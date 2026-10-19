<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Edition;
use App\Models\TicketType;
use Database\Seeders\EditionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The seeded 2026 edition must match the brief.
 *
 * Prices are the reason this file exists. They were wrong in the first draft
 * (6 375 / 7 500 MAD and 675 / 850 USD) because a member rate had been invented
 * as "85% of standard" on top of a standard price that was itself read from the
 * wrong column of the brief. Nothing caught it: the seeder ran clean, the
 * pricing page rendered, and the number was simply wrong on a page whose whole
 * job is to state the number.
 *
 * The authoritative source is sheet "Modifications 2026", row 15:
 *
 *     C15  7 500/8 500 MAD et 750/850 EUR     (2024: EUR)
 *     D15  7 500/8 500 MAD et 750/850 USD     (2026 target)
 *
 * The lower figure is the IIA member rate, the higher is standard.
 */
class EditionSeederTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Edition::forgetCurrent();
    }

    protected function tearDown(): void
    {
        Edition::forgetCurrent();

        parent::tearDown();
    }

    public function test_mad_prices_match_the_brief(): void
    {
        $mad = $this->ticket('STANDARD');

        $this->assertSame('MAD', $mad->currency);
        $this->assertSame(504, (int) $mad->currency_numeric);

        // Minor units: 7 500,00 MAD and 8 500,00 MAD.
        $this->assertSame(750000, $mad->price_member, 'Member rate must be 7 500,00 MAD.');
        $this->assertSame(850000, $mad->price_standard, 'Standard rate must be 8 500,00 MAD.');
    }

    public function test_usd_prices_match_the_brief(): void
    {
        $usd = $this->ticket('STANDARD_USD');

        $this->assertSame('USD', $usd->currency);
        $this->assertSame(840, (int) $usd->currency_numeric);

        // Minor units: 750,00 USD and 850,00 USD.
        $this->assertSame(75000, $usd->price_member, 'Member rate must be 750,00 USD.');
        $this->assertSame(85000, $usd->price_standard, 'Standard rate must be 850,00 USD.');
    }

    /**
     * The member rate is a separate column, not a discount on the standard rate.
     *
     * Asserted as a strict inequality rather than a ratio on purpose: the old
     * bug was a plausible-looking 85%, and pinning the ratio is what let it look
     * intentional. If a future brief sets a real percentage, this still holds.
     */
    public function test_the_member_rate_is_cheaper_than_the_standard_rate(): void
    {
        foreach (['STANDARD', 'STANDARD_USD'] as $code) {
            $type = $this->ticket($code);

            $this->assertLessThan(
                $type->price_standard,
                $type->price_member,
                "{$code}: the member rate must be lower than the standard rate.",
            );
        }
    }

    /**
     * The member rate is chosen server-side from a Membership record, so it must
     * be a real column and not derived at read time.
     */
    public function test_prices_are_stored_in_minor_units_as_integers(): void
    {
        $mad = $this->ticket('STANDARD');

        $this->assertIsInt($mad->price_member);
        $this->assertIsInt($mad->price_standard);

        // A stored float here is the 2024 `iia_offre.prix_adherent` TEXT bug:
        // 0.1 + 0.2 style drift makes totals that differ by a centime.
        $this->assertSame(0, $mad->price_member % 1);
        $this->assertSame(0, $mad->price_standard % 1);
    }

    public function test_the_seeder_is_idempotent(): void
    {
        $this->seed(EditionSeeder::class);
        $before = TicketType::query()->orderBy('code')->pluck('price_standard', 'code')->all();

        $this->seed(EditionSeeder::class);
        $after = TicketType::query()->orderBy('code')->pluck('price_standard', 'code')->all();

        $this->assertSame($before, $after, 'Re-seeding must not duplicate or alter ticket types.');
        $this->assertCount(2, $after);
    }

    private function ticket(string $code): TicketType
    {
        $this->seed(EditionSeeder::class);

        return TicketType::query()
            ->where('code', $code)
            ->firstOrFail();
    }
}
