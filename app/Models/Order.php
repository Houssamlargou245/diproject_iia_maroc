<?php

namespace App\Models;

use App\Enums\OrderStatus;
use App\Support\Money;
use Database\Factories\OrderFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * A registration order.
 *
 * `uuid` and `reference` are both externally visible: the reference is printed
 * on the invoice and quoted in email, the uuid is what the payment callback
 * resolves. Totals are snapshot columns written once at checkout.
 */
class Order extends Model
{
    /** @use HasFactory<OrderFactory> */
    use HasFactory;

    use SoftDeletes;

    protected $fillable = [
        'edition_id', 'user_id', 'ticket_type_id', 'reference', 'status',
        'payment_driver', 'billing', 'subtotal', 'discount_total', 'tax_total',
        'total', 'currency', 'currency_numeric', 'member_count', 'standard_count',
        'paid_at', 'cancelled_at', 'refunded_at',
        'invoice_number', 'invoice_path', 'notes', 'ip_address',
    ];

    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
            'billing' => 'array',
            'subtotal' => 'integer',
            'discount_total' => 'integer',
            'tax_total' => 'integer',
            'total' => 'integer',
            'member_count' => 'integer',
            'standard_count' => 'integer',
            'paid_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'refunded_at' => 'datetime',
        ];
    }

    /**
     * `id` stays an auto-incrementing BIGINT: it is the primary key, it is what
     * every foreign key references, and the 2024 import maps legacy integer
     * order ids onto it directly. The externally visible `uuid` is a separate
     * unique column, used by the payment callback so no row count leaks.
     */
    protected static function booted(): void
    {
        static::creating(function (self $order): void {
            $order->uuid ??= (string) Str::uuid();

            // Human-facing reference, quoted in emails and printed on the
            // invoice. Derived from the uuid so it is available before insert
            // (the column is NOT NULL) and still globally unique.
            $order->reference ??= sprintf(
                '%s-%s',
                config('cmi.invoice_prefix', 'ARABCIA-2026'),
                strtoupper(substr(str_replace('-', '', $order->uuid), 0, 10))
            );
        });
    }

    // --- Relations -------------------------------------------------------

    /** @return BelongsTo<Edition, $this> */
    public function edition(): BelongsTo
    {
        return $this->belongsTo(Edition::class);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<TicketType, $this> */
    public function ticketType(): BelongsTo
    {
        return $this->belongsTo(TicketType::class);
    }

    /** @return HasMany<OrderItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /** @return HasMany<Participant, $this> */
    public function participants(): HasMany
    {
        return $this->hasMany(Participant::class);
    }

    /** @return HasMany<Payment, $this> */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    // --- Scopes ----------------------------------------------------------

    /** @param  Builder<Order>  $query */
    public function scopeForUser(Builder $query, User|int $user): void
    {
        $query->where('user_id', $user instanceof User ? $user->id : $user);
    }

    /** @param  Builder<Order>  $query */
    public function scopePaid(Builder $query): void
    {
        $query->where('status', OrderStatus::Paid);
    }

    /** @param  Builder<Order>  $query */
    public function scopeOutstanding(Builder $query): void
    {
        $query->whereIn('status', [OrderStatus::Pending, OrderStatus::AwaitingPayment, OrderStatus::Failed]);
    }

    // --- State machine ---------------------------------------------------

    /**
     * Apply a status change, refusing illegal transitions.
     *
     * @throws \LogicException when the transition is not allowed
     */
    public function transitionTo(OrderStatus $target): bool
    {
        if ($this->status === $target) {
            return false;
        }

        if (! $this->status->canTransitionTo($target)) {
            throw new \LogicException(
                "Illegal order transition {$this->status->value} -> {$target->value} for order {$this->reference}"
            );
        }

        $changes = ['status' => $target];

        match ($target) {
            OrderStatus::Paid => $changes['paid_at'] = now(),
            OrderStatus::Cancelled => $changes['cancelled_at'] = now(),
            OrderStatus::Refunded, OrderStatus::PartiallyRefunded => $changes['refunded_at'] = now(),
            default => null,
        };

        $this->forceFill($changes)->save();

        return true;
    }

    // --- Formatting ------------------------------------------------------

    public function formattedTotal(): string
    {
        return Money::format($this->total, $this->currency);
    }

    public function participantCount(): int
    {
        return $this->participants()->count();
    }

    /**
     * The payment attempt that should be shown to the user.
     *
     * Named `latestPaymentRecord` rather than `latestPayment` on purpose. Eloquent
     * resolves `$order->latestPayment` — property syntax — as a *relationship*
     * whenever a method of that name exists, and then insists the method return a
     * Relation. This one returns a single model or null, so every property-style
     * access blew up with "latestPayment must return a relationship instance".
     *
     * A plain method is right here: this is a query against the payments the order
     * already owns, not an association the order might not have. Call it with
     * parentheses.
     */
    public function latestPaymentRecord(): ?Payment
    {
        return $this->payments()->latest('id')->first();
    }

    /**
     * Allocate an invoice number.
     *
     * Sequential per year, e.g. ARABCIA-2026/2026/00042, because accountants
     * expect a gapless-ish series. Derived from the current maximum inside a
     * transaction, with a re-check loop: a count-based sequence collides
     * immediately after a rollback or a deleted order, and an invoice number
     * that appears twice is an accounting problem, not a cosmetic one.
     */
    public function assignInvoiceNumber(): string
    {
        if (filled($this->invoice_number)) {
            return $this->invoice_number;
        }

        return DB::transaction(function (): string {
            $prefix = (string) config('cmi.invoice_prefix', 'ARABCIA-2026');
            $year = (int) ($this->edition?->year ?? now()->year);
            $like = "{$prefix}/{$year}/%";

            $highest = DB::table('orders')
                ->where('invoice_number', 'like', $like)
                ->max('invoice_number');

            $next = $highest === null
                ? 1
                : ((int) substr((string) $highest, -5)) + 1;

            do {
                $number = sprintf('%s/%d/%05d', $prefix, $year, $next);
                $taken = DB::table('orders')
                    ->where('invoice_number', $number)
                    ->where('id', '!=', $this->getKey())
                    ->exists();
                $next++;
            } while ($taken);

            $this->forceFill(['invoice_number' => $number])->save();

            return $number;
        });
    }

    public function isPayable(): bool
    {
        return $this->status->isPayable();
    }

    public function hasSuccessfulPayment(): bool
    {
        // Read from the loaded collection when the caller eager-loaded it, so a
        // list of orders does not issue one query per row. `effectiveStatus()`
        // is called while rendering exactly those lists.
        if ($this->relationLoaded('payments')) {
            return $this->payments->contains(
                fn (Payment $payment): bool => $payment->isSuccessful()
            );
        }

        return $this->payments()->successful()->exists();
    }

    /**
     * The status to show the buyer, corrected for a payment that succeeded
     * without the order ever being settled.
     *
     * A CMI/Saham authorisation can be accepted while the server-to-server
     * callback that settles the order never lands — the gateway shows the
     * customer a "vérifiez l'état de votre commande avec votre marchand"
     * receipt, and the order keeps reading `awaiting_payment` even though the
     * money moved. Showing the stored string there is telling the buyer the
     * opposite of the truth, so the display is derived from the payment
     * evidence: an order that carries a successful payment reads as paid.
     *
     * Only the not-yet-settled states are upgraded. A refunded or cancelled
     * order keeps its stored status — those are deliberate terminal states, not
     * a callback that failed to arrive.
     */
    public function effectiveStatus(): OrderStatus
    {
        $promotable = in_array($this->status, [
            OrderStatus::Pending,
            OrderStatus::AwaitingPayment,
            OrderStatus::Failed,
        ], true);

        if ($promotable && $this->hasSuccessfulPayment()) {
            return OrderStatus::Paid;
        }

        return $this->status;
    }
}
