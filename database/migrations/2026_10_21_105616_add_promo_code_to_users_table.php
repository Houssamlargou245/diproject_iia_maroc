<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Max 9 chars (XX-XXXXXX), nullable for existing users, unique for safety
            $table->string('promo_code', 9)->nullable()->unique()->after('last_name');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['promo_code']);
            $table->dropColumn('promo_code');
        });
    }
};