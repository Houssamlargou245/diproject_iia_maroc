<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Registration, pricing, orders, participants and payment records.
 *
 * Fixes carried over from the 2024 build:
 *
 *  1. Money is `unsignedBigInteger` minor units (centimes), never a float or
 *     a `text` column. The legacy `iia_offre.prix_adherent` was TEXT, which
 *     made it impossible to do arithmetic or sort correctly.
 *  2. Order totals are snapshot columns set once at checkout, so a later price
 *     change can never retroactively alter an issued invoice.
 *  3. Order status is a real enum with a legal state machine, replacing the
 *     free-text `etat_payment` in ('encours','valider','annuler').
 *  4. The cart is session-scoped and expires, rather than the legacy
 *     `iia_panier` table where a single stray 'encours' row permanently
 *     blocked a user from buying again (addToCart.php rejected the insert).
 */
return new class extends Migration
{
    public function up(): void
    {
        // Adherents (IIA Maroc members) pay a reduced rate. Modelled as its own
        // table because the legacy `iia_adhesion` is a paid application record
        // with a lifecycle, not a boolean.
        Schema::create('memberships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('reference', 64)->nullable()->index();
            $table->json('civility')->nullable();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('job_title')->nullable();
            $table->string('organisation')->nullable();
            $table->date('birth_date')->nullable();
            $table->string('address')->nullable();
            $table->string('professional_email')->nullable();
            $table->string('landline')->nullable();
            $table->string('mobile')->nullable();
            // pending | active | rejected | cancelled
            $table->string('status', 16)->default('pending')->index();
            $table->unsignedBigInteger('amount_paid')->nullable();
            $table->string('currency', 3)->nullable();
            $table->foreignId('order_id')->nullable();
            $table->timestamp('activated_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });

        Schema::create('ticket_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('edition_id')->constrained('editions')->cascadeOnDelete();
            $table->string('code', 32);
            $table->json('name');
            $table->json('description')->nullable();
            $table->json('includes')->nullable();

            // Minor units. MAD and USD are both 2-decimal, so cents throughout.
            $table->unsignedBigInteger('price_member');
            $table->unsignedBigInteger('price_standard');
            $table->char('currency', 3);

            // ISO-4217 numeric code for the CMI gateway (504 = MAD, 840 = USD).
            $table->char('currency_numeric', 3);

            $table->string('colour', 16)->nullable();
            $table->unsignedSmallInteger('max_quantity')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamp('sales_start_at')->nullable();
            $table->timestamp('sales_end_at')->nullable();
            $table->timestamps();

            $table->unique(['edition_id', 'code']);
        });

        // Session cart. Never holds a price: prices resolve from ticket_types
        // at checkout so a stale cart cannot lock in an old amount.
        Schema::create('carts', function (Blueprint $table) {
            $table->id();
            $table->string('session_id', 64)->unique();
            $table->foreignId('user_id')->nullable()->constrained('users')->cascadeOnDelete();
            $table->timestamp('expires_at');
            $table->timestamps();
            $table->index('expires_at');
        });

        Schema::create('cart_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cart_id')->constrained('carts')->cascadeOnDelete();
            $table->foreignId('ticket_type_id')->constrained('ticket_types')->cascadeOnDelete();
            // The registrant declares how many of them are IIA Maroc members.
            $table->unsignedSmallInteger('quantity');
            $table->unsignedSmallInteger('member_quantity')->default(0);
            $table->timestamps();

            $table->index(['cart_id', 'ticket_type_id']);
        });

        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('edition_id')->constrained('editions')->restrictOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('ticket_type_id')->constrained('ticket_types')->restrictOnDelete();

            $table->string('reference', 32)->unique();
            $table->string('status', 24)->default('pending')->index();
            $table->string('payment_driver', 24)->default('cmi');

            // Snapshot of the buyer at checkout time.
            $table->json('billing');

            // Snapshot totals, in minor units. Never recomputed after issue.
            $table->unsignedBigInteger('subtotal');
            $table->unsignedBigInteger('discount_total')->default(0);
            $table->unsignedBigInteger('tax_total')->default(0);
            $table->unsignedBigInteger('total');
            $table->char('currency', 3);
            $table->char('currency_numeric', 3);

            $table->unsignedSmallInteger('member_count')->default(0);
            $table->unsignedSmallInteger('standard_count')->default(0);

            $table->timestamp('paid_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('refunded_at')->nullable();
            $table->string('invoice_number', 32)->nullable()->unique();
            $table->string('invoice_path')->nullable();
            $table->text('notes')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'status']);
            $table->index(['edition_id', 'status']);
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignId('ticket_type_id')->constrained('ticket_types')->restrictOnDelete();
            $table->json('label');                    // snapshot of the name
            $table->unsignedBigInteger('unit_price_member');
            $table->unsignedBigInteger('unit_price_standard');
            $table->unsignedSmallInteger('member_quantity');
            $table->unsignedSmallInteger('standard_quantity');
            $table->unsignedBigInteger('line_total');
            $table->timestamps();
        });

        Schema::create('participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->string('full_name');
            $table->string('job_title')->nullable();
            $table->string('email')->nullable();
            $table->string('phone', 32)->nullable();
            $table->boolean('is_member')->default(false);
            $table->string('badge_name')->nullable();
            $table->json('dietary_requirements')->nullable();
            $table->boolean('checked_in')->default(false);
            $table->timestamp('checked_in_at')->nullable();
            $table->timestamps();

            $table->index('order_id');
            $table->index('email');
        });

        /**
         * Payment attempts. One row per gateway round-trip.
         *
         * The legacy `iia_payment` had 45 loose TEXT columns and no foreign key,
         * so a callback could never be matched back to an order. Here:
         *  - `order_id` is mandatory, so reconciliation is a join, not a guess.
         *  - `idempotency_key` makes a replayed callback a no-op instead of a
         *    double capture.
         *  - the raw gateway payload is kept for dispute evidence.
         */
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->string('driver', 24);
            $table->string('gateway_transaction_id', 128)->nullable()->index();
            $table->string('gateway_reference', 128)->nullable()->index();
            $table->string('idempotency_key', 128)->unique();
            $table->string('status', 24)->default('pending')->index();
            $table->unsignedBigInteger('amount');
            $table->char('currency', 3);
            $table->char('currency_numeric', 3);
            $table->string('masked_pan', 32)->nullable();
            $table->string('card_brand', 32)->nullable();
            $table->string('card_issuer', 128)->nullable();
            $table->string('auth_code', 32)->nullable();
            $table->string('return_code', 8)->nullable();
            $table->text('error_message')->nullable();
            $table->json('raw_payload')->nullable();
            $table->timestamp('authorised_at')->nullable();
            $table->timestamp('captured_at')->nullable();
            $table->timestamp('settled_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamps();

            $table->index(['order_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
        Schema::dropIfExists('participants');
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('cart_items');
        Schema::dropIfExists('carts');
        Schema::dropIfExists('ticket_types');
        Schema::dropIfExists('memberships');
    }
};
