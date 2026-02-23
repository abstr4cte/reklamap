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
            // Billboard - lighting type
            if (!Schema::hasColumn('advertisements', 'lighting_type')) {
                $table->enum('lighting_type', ['led', 'fluorescent', 'natural', 'none'])->nullable()->comment('Typ oświetlenia billboardu');
            }
            
            // Banner, Wall - extend has_backlight to these types
            // (has_backlight already exists, just need to use it for these types)
            
            // Transport - daily passengers
            if (!Schema::hasColumn('advertisements', 'daily_passengers')) {
                $table->integer('daily_passengers')->nullable()->comment('Liczba pasażerów dziennie (dla transportu publicznego)');
            }
            
            // Mobile - operating zone
            if (!Schema::hasColumn('advertisements', 'operating_zone')) {
                $table->enum('operating_zone', ['center', 'periphery', 'agglomeration'])->nullable()->comment('Strefa operacyjna reklamy mobilnej');
            }
            
            // LED Screen - ambient light control
            if (!Schema::hasColumn('advertisements', 'ambient_light_control')) {
                $table->boolean('ambient_light_control')->default(false)->comment('Czy ekran dostosowuje jasność do otoczenia');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('advertisements', function (Blueprint $table) {
            $columnsToDelete = [];
            
            if (Schema::hasColumn('advertisements', 'lighting_type')) {
                $columnsToDelete[] = 'lighting_type';
            }
            if (Schema::hasColumn('advertisements', 'daily_passengers')) {
                $columnsToDelete[] = 'daily_passengers';
            }
            if (Schema::hasColumn('advertisements', 'operating_zone')) {
                $columnsToDelete[] = 'operating_zone';
            }
            if (Schema::hasColumn('advertisements', 'ambient_light_control')) {
                $columnsToDelete[] = 'ambient_light_control';
            }
            
            if (!empty($columnsToDelete)) {
                $table->dropColumn($columnsToDelete);
            }
        });
    }
};
