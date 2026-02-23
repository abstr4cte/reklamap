<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('advertisements', function (Blueprint $table) {
            // Dodaj nową kolumnę lighting_type dla banerów i ścian
            // none, backlight, frontlight
            $table->enum('lighting_type_banner', ['none', 'backlight', 'frontlight'])->nullable()->after('has_backlight');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('advertisements', function (Blueprint $table) {
            $table->dropColumn('lighting_type_banner');
        });
    }
};
