<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\Registration\PromoCodeGenerator;
use Illuminate\Console\Command;

class BackfillPromoCodes extends Command
{
    protected $signature = 'promo:backfill';
    protected $description = 'Generate promo codes for users missing one';

    public function handle(PromoCodeGenerator $generator): int
    {
        $count = 0;

        User::whereNull('promo_code')
            ->orderBy('id')
            ->chunkById(100, function ($users) use ($generator, &$count) {
                foreach ($users as $user) {
                    $user->promo_code = $generator->generate((string) ($user->first_name ?? ''));
                    $user->save();
                    $count++;
                }
            });

        $this->info("Backfilled {$count} users with a promo code.");
        return self::SUCCESS;
    }
}