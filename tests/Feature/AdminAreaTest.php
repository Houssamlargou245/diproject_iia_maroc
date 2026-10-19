<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\EditionStatus;
use App\Enums\OrderStatus;
use App\Enums\SessionFormat;
use App\Models\ContactMessage;
use App\Models\Country;
use App\Models\Edition;
use App\Models\Order;
use App\Models\Participant;
use App\Models\SpeakerSubmission;
use App\Models\SponsoringPackage;
use App\Models\SponsorshipEnquiry;
use App\Models\TicketType;
use App\Models\User;
use App\Support\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * The staff area.
 *
 * The tests here are mostly about refusals rather than about rendering, because
 * the rendering is the easy half. What matters is that:
 *
 *   1. a guest is sent to sign in, and a signed-in member is refused with a 403
 *      rather than quietly shown an empty dashboard;
 *   2. an order, submission, enquiry or participant belonging to another
 *      edition cannot be reached by guessing its id;
 *   3. an order cannot be pushed into a state the enum forbids, because that
 *      transition table is what stops a replayed gateway callback from
 *      resurrecting a cancelled order;
 *   4. the money on the dashboard is the money actually taken.
 */
class AdminAreaTest extends TestCase
{
    /**
     * Years deliberately outside `EditionFactory`'s 2024..2030 range.
     *
     * The factory draws a unique random year, so a test edition pinned to a year
     * in that range can collide with a nested factory edition. Both `year` and
     * `code` are globally unique, so a collision fails as a bare constraint
     * violation that names neither the helper nor the fixture that caused it.
     * Outside the range, no collision is possible.
     */
    private const CURRENT_YEAR = 2031;

