<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Dane demo/testowe (owner_email=test@test.pl) seedujemy TYLKO lokalnie.
        // Na produkcji goły `db:seed` nie odtwarza danych testowych.
        if (app()->environment('production')) {
            return;
        }

        $this->call([
            AdvertisementsSeeder::class,
            AdvertisementStatsSeeder::class,
        ]);
    }
}
