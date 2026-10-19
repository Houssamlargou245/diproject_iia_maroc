<?php

namespace Database\Factories;

use App\Casts\TranslationPayload;
use App\Models\Edition;
use App\Models\TicketType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TicketType>
 */
class TicketTypeFactory extends Factory
{
    protected $model = TicketType::class;

    public function definition(): array
    {
        // Brief, sheet "Modifications 2026" row 15: "7 500/8 500 MAD et
        // 750/850 USD". The member rate is a separate column, not a percentage
        // of the standard rate. Minor units.
        return [
            'edition_id' => Edition::factory(),
            'code' => 'full-'.fake()->unique()->numberBetween(1, 999999),
            'name' => TranslationPayload::encode([
                'fr' => 'Inscription complète',
                'en' => 'Full registration',
                'ar' => 'تسجيل كامل',
            ]),
            'description' => TranslationPayload::encode([
                'fr' => 'Accès aux deux jours.',
                'en' => 'Access to both days.',
                'ar' => 'الوصول إلى اليومين.',
            ]),
            'includes' => TranslationPayload::encode([
                'fr' => ['Accès aux conférences', 'Pause déjeuner', 'Dossier numérique'],
                'en' => ['Access to the sessions', 'Lunch', 'Digital proceedings'],
                'ar' => ['الوصول إلى الجلسات', 'الغداء', 'الملف الرقمي'],
            ]),
            'price_member' => 750000,
            'price_standard' => 850000,
            'currency' => 'MAD',
            'currency_numeric' => '504',
            'colour' => '#0b5d3b',
            'max_quantity' => 300,
            'sort_order' => 0,
            'is_active' => true,
            'sales_start_at' => now()->subWeek(),
            'sales_end_at' => now()->addMonths(3),
        ];
    }

    public function soldOut(): static
    {
        return $this->state(fn (): array => [
            'is_active' => true,
            'sales_end_at' => now()->subDay(),
        ]);
    }

    public function inCurrency(string $currency, int $standard, int $member): static
    {
        return $this->state(fn (): array => [
            'currency' => $currency,
            'currency_numeric' => $currency === 'USD' ? '840' : '504',
            'price_standard' => $standard,
            'price_member' => $member,
        ]);
    }
}