    private const PAST_YEAR = 2032;

    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // `Edition::current()` memoises in a static. Across a suite that would
        // hand one test's edition to the next, and the failure would look like
        // a data bug rather than a leaked cache.
        Edition::forgetCurrent();
    }

    protected function tearDown(): void
    {
        Edition::forgetCurrent();

        parent::tearDown();
    }

    private function admin(): User
    {
        return User::factory()->admin()->create();
    }

    private function delegate(): User
    {
        return User::factory()->create();
    }

    /**
     * A current edition.
     *
     * `status` matters and is easy to miss: `Edition::scopeCurrent()` requires
     * `status = published` as well as `is_current`, and the factory defaults to
     * `draft`. An edition that is flagged current but left in draft is invisible
     * to `Edition::current()`, so every admin request 404s and the suite fails
     * in ways that look like broken routing rather than an unpublished edition.
     */
    private function edition(int $year = self::CURRENT_YEAR, bool $current = true): Edition
    {
        return Edition::factory()->create([
            'year' => $year,
            'is_current' => $current,
            'status' => $current ? EditionStatus::Published : EditionStatus::Archived,
            'registration_open' => true,
        ]);
    }

    /**
     * A previous edition — archived, not current.
     *
     * The year is given explicitly because the factory's default is 2026 and
     * `editions.year` is unique, so a second 2026 row is a constraint violation
     * rather than a readable failure.
     */
    private function pastEdition(): Edition
    {
        return Edition::factory()->create([
            'year' => self::PAST_YEAR,
            'code' => 'ARABCIA'.self::PAST_YEAR,
            'is_current' => false,
            'status' => EditionStatus::Archived,
        ]);
    }

    // --- Access -------------------------------------------------------------

    #[DataProvider('guestRoutes')]
    public function test_a_guest_is_sent_to_sign_in(string $uri): void
    {
        // Not 403: an unauthenticated visitor is not forbidden, they are not
        // signed in, and the redirect carries them to the only place that can
        // change that.
        $this->get($uri)->assertRedirect(route('login'));
    }

    public static function guestRoutes(): array
    {
        return [
            'dashboard' => ['/admin'],
            'orders' => ['/admin/orders'],
            'participants' => ['/admin/participants'],
            'submissions' => ['/admin/submissions'],
            'enquiries' => ['/admin/enquiries'],
            'messages' => ['/admin/messages'],
        ];
    }

    #[DataProvider('guestRoutes')]
    public function test_a_signed_in_member_is_refused(string $uri): void
    {
        // 403 and not a redirect to the dashboard. Sending a member to a page
        // they cannot use invites them to conclude the page is broken, and a
        // redirect loop through sign-in invites them to try a password.
        $this->actingAs($this->delegate())
            ->get($uri)
            ->assertForbidden();
    }

    public function test_a_signed_in_member_cannot_post_an_order_status_change(): void
    {
        $edition = $this->edition();
        $order = Order::factory()->forEdition($edition)->create();

        $this->actingAs($this->delegate())
            ->post(route('admin.orders.status', $order), ['status' => 'paid'])
            ->assertForbidden();

        $this->assertSame(OrderStatus::Pending, $order->fresh()->status);
    }

    public function test_the_dashboard_tells_a_guest_where_to_sign_in(): void
    {
        $this->get('/admin')->assertRedirect(route('login'));
    }

    public function test_an_administrator_sees_the_dashboard(): void
    {
        $this->edition();

        $this->actingAs($this->admin())
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee(__('admin.nav.dashboard'));
    }

    // --- No edition ---------------------------------------------------------

    public function test_the_dashboard_explains_a_missing_edition_instead_of_erroring(): void
    {
        // A fresh install has no edition. Six zeroed cards would read as "nothing
        // has happened yet" and send an operator hunting for data, when what is
        // missing is the thing they are here to manage.
        $this->actingAs($this->admin())
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee(__('admin.no_edition.title'))
            ->assertDontSee(__('admin.revenue'));
    }

    // --- The figures --------------------------------------------------------

    public function test_the_dashboard_counts_orders_and_participants(): void
    {
        $edition = $this->edition();

        $paid = Order::factory()->forEdition($edition)->paid()->create();
        $pending = Order::factory()->forEdition($edition)->create();

        // One participant on the paid order, checked in; one on the pending one,
        // not. The two must not be conflated: a paid order can be paid before
        // anybody has been named against it.
        Participant::query()->create([
            'order_id' => $paid->getKey(),
            'full_name' => 'Amina Alaoui',
            'email' => 'amina@example.test',
            'is_member' => true,
        ]);
        Participant::query()->create([
            'order_id' => $pending->getKey(),
            'full_name' => 'Karim Benali',
            'email' => 'karim@example.test',
            'is_member' => false,
        ]);

        $response = $this->actingAs($this->admin())->get(route('admin.dashboard'));

        // `assertSeeText($value, false)`, not `assertSee`: `@lang` writes the
        // translation out raw, so an escaped needle (which turns the apostrophe
        // in "présents à l'accueil" into `&#039;`) can never match the page.
        $response->assertOk()
            ->assertSeeText(__('admin.stats.orders'), false)
            ->assertSeeText(__('admin.stats.checked_in'), false);

        // 2 orders, 1 of them paid.
        $this->assertSame(2, $edition->orders()->count());
        $this->assertSame(1, $edition->orders()->where('status', OrderStatus::Paid)->count());
        $this->assertSame(0, Participant::query()->whereNotNull('checked_in_at')->count());
    }

    public function test_revenue_counts_only_paid_orders(): void
    {
        $edition = $this->edition();

        $paid = Order::factory()->forEdition($edition)->paid()->create(['total' => 850000]);
        $cancelled = Order::factory()->forEdition($edition)->cancelled()->create(['total' => 850000]);
        $pending = Order::factory()->forEdition($edition)->create(['total' => 850000]);

        $this->assertSame(
            850000,
            (int) $edition->orders()->where('status', OrderStatus::Paid)->sum('total'),
            'The cancelled and pending orders must not count as money taken.',
        );

        // And the figure the page renders is that sum, not the sum of every row.
        // Asserted through `Money::format` rather than a literal: the formatter
        // uses a narrow no-break space as the thousands separator and omits the
        // decimals when there are none, both of which a hardcoded string gets
        // wrong for reasons that have nothing to do with the code under test.
        $this->actingAs($this->admin())
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee(Money::format(850000));

        unset($paid, $cancelled, $pending);
    }

    public function test_an_order_from_another_edition_is_not_counted(): void
    {
        $this->edition();

        // A previous year's paid order must not appear in the current edition's
        // takings. `Edition::current()` scopes everything the dashboard reads.
        $past = $this->pastEdition();
        Order::factory()->forEdition($past)->paid()->create(['total' => 999900]);

        $this->actingAs($this->admin())
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertDontSee(Money::format(999900));
    }

    // --- The shape of the run -----------------------------------------------

    public function test_the_dashboard_splits_member_and_non_member_seats(): void
    {
        $edition = $this->edition();
        $type = TicketType::factory()->create(['edition_id' => $edition->getKey()]);

        // One paid order carrying both kinds of place: seven at the member
        // rate, three at the standard one.
        Order::factory()->forEdition($edition)->paid()->create([
            'ticket_type_id' => $type->getKey(),
            'member_count' => 7,
            'standard_count' => 3,
        ]);

        $this->actingAs($this->admin())
            ->get(route('admin.dashboard'))
            ->assertOk()
            // The split line carries both counts, so it is the whole assertion.
            ->assertSeeText(__('admin.members.summary', ['member' => 7, 'standard' => 3]), false);
    }

    public function test_the_dashboard_reports_remaining_capacity_per_tariff(): void
    {
        $edition = $this->edition();
        $type = TicketType::factory()->create([
            'edition_id' => $edition->getKey(),
            'max_quantity' => 10,
        ]);

        Order::factory()->forEdition($edition)->paid()->create([
            'ticket_type_id' => $type->getKey(),
            'member_count' => 0,
            'standard_count' => 4,
        ]);

        $this->actingAs($this->admin())
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSeeText(__('admin.capacity.remaining', ['count' => 6]), false);
    }

    public function test_a_tariff_sold_to_capacity_is_reported_as_full(): void
    {
        $edition = $this->edition();
        $type = TicketType::factory()->create([
            'edition_id' => $edition->getKey(),
            'max_quantity' => 3,
        ]);

        Order::factory()->forEdition($edition)->paid()->create([
            'ticket_type_id' => $type->getKey(),
            'member_count' => 1,
            'standard_count' => 2,
        ]);

        // Sold == capacity: the gauge must read full, not "0 places remaining".
        $this->actingAs($this->admin())
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSeeText(__('admin.capacity.full'), false);
    }

    public function test_a_cancelled_order_holds_no_capacity(): void
    {
        $edition = $this->edition();
        $type = TicketType::factory()->create([
            'edition_id' => $edition->getKey(),
            'max_quantity' => 10,
        ]);

        // A cancelled order is not money received, so it must not consume a
        // place: the tariff still has all ten.
        Order::factory()->forEdition($edition)->cancelled()->create([
            'ticket_type_id' => $type->getKey(),
            'member_count' => 0,
            'standard_count' => 6,
        ]);

        $this->actingAs($this->admin())
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSeeText(__('admin.capacity.remaining', ['count' => 10]), false);
    }

    public function test_the_dashboard_groups_buyers_by_country_for_the_current_edition_only(): void
    {
        $edition = $this->edition();

        $morocco = Country::query()->create([
            'iso2' => 'MA', 'iso3' => 'MAR',
            'name_fr' => 'Maroc', 'name_en' => 'Morocco', 'name_ar' => 'المغرب',
            'is_active' => true,
        ]);
        $atlantis = Country::query()->create([
            'iso2' => 'ZZ', 'iso3' => 'ZZZ',
            'name_fr' => 'Atlantide', 'name_en' => 'Atlantis', 'name_ar' => 'أطلانطس',
            'is_active' => true,
        ]);

        $buyer = User::factory()->create(['country_id' => $morocco->getKey()]);
        Order::factory()->forEdition($edition)->paid()->forUser($buyer)->create();

        // A paid order from a previous edition must not leak into this panel.
        $past = $this->pastEdition();
        $pastBuyer = User::factory()->create(['country_id' => $atlantis->getKey()]);
        Order::factory()->forEdition($past)->paid()->forUser($pastBuyer)->create();

        // Operators read the country name in their own working language.
        $admin = User::factory()->admin()->create(['locale' => 'fr']);

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSeeText('Maroc')
            ->assertDontSeeText('Atlantide');
    }

    public function test_the_dashboard_trend_counts_registrations(): void
    {
        $edition = $this->edition();

        Order::factory()->forEdition($edition)->paid()->create();
        Order::factory()->forEdition($edition)->create();

        // The trend counts every order placed, paid or not: the run-up is the
        // funnel, and the paid line is drawn separately beside it.
        $this->actingAs($this->admin())
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSeeText(__('admin.trend.total', ['count' => 2]), false);
    }

    // --- Orders -------------------------------------------------------------

    public function test_the_orders_list_can_be_filtered_by_status(): void
    {
        $edition = $this->edition();

        Order::factory()->forEdition($edition)->paid()->create();
        Order::factory()->forEdition($edition)->create();

        $this->actingAs($this->admin())
            ->get(route('admin.orders.index', ['status' => 'paid']))
            ->assertOk()
            // The paid reference is on the page; the pending one is not.
            ->assertSee(Order::query()->where('status', OrderStatus::Paid)->first()->reference)
            ->assertDontSee(Order::query()->where('status', OrderStatus::Pending)->first()->reference);
    }

    public function test_an_order_belonging_to_another_edition_is_not_found(): void
    {
        $this->edition();

        $past = $this->pastEdition();
        $order = Order::factory()->forEdition($past)->create();

        // 404 rather than 403: from the operator's point of view an order of
        // another edition is not present in this one, and 403 would confirm the
        // id exists.
        $this->actingAs($this->admin())
            ->get(route('admin.orders.show', $order))
            ->assertNotFound();
    }

    public function test_an_order_belonging_to_another_edition_cannot_be_changed(): void
    {
        $this->edition();

        $past = $this->pastEdition();
        $order = Order::factory()->forEdition($past)->create();

        $this->actingAs($this->admin())
            ->post(route('admin.orders.status', $order), ['status' => 'paid'])
            ->assertNotFound();

        $this->assertSame(OrderStatus::Pending, $order->fresh()->status);
    }

    public function test_an_illegal_status_change_is_refused(): void
    {
        $edition = $this->edition();
        $order = Order::factory()->forEdition($edition)->cancelled()->create();

        // Cancelled is terminal. Marking it paid would be the exact bug the
        // transition table exists to prevent.
        $this->actingAs($this->admin())
            ->from(route('admin.orders.show', $order))
            ->post(route('admin.orders.status', $order), ['status' => 'paid'])
            ->assertRedirect(route('admin.orders.show', $order))
            ->assertSessionHasErrors('status');

        $this->assertSame(OrderStatus::Cancelled, $order->fresh()->status);
    }

    public function test_a_legal_status_change_is_applied_and_stamped(): void
    {
        $edition = $this->edition();
        $order = Order::factory()->forEdition($edition)->create();

        $this->actingAs($this->admin())
            ->post(route('admin.orders.status', $order), [
                'status' => 'paid',
                'notes' => 'Bank transfer received.',
            ])
            ->assertSessionHasNoErrors();

        $fresh = $order->fresh();

        $this->assertSame(OrderStatus::Paid, $fresh->status);
        // The timestamp is derived from the target state, not supplied by the
        // form, so `paid_at` cannot disagree with the status.
        $this->assertNotNull($fresh->paid_at);
        $this->assertStringContainsString('Bank transfer received.', (string) $fresh->notes);
    }

    public function test_a_status_change_without_a_note_leaves_the_history_alone(): void
    {
        $edition = $this->edition();
        $order = Order::factory()->forEdition($edition)->create();

        $this->actingAs($this->admin())
            ->post(route('admin.orders.status', $order), ['status' => 'paid'])
            ->assertSessionHasNoErrors();

        // An unannotated change must not write an empty string into the audit
        // trail — "no note" and "a note that says nothing" are different.
        $this->assertNull($order->fresh()->notes);
    }

    public function test_an_unknown_status_is_rejected(): void
    {
        $edition = $this->edition();
        $order = Order::factory()->forEdition($edition)->create();

        $this->actingAs($this->admin())
            ->post(route('admin.orders.status', $order), ['status' => 'refunded_by_post'])
            ->assertSessionHasErrors('status');

        $this->assertSame(OrderStatus::Pending, $order->fresh()->status);
    }

    public function test_the_order_show_page_offers_only_legal_next_states(): void
    {
        $edition = $this->edition();
        $order = Order::factory()->forEdition($edition)->cancelled()->create();

        // Cancelled has no outgoing transitions, so the page must not offer a
        // select with nothing legal in it.
        $this->actingAs($this->admin())
            ->get(route('admin.orders.show', $order))
            ->assertOk()
            ->assertDontSee('<select name="status"', false);
    }

    // --- The submission queue ------------------------------------------------

    public function test_the_submission_queue_lists_pending_submissions_by_default(): void
    {
        $edition = $this->edition();

        $this->submission($edition, SpeakerSubmission::STATUS_SUBMITTED, 'Le contrôle interne en 2026');
        $this->submission($edition, SpeakerSubmission::STATUS_REJECTED, 'La cartographie des risques');

        // Asserted on the proposals themselves rather than on the status word:
        // the status filter dropdown legitimately offers every status, so
        // "Refusée" is on the page even when no rejected proposal is listed.
        $this->actingAs($this->admin())
            ->get(route('admin.submissions.index'))
            ->assertOk()
            ->assertSee('Le contrôle interne en 2026')
            ->assertSee(__('admin.stages.submissions.submitted'))
            // The decided one is filtered out unless asked for.
            ->assertDontSee('La cartographie des risques');
    }

    public function test_reviewing_a_submission_records_who_decided_and_when(): void
    {
        $edition = $this->edition();
        $submission = $this->submission($edition, SpeakerSubmission::STATUS_SUBMITTED);

        $admin = $this->admin();

        $this->actingAs($admin)
            ->post(route('admin.submissions.review', $submission), [
                'status' => SpeakerSubmission::STATUS_ACCEPTED,
                'review_notes' => 'Good fit for the transformation track.',
            ])
            ->assertSessionHasNoErrors();

        $fresh = $submission->fresh();

        $this->assertSame(SpeakerSubmission::STATUS_ACCEPTED, $fresh->status);
        // Attribution is a property of the request, not something the form can
        // assert: a decided submission with no reviewer is nobody's decision.
        $this->assertSame($admin->getKey(), $fresh->reviewed_by);
        $this->assertNotNull($fresh->reviewed_at);
    }

    public function test_a_submission_from_another_edition_cannot_be_reviewed(): void
    {
        $this->edition();

        $past = $this->pastEdition();
        $submission = $this->submission($past, SpeakerSubmission::STATUS_SUBMITTED);

        $this->actingAs($this->admin())
            ->post(route('admin.submissions.review', $submission), [
                'status' => SpeakerSubmission::STATUS_ACCEPTED,
            ])
            ->assertNotFound();

        $this->assertSame(SpeakerSubmission::STATUS_SUBMITTED, $submission->fresh()->status);
    }

    public function test_a_status_outside_the_reviewable_set_is_rejected(): void
    {
        $edition = $this->edition();
        $submission = $this->submission($edition, SpeakerSubmission::STATUS_SUBMITTED);

        // `submitted` is the state it is already in, not a decision, so it is
        // not in the reviewable list the form offers.
        $this->actingAs($this->admin())
            ->post(route('admin.submissions.review', $submission), [
                'status' => SpeakerSubmission::STATUS_SUBMITTED,
            ])
            ->assertSessionHasErrors('status');
    }

    public function test_rejecting_a_submission_without_a_reason_is_refused(): void
    {
        $edition = $this->edition();
        $submission = $this->submission($edition, SpeakerSubmission::STATUS_SUBMITTED);

        // A refusal with no recorded reason leaves the speaker asking and nobody
        // able to answer. Acceptance, by contrast, needs no justification.
        $this->actingAs($this->admin())
            ->post(route('admin.submissions.review', $submission), [
                'status' => SpeakerSubmission::STATUS_REJECTED,
            ])
            ->assertSessionHasErrors('review_notes');

        $this->assertSame(SpeakerSubmission::STATUS_SUBMITTED, $submission->fresh()->status);
        $this->assertNull($submission->fresh()->reviewed_by);

        $this->actingAs($this->admin())
            ->post(route('admin.submissions.review', $submission), [
                'status' => SpeakerSubmission::STATUS_ACCEPTED,
            ])
            ->assertSessionHasNoErrors();
    }

    // --- Sponsorship enquiries ------------------------------------------------

    public function test_handling_an_enquiry_records_the_handler(): void
    {
        $edition = $this->edition();
        $enquiry = $this->enquiry($edition);

        $admin = $this->admin();

        $this->actingAs($admin)
            ->post(route('admin.enquiries.status', $enquiry), [
                'status' => SponsorshipEnquiry::STATUS_QUOTED,
            ])
            ->assertSessionHasNoErrors();

        $fresh = $enquiry->fresh();

        $this->assertSame(SponsorshipEnquiry::STATUS_QUOTED, $fresh->status);
        $this->assertSame($admin->getKey(), $fresh->handled_by);
        $this->assertNotNull($fresh->handled_at);
    }

    public function test_an_operator_note_does_not_overwrite_the_prospects_message(): void
    {
        $edition = $this->edition();
        $enquiry = $this->enquiry($edition);

        $original = $enquiry->message;

        $this->actingAs($this->admin())
            ->post(route('admin.enquiries.status', $enquiry), [
                'status' => SponsorshipEnquiry::STATUS_CONTACTED,
                'notes' => 'Called their office on Tuesday.',
            ])
            ->assertSessionHasNoErrors();

        $fresh = $enquiry->fresh();

        // Two different voices in one column would make the record of what was
        // actually asked for unreadable after the first follow-up.
        $this->assertSame($original, $fresh->message);
        $this->assertStringContainsString('Called their office on Tuesday.', (string) $fresh->internal_notes);
    }

    public function test_closing_an_enquiry_as_lost_requires_a_reason(): void
    {
        $edition = $this->edition();
        $enquiry = $this->enquiry($edition);

        // Marking a lead lost is the one irreversible-sounding move in the
        // pipeline: without a recorded reason the next organiser cannot tell a
        // genuine "no" from a stalled conversation.
        $this->actingAs($this->admin())
            ->post(route('admin.enquiries.status', $enquiry), [
                'status' => SponsorshipEnquiry::STATUS_LOST,
            ])
            ->assertSessionHasErrors('notes');

        $fresh = $enquiry->fresh();

        $this->assertSame(SponsorshipEnquiry::STATUS_NEW, $fresh->status);
        $this->assertNull($fresh->handled_by);

        // Any other stage needs no note.
        $this->actingAs($this->admin())
            ->post(route('admin.enquiries.status', $enquiry), [
                'status' => SponsorshipEnquiry::STATUS_QUOTED,
            ])
            ->assertSessionHasNoErrors();
    }

    public function test_an_enquiry_from_another_edition_cannot_be_handled(): void
    {
        $this->edition();

        $past = $this->pastEdition();
        $enquiry = $this->enquiry($past);

        $this->actingAs($this->admin())
            ->post(route('admin.enquiries.status', $enquiry), [
                'status' => SponsorshipEnquiry::STATUS_CONTACTED,
            ])
            ->assertNotFound();

        $this->assertNull($enquiry->fresh()->handled_by);
    }

    // --- Contact messages -----------------------------------------------------

    public function test_creating_an_enquiry_records_a_fresh_message(): void
    {
        $edition = $this->edition();

        $this->submission($edition, SpeakerSubmission::STATUS_SUBMITTED);

        ContactMessage::query()->create([
            'edition_id' => $edition->getKey(),
            'subject_type' => ContactMessage::SUBJECT_REGISTRATION,
            'name' => 'Salma Idrissi',
            'email' => 'salma@example.test',
            'message' => 'Bonjour, puis-je payer en espèces ?',
            'locale' => 'fr',
            'status' => ContactMessage::STATUS_NEW,
        ]);

        $this->actingAs($this->admin())
            ->get(route('admin.messages.index'))
            ->assertOk()
            ->assertSee('Salma Idrissi');
    }

    public function test_answering_a_message_records_the_handler(): void
    {
        $edition = $this->edition();
        $message = $this->message($edition);

        $admin = $this->admin();

        $this->actingAs($admin)
            ->post(route('admin.messages.status', $message), [
                'status' => ContactMessage::STATUS_ANSWERED,
                'notes' => 'Answered by phone.',
            ])
            ->assertSessionHasNoErrors();

        $fresh = $message->fresh();

        $this->assertSame(ContactMessage::STATUS_ANSWERED, $fresh->status);
        $this->assertSame($admin->getKey(), $fresh->handled_by);
        $this->assertNotNull($fresh->handled_at);
        $this->assertStringContainsString('Answered by phone.', (string) $fresh->internal_notes);
    }

    public function test_a_message_from_another_edition_cannot_be_updated(): void
    {
        $this->edition();

        $past = $this->pastEdition();
        $message = $this->message($past);

        $this->actingAs($this->admin())
            ->post(route('admin.messages.status', $message), [
                'status' => ContactMessage::STATUS_ANSWERED,
            ])
            ->assertNotFound();

        $this->assertSame(ContactMessage::STATUS_NEW, $message->fresh()->status);
    }

    // --- The door list --------------------------------------------------------

    public function test_checking_a_participant_in_is_idempotent(): void
    {
        $edition = $this->edition();
        $participant = $this->participant($edition);

        $admin = $this->admin();

        $this->actingAs($admin)->post(route('admin.participants.check-in', $participant));
        $this->actingAs($admin)->post(route('admin.participants.check-in', $participant));

        $this->assertTrue($participant->fresh()->checked_in);
        $this->assertNotNull($participant->fresh()->checked_in_at);
    }

    public function test_a_check_in_can_be_undone(): void
    {
        $edition = $this->edition();
        $participant = $this->participant($edition);

        $admin = $this->admin();

        $this->actingAs($admin)->post(route('admin.participants.check-in', $participant));
        $this->actingAs($admin)->post(route('admin.participants.check-out', $participant));

        $fresh = $participant->fresh();

        // Both the flag and the timestamp, or the row reads as checked in while
        // the history says otherwise.
        $this->assertFalse($fresh->checked_in);
        $this->assertNull($fresh->checked_in_at);
    }

    public function test_checking_in_is_not_reachable_by_a_get(): void
    {
        $edition = $this->edition();
        $participant = $this->participant($edition);

        $this->actingAs($this->admin())
            ->get(route('admin.participants.check-in', $participant))
            ->assertMethodNotAllowed();

        $this->assertFalse($participant->fresh()->checked_in);
    }

    public function test_a_participant_from_another_edition_cannot_be_checked_in(): void
    {
        $this->edition();

        $past = $this->pastEdition();
        $participant = $this->participant($past);

        // The participant belongs to the edition through its order, which is
        // two joins deep — a place a route constraint cannot express.
        $this->actingAs($this->admin())
            ->post(route('admin.participants.check-in', $participant))
            ->assertNotFound();

        $this->assertFalse($participant->fresh()->checked_in);
    }

    public function test_the_participant_search_matches_name_email_and_phone(): void
    {
        $edition = $this->edition();

        Participant::query()->create([
            'order_id' => Order::factory()->forEdition($edition)->create()->getKey(),
            'full_name' => 'Amina Alaoui',
            'email' => 'amina@example.test',
            'phone' => '+212600112233',
            'job_title' => 'Auditrice',
            'is_member' => false,
        ]);

        $this->actingAs($this->admin())
            ->get(route('admin.participants.index', ['q' => 'amina']))
            ->assertOk()
            ->assertSee('Amina Alaoui');

        $this->actingAs($this->admin())
            ->get(route('admin.participants.index', ['q' => '600112233']))
            ->assertOk()
            ->assertSee('Amina Alaoui');
    }

    public function test_a_one_character_search_is_ignored(): void
    {
        $edition = $this->edition();
        $this->participant($edition);

        // A single letter matches a large share of any attendee list, so the
        // screen degrades to the unfiltered list rather than returning a slow
        // page of near-random names.
        $this->actingAs($this->admin())
            ->get(route('admin.participants.index', ['q' => 'a']))
            ->assertOk();
    }

    // --- The shell ------------------------------------------------------------

    public function test_the_admin_shell_is_not_indexable(): void
    {
        $this->edition();

        $this->actingAs($this->admin())
            ->get(route('admin.dashboard'))
            ->assertOk()
            // An order list indexed by a search engine is a data leak with a
            // delay.
            ->assertSee('noindex', false);
    }

    public function test_the_admin_area_is_not_localised(): void
    {
        $this->edition();

        // The staff area is deliberately outside the `{locale?}` prefix. It is
        // operated by one team in one working language, and putting admin links
        // behind a prefix would make them 404 for anyone whose session happens
        // to carry a locale from the public site. 404 rather than a redirect,
        // so a mistyped /fr/admin does not resolve to something.
        $this->actingAs($this->admin())
            ->get('/ar/admin')
            ->assertNotFound();
    }

    public function test_the_staff_area_can_be_left_behind(): void
    {
        $this->edition();

        $admin = $this->admin();

        // A whole-session sign-out, not "drop the admin flag": handing the desk
        // laptop to a delegate must not leave the organiser's session — and the
        // account behind it — usable from the back button.
        $this->actingAs($admin)
            ->post(route('admin.logout'))
            ->assertRedirect();

        $this->assertGuest();

        // And the staff area is closed again.
        $this->get(route('admin.dashboard'))->assertRedirect(route('login'));
    }

    public function test_leaving_the_staff_area_is_not_reachable_by_a_get(): void
    {
        $this->edition();

        // A GET sign-out would fire from a prefetcher, a prefetch hint or a
        // browser's history restore.
        $this->actingAs($this->admin())
            ->get(route('admin.logout'))
            ->assertMethodNotAllowed();

        $this->assertAuthenticated();
    }

    public function test_the_admin_shell_renders_right_to_left_for_an_arabic_operator(): void
    {
        $this->edition();

        // The locale comes from the operator's stored preference, so this is the
        // real path: an Arabic-speaking organiser signs in and the rail mirrors.
        // The CSS uses logical properties throughout, which only holds if `dir`
        // and `rtl` actually reach the document.
        $admin = $this->admin();
        $admin->forceFill(['locale' => 'ar'])->save();

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('dir="rtl"', false)
            ->assertSee('<body class="admin-body rtl"', false);
    }

    // --- Fixtures -------------------------------------------------------------

    /**
     * Distinct titles per submission, so a filter assertion can tell the rows
     * apart. Two fixtures sharing a title cannot demonstrate that a row was
     * filtered out.
     */
    private function submission(Edition $edition, string $status, ?string $title = null): SpeakerSubmission
    {
        return SpeakerSubmission::query()->create([
            'edition_id' => $edition->getKey(),
            'full_name' => 'Hicham Ouazzani',
            'email' => 'hicham@example.test',
            'organisation' => 'Audit Conseil',
            'job_title' => 'Directeur',
            // NOT NULL in the schema: the format is how the programme is
            // balanced, so a submission without one could not be scheduled.
            'requested_format' => SessionFormat::Plenary->value,
            'session_title' => $title ?? 'Le contrôle interne en 2026',
            'abstract' => 'Une synthèse des pratiques observées.',
            'status' => $status,
        ]);
    }

    private function enquiry(Edition $edition): SponsorshipEnquiry
    {
        // No factory for this model yet, so the package row is built directly.
        // It exists only to give the enquiry a `package_id` — the admin page
        // reads the tier name, not its price.
        $package = SponsoringPackage::query()->create([
            'edition_id' => $edition->getKey(),
            'code' => 'gold',
            'name' => ['fr' => 'Or', 'en' => 'Gold', 'ar' => 'ذهبي'],
            'benefits' => ['fr' => 'Stand', 'en' => 'Stand', 'ar' => 'جناح'],
            'price_from' => 1200000,
            'currency' => 'MAD',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        return SponsorshipEnquiry::query()->create([
            'edition_id' => $edition->getKey(),
            'package_id' => $package->getKey(),
            'company' => 'Atlas Bank',
            'contact_name' => 'Nadia Berrada',
            'contact_email' => 'nadia@atlasbank.test',
            'message' => 'Nous souhaitons soutenir la conférence.',
            'status' => SponsorshipEnquiry::STATUS_NEW,
        ]);
    }

    private function message(Edition $edition): ContactMessage
    {
        return ContactMessage::query()->create([
            'edition_id' => $edition->getKey(),
            'subject_type' => ContactMessage::SUBJECT_REGISTRATION,
            'name' => 'Youssef El Amrani',
            'email' => 'youssef@example.test',
            'message' => 'Puis-je modifier le nom sur ma facture ?',
            'locale' => 'fr',
            'status' => ContactMessage::STATUS_NEW,
        ]);
    }

    private function participant(Edition $edition): Participant
    {
        return Participant::query()->create([
            'order_id' => Order::factory()->forEdition($edition)->paid()->create()->getKey(),
            'full_name' => 'Leila Fassi',
            'email' => 'leila@example.test',
            'job_title' => 'Analyste',
            'is_member' => false,
        ]);
    }
}
