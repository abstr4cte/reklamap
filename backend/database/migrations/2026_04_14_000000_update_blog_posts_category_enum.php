<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            // Krok 1: rozszerz ENUM o nową wartość (obie wartości muszą być dozwolone przed zmianą danych)
            DB::statement("ALTER TABLE blog_posts MODIFY COLUMN category ENUM('poradniki', 'trendy', 'case-study', 'nowosci', 'rynek-ooh', 'prawo-i-regulacje', 'lokalizacje') NOT NULL DEFAULT 'rynek-ooh'");
        }

        // Krok 2: zaktualizuj dane — teraz obie wartości są w ENUM
        DB::table('blog_posts')->where('category', 'nowosci')->update(['category' => 'rynek-ooh']);

        if (DB::getDriverName() === 'mysql') {
            // Krok 3: usuń starą wartość z ENUM
            DB::statement("ALTER TABLE blog_posts MODIFY COLUMN category ENUM('poradniki', 'trendy', 'case-study', 'rynek-ooh', 'prawo-i-regulacje', 'lokalizacje') NOT NULL DEFAULT 'rynek-ooh'");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            // Krok 1: przywróć starą wartość do ENUM przed zmianą danych
            DB::statement("ALTER TABLE blog_posts MODIFY COLUMN category ENUM('poradniki', 'trendy', 'case-study', 'nowosci', 'rynek-ooh', 'prawo-i-regulacje', 'lokalizacje') NOT NULL DEFAULT 'nowosci'");
        }

        // Krok 2: zaktualizuj dane — sprowadź wszystkie wartości spoza docelowego ENUM do 'nowosci'
        DB::table('blog_posts')
            ->whereNotIn('category', ['poradniki', 'trendy', 'case-study', 'nowosci'])
            ->update(['category' => 'nowosci']);

        if (DB::getDriverName() === 'mysql') {
            // Krok 3: usuń nową wartość z ENUM
            DB::statement("ALTER TABLE blog_posts MODIFY COLUMN category ENUM('poradniki', 'trendy', 'case-study', 'nowosci') NOT NULL DEFAULT 'nowosci'");
        }
    }
};
