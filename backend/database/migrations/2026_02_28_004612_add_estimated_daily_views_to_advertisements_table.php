<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('advertisements', function (Blueprint $table) {
            if (!Schema::hasColumn('advertisements', 'estimated_daily_views')) {
                $table->integer('estimated_daily_views')->nullable()->after('traffic_intensity')->comment('Szacowana dzienna liczba wyświetleń / kontaktów (OTS)');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('advertisements', function (Blueprint $table) {
            if (Schema::hasColumn('advertisements', 'estimated_daily_views')) {
                $table->dropColumn('estimated_daily_views');
            }
        });
    }
};
