<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\Locale;
use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use App\Models\Edition;
use App\Models\Order;
use App\Models\Participant;
use App\Models\SpeakerSubmission;
use App\Models\SponsorshipEnquiry;
use App\Support\Money;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * The staff overview.
 *
 * Everything here is a count or a sum over the *current* edition, computed in
 * the database rather than by loading rows and reducing them in PHP. That is not
 * premature optimisation: the orders table grows by one row per transaction for
 * the whole run-up to the conference, and the dashboard is the page an organiser
 * refreshes constantly to watch the number move.
 *
 * The three "needs a decision" counts — unhandled speaker submissions, open
 * sponsorship enquiries and unanswered contact messages — are the reason the
 * page exists rather than a revenue chart. Each of those three models already
 * carries `handled_by`/`handled_at` columns that no public code path writes,
 * which is what made the queue shape obvious; the enums on them make the
 * "open" test a scope instead of a guess about which status strings mean
 * "unhandled".
 *
 * Beyond the counters, the page carries the shape of the run: registrations
 * over time, the member/non-member split, where buyers come from and how full
 * each tariff is. Each of those is one grouped or aggregated query over the
 * current edition — the same rule as the counters, applied to the questions an
 * organiser asks when the headline number alone is not enough to act on.
 */
class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $edition = Edition::current();

        // An admin with no edition is a legitimate state during setup, so the
        // dashboard renders empty rather than throwing on a null relation.
        $locale = Locale::parse(app()->getLocale());

        if ($edition === null) {
            return view('admin.dashboard', [
                'edition' => null,
                'locale' => $locale,
                'currentRoute' => 'admin.dashboard',
                'stats' => [],
                'revenue' => null,
                'currency' => config('conference.default_currency', 'MAD'),
                'salesByType' => collect(),
                'ordersByStatus' => collect(),
                'recentOrders' => collect(),
                'queues' => [],
                // The shapes are empty rather than absent: the view renders the
                // same markup whatever the edition state, and an undefined
                // variable on a setup install must not be the reason the staff
                // page 500s before the first order exists.
                'paidRate' => 0,
                'averageOrder' => 0,
                'trend' => ['days' => [], 'totalOrders' => 0, 'totalPaid' => 0],
                'memberSplit' => $this->memberSplit(collect()),
                'capacity' => collect(),
                'countries' => collect(),
            ]);
        }

        $paid = OrderStatus::Paid;

        // The counters, each summed in SQL for the reason given in the class
        // docblock: the orders table grows by one row per transaction for the
        // whole run-up, and the dashboard is the page an organiser refreshes.
        $stats = [
            'orders' => $edition->orders()->count(),
            'paidOrders' => $edition->orders()->where('status', $paid)->count(),
            'participants' => Participant::query()
                ->whereIn('order_id', $edition->orders()->select('id'))
                ->count(),
            'checkedIn' => Participant::query()
                ->whereIn('order_id', $edition->orders()->select('id'))
                ->whereNotNull('checked_in_at')
                ->count(),
        ];

        // Only `Paid` counts as revenue: summing `total` across every status
        // would report cancelled and abandoned orders as income, which is the
        // mistake that makes a reconciliation impossible to trust.
        $revenue = (int) $edition->orders()
            ->where('status', $paid)
            ->sum('total');

        $currency = (string) ($edition->orders()
            ->where('status', $paid)
            ->value('currency') ?? config('conference.default_currency', 'MAD'));

        // Places sold per tariff, counting member places at the member price
        // and the remainder at the standard one. Taken from the order aggregate
        // columns rather than from the participant rows, because an order can
        // be paid before its participants are filled in. Two further panels —
        // the member split and the capacity gauges — are derived from this same
        // collection so they cannot disagree with the table it already powers.
        $salesByType = $this->salesByType($edition);

        return view('admin.dashboard', [
            'edition' => $edition,
            'locale' => $locale,

            // `$currentRoute` is what the admin layout uses to highlight the
            // active nav item. Every admin page sets it.
            'currentRoute' => 'admin.dashboard',

            'stats' => $stats,
            'revenue' => $revenue,
            'currency' => $currency,
            'salesByType' => $salesByType,

            // Relative measures: a raw count cannot answer "is the funnel
            // healthy?" once the run-up is long. The paid rate is orders paid
            // over orders placed; the average order is takings over paid
            // orders, and is zero rather than a division by zero when nothing
            // has been paid.
            'paidRate' => $stats['orders'] > 0
                ? (int) round($stats['paidOrders'] / $stats['orders'] * 100)
                : 0,
            'averageOrder' => $stats['paidOrders'] > 0
                ? intdiv($revenue, $stats['paidOrders'])
                : 0,

            'trend' => $this->registrationsTrend($edition),
            'memberSplit' => $this->memberSplit($salesByType),
            'capacity' => $this->capacity($salesByType),
            'countries' => $this->byCountry($edition, $locale),

            // Every status in the enum, including the ones with a count of
            // zero, so the breakdown answers "is anything stuck?" rather than
            // only "what happened?".
            'ordersByStatus' => $this->ordersByStatus($edition),

            'recentOrders' => $edition->orders()
                ->with('user')
                ->latest()
                ->limit(8)
                ->get(),

            'queues' => $this->queues($edition),
        ]);
    }

    /** @return Collection<int, object> */
    private function salesByType(Edition $edition): Collection
    {
        return $edition->ticketTypes()
            ->withSum('orders', 'member_count')
            ->withSum('orders', 'standard_count')
            ->orderBy('sort_order')
            ->get()
            ->map(function ($type) {
                $member = (int) ($type->orders_sum_member_count ?? 0);
                $standard = (int) ($type->orders_sum_standard_count ?? 0);

                return (object) [
                    'type' => $type,
                    'member' => $member,
                    'standard' => $standard,
                    'total' => $member + $standard,
                    // Revenue is derived from the tariff's own prices rather
                    // than from the order totals, so the figure on the pricing
                    // page and the figure here can be compared directly. A
                    // tariff that changed price mid-run would break that
                    // equivalence, which is a second reason not to treat this as
                    // an accounting total — it is a planning aid.
                    'revenue' => $member * $type->price_member
                        + $standard * $type->price_standard,
                ];
            });
    }

    /**
     * Orders placed, and orders paid, per day over the registration run-up.
     *
     * The series is dense — every day from the first order to today, zeros
     * included — because a line drawn only from the days that had an order
     * silently compresses the quiet stretches and makes a flat week look busy.
     * Both figures are cumulative: the operator's question is "where has the
     * number got to", not "what did Tuesday add".
     *
     * The window is capped at 180 days so a stale first order from a previous
     * campaign cannot force a thousand-point polyline into the page.
     *
     * @return array{days: list<array{date: string, label: string, orders: int, paid: int, ordersCum: int, paidCum: int}>, totalOrders: int, totalPaid: int}
     */
    private function registrationsTrend(Edition $edition): array
    {
        $first = $edition->orders()->min('created_at');

        if ($first === null) {
            return ['days' => [], 'totalOrders' => 0, 'totalPaid' => 0];
        }

        $byDay = $edition->orders()
            ->selectRaw('DATE(created_at) as day')
            ->selectRaw('COUNT(*) as total')
            // A conditional sum rather than a second query: the paid line has
            // to share the orders line's exact day buckets, and two grouped
            // queries are two chances for the boundaries to differ.
            ->selectRaw('SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as paid_total', [OrderStatus::Paid->value])
            ->groupByRaw('DATE(created_at)')
            ->orderByRaw('DATE(created_at)')
            ->get()
            ->keyBy('day');

        $start = Carbon::parse((string) $first)->startOfDay();
        $end = now()->startOfDay();

        if ($start->gt($end)) {
            $start = $end->copy();
        }

        if ($start->diffInDays($end) > 179) {
            $start = $end->copy()->subDays(179);
        }

        $days = [];
        $ordersCum = 0;
        $paidCum = 0;

        for ($day = $start->copy(); $day->lte($end); $day->addDay()) {
            $key = $day->format('Y-m-d');
            $row = $byDay->get($key);

            $ordersCum += (int) ($row->total ?? 0);
            $paidCum += (int) ($row->paid_total ?? 0);

            $days[] = [
                'date' => $key,
                'label' => $day->format('d/m'),
                'orders' => (int) ($row->total ?? 0),
                'paid' => (int) ($row->paid_total ?? 0),
                'ordersCum' => $ordersCum,
                'paidCum' => $paidCum,
            ];
        }

        return ['days' => $days, 'totalOrders' => $ordersCum, 'totalPaid' => $paidCum];
    }

    /**
     * The member / non-member split across every tariff.
     *
     * Derived from the places-sold collection rather than from a second query,
     * so the two panels cannot drift apart. Revenue is priced at each tariff's
     * own rates, for the same planning-aid reason given in {@see self::salesByType()}.
     */
    private function memberSplit(Collection $salesByType): object
    {
        $member = (int) $salesByType->sum('member');
        $standard = (int) $salesByType->sum('standard');
        $total = $member + $standard;

        return (object) [
            'member' => $member,
            'standard' => $standard,
            'total' => $total,
            'percentMember' => $total > 0 ? (int) round($member / $total * 100) : 0,
            'memberRevenue' => (int) $salesByType->sum(
                fn ($row): int => (int) $row->member * (int) $row->type->price_member
            ),
            'standardRevenue' => (int) $salesByType->sum(
                fn ($row): int => (int) $row->standard * (int) $row->type->price_standard
            ),
        ];
    }

    /**
     * How full each tariff is.
     *
     * `max_quantity` of zero means the tariff is not capped — the legacy schema
     * used 0 for "no limit" rather than NULL — so those rows report a sold
     * count and no percentage instead of a gauge pinned at a meaningless 0%.
     *
     * @return Collection<int, object>
     */
    private function capacity(Collection $salesByType): Collection
    {
        return $salesByType->map(function ($row): object {
            $max = (int) $row->type->max_quantity;
            $sold = (int) $row->total;
            $unlimited = $max <= 0;

            return (object) [
                'type' => $row->type,
                'sold' => $sold,
                'max' => $max,
                'unlimited' => $unlimited,
                'remaining' => $unlimited ? null : max(0, $max - $sold),
                'percent' => $unlimited ? 0 : (int) min(100, round($sold / $max * 100)),
                'full' => ! $unlimited && $sold >= $max,
            ];
        })->values();
    }

    /**
     * Where the buyers come from, by paid orders.
     *
     * Grouped through the buyer's account (`users.country_id`) rather than the
     * `billing.country_iso2` snapshot: the snapshot is free text on a JSON blob
     * that a driver-dependent `JSON_EXTRACT` would have to parse, while the
     * account's country is a real foreign key to the ISO reference table and
     * the one the delegate can actually correct under "my account".
     *
     * @return Collection<int, object>
     */
    private function byCountry(Edition $edition, Locale $locale): Collection
    {
        return $edition->orders()
            ->join('users', 'users.id', '=', 'orders.user_id')
            ->join('countries', 'countries.id', '=', 'users.country_id')
            ->where('orders.status', OrderStatus::Paid)
            ->groupBy('countries.iso2', 'countries.name_fr', 'countries.name_en', 'countries.name_ar')
            ->selectRaw('countries.iso2 as iso2')
            ->selectRaw('countries.name_fr as name_fr')
            ->selectRaw('countries.name_en as name_en')
            ->selectRaw('countries.name_ar as name_ar')
            ->selectRaw('COUNT(*) as registrations')
            ->selectRaw('SUM(orders.total) as revenue')
            ->orderByDesc('registrations')
            ->orderBy('countries.name_en')
            ->limit(8)
            ->get()
            ->map(fn ($row): object => (object) [
                'iso2' => (string) $row->iso2,
                'name' => match ($locale->value) {
                    'ar' => (string) $row->name_ar,
                    'en' => (string) $row->name_en,
                    default => (string) $row->name_fr,
                },
                'registrations' => (int) $row->registrations,
                'revenue' => (int) $row->revenue,
            ]);
    }

    /** @return Collection<string, int> */
    private function ordersByStatus(Edition $edition): Collection
    {
        // A single grouped query, then keyed by the enum's backing value, so
        // absent statuses are zero rather than missing from the loop.
        $counts = $edition->orders()
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        return collect(OrderStatus::cases())
            ->mapWithKeys(fn (OrderStatus $s): array => [$s->value => (int) ($counts[$s->value] ?? 0)]);
    }

    /**
     * The three queues that need a human decision.
     *
     * @return array<string, array{key: string, count: int, route: string}>
     */
    private function queues(Edition $edition): array
    {
        return [
            'submissions' => [
                'key' => 'admin.queue.submissions',
                'count' => SpeakerSubmission::query()
                    ->where('edition_id', $edition->getKey())
                    ->pending()
                    ->count(),
                'route' => 'admin.submissions.index',
            ],
            'enquiries' => [
                'key' => 'admin.queue.enquiries',
                'count' => SponsorshipEnquiry::query()
                    ->where('edition_id', $edition->getKey())
                    ->open()
                    ->count(),
                'route' => 'admin.enquiries.index',
            ],
            'messages' => [
                'key' => 'admin.queue.messages',
                'count' => ContactMessage::query()
                    ->where('edition_id', $edition->getKey())
                    ->open()
                    ->count(),
                'route' => 'admin.messages.index',
            ],
        ];
    }

    /** Format a minor-unit figure for the dashboard headline. */
    public static function format(int $minorUnits, string $currency): string
    {
        return Money::format($minorUnits, $currency);
    }
}
