<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\EditionStatus;
use App\Models\Country;
use App\Models\Edition;
use App\Models\TicketType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The printable basket renders from the shared invoice sheet.
 *
 * The sheet markup and styles were factored into
 * resources/views/invoices/partials/ so the standalone print pages and the
 * in-app order page share one document. This locks that a printable page is
 * still assembled from those partials, rather than falling back to an empty or
 * half-styled page if the include is ever dropped.
 */
class CartPrintPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_printable_basket_renders_the_shared_invoice_sheet(): void
    {
        Country::query()->create([
            'iso2' => 'MA',
            'iso3' => 'MAR',
            'phone_code' => '+212',
            'name_fr' => 'Maroc',
            'name_en' => 'Morocco',
            'name_ar' => 'المغرب',
            'is_active' => true,
        ]);

        $edition = Edition::query()->create([
            'year' => 2026,
            'code' => 'ARABCIA2026',
            'organiser' => 'ARABCIA',
            'host_institute' => 'IIA Maroc',
            'title' => ['fr' => 'ARABCIA 2026', 'en' => 'ARABCIA 2026', 'ar' => 'ARABCIA 2026'],
            'theme' => ['fr' => 'Thème', 'en' => 'Theme', 'ar' => 'الموضوع'],
            'introduction' => ['fr' => 'La conférence.', 'en' => 'The conference.', 'ar' => 'المؤتمر.'],
            'city' => 'Rabat',
            'country_iso2' => 'MA',
            'venue_name' => 'Four Seasons Hotel Rabat',
            'contact_email' => 'contact@arabcia.test',
            'starts_on' => '2026-12-16',
            'ends_on' => '2026-12-17',
            'languages' => ['fr', 'en', 'ar'],
            'status' => EditionStatus::Published,
            'is_current' => true,
            'registration_open' => true,
        ]);

        $ticket = TicketType::factory()->create(['edition_id' => $edition->getKey()]);

        $this->post(route('cart.store'), [
            'ticket_type_id' => $ticket->getKey(),
            'quantity' => 2,
            'member_quantity' => 0,
        ])->assertRedirect(route('cart'));

        $this->get(route('cart.printable'))
            ->assertOk()
            ->assertSee('invoice-doc--page', false)
            ->assertSee('class="sheet"', false)
            ->assertSee('invoice-bg.png', false)
            ->assertSee(__('order.invoice.proforma_title'));

        $pdfResponse = $this->get(route('cart.proforma'));
        $pdfResponse->assertOk()->assertHeader('content-type', 'application/pdf');

        $pdf = $pdfResponse->streamedContent();
        $this->assertStringStartsWith('%PDF-', $pdf);
        $this->assertSame(1, preg_match('/\/Count\s+(\d+)/', $pdf, $pageCount));
        $this->assertSame('1', $pageCount[1], 'The Arabic proforma should fit on one page.');
    }
}
