<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Zamień istniejące wpisy 'nowosci' na 'rynek-ooh'
        DB::table('blog_posts')->where('category', 'nowosci')->update(['category' => 'rynek-ooh']);

        // MODIFY COLUMN działa tylko na MySQL — SQLite (testy) nie ma enuma, pomijamy
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE blog_posts MODIFY COLUMN category ENUM('poradniki', 'trendy', 'case-study', 'rynek-ooh', 'prawo-i-regulacje', 'lokalizacje') NOT NULL DEFAULT 'rynek-ooh'");
        }
    }

    public function down(): void
    {
        DB::table('blog_posts')->where('category', 'rynek-ooh')->update(['category' => 'nowosci']);

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE blog_posts MODIFY COLUMN category ENUM('poradniki', 'trendy', 'case-study', 'nowosci') NOT NULL DEFAULT 'nowosci'");
        }
    }
};
