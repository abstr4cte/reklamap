<?php

namespace App\Console\Commands;

use App\Models\Advertisement;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Usuwa dane testowe/demo (owner_email=test@test.pl) z bazy + ich osierocone statystyki.
 * Bezpieczne na produkcji: demo-seeder jest tam zablokowany, więc nie wrócą.
 *
 * Uruchom: php artisan demo:purge            (usuwa)
 *          php artisan demo:purge --dry-run  (tylko pokazuje, ile)
 */
class PurgeDemoData extends Command
{
    protected $signature = 'demo:purge {--dry-run : Tylko pokaż liczbę, nic nie usuwaj.}';
    protected $description = 'Usuwa ogłoszenia testowe (test@test.pl) i ich statystyki.';

    private const EMAIL = 'test@test.pl';

    public function handle(): int
    {
        $ids = Advertisement::where('owner_email', self::EMAIL)->pluck('id');
        $this->info("Ogłoszeń testowych ({$this->emailLabel()}): {$ids->count()}");

        if ($ids->isEmpty()) {
            $this->info('Nic do usunięcia.');
            return self::SUCCESS;
        }

        if ($this->option('dry-run')) {
            $this->line('PRÓBA — nic nie usunięto.');
            return self::SUCCESS;
        }

        DB::transaction(function () use ($ids) {
            $stats = DB::table('advertisement_daily_stats')->whereIn('advertisement_id', $ids)->delete();
            $ads = Advertisement::where('owner_email', self::EMAIL)->delete();
            $this->info("Usunięto: {$ads} ogłoszeń, {$stats} wpisów statystyk.");
        });

        return self::SUCCESS;
    }

    private function emailLabel(): string
    {
        return self::EMAIL;
    }
}
