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
            // Rename has_lighting to has_backlight (only if has_lighting exists)
            if (Schema::hasColumn('advertisements', 'has_lighting')) {
                $table->renameColumn('has_lighting', 'has_backlight');
            }
            
            // Make traffic_intensity nullable (only for billboards and banners)
            $table->string('traffic_intensity')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('advertisements', function (Blueprint $table) {
            // Rename back to has_lighting (only if has_backlight exists)
            if (Schema::hasColumn('advertisements', 'has_backlight')) {
                $table->renameColumn('has_backlight', 'has_lighting');
            }
            
            // Revert traffic_intensity to nullable
            $table->string('traffic_intensity')->nullable()->change();
        });
    }
};
