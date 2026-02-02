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
            // Pola techniczne dla LED screens
            if (!Schema::hasColumn('advertisements', 'resolution')) {
                $table->string('resolution')->nullable()->comment('Rozdzielczość ekranu (np. 1920x1080)');
            }
            if (!Schema::hasColumn('advertisements', 'pixel_pitch')) {
                $table->decimal('pixel_pitch', 5, 2)->nullable()->comment('Pixel pitch w milimetrach');
            }
            if (!Schema::hasColumn('advertisements', 'brightness')) {
                $table->integer('brightness')->nullable()->comment('Jasność ekranu w nitach');
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
            if (Schema::hasColumn('advertisements', 'resolution')) {
                $columnsToDelete[] = 'resolution';
            }
            if (Schema::hasColumn('advertisements', 'pixel_pitch')) {
                $columnsToDelete[] = 'pixel_pitch';
            }
            if (Schema::hasColumn('advertisements', 'brightness')) {
                $columnsToDelete[] = 'brightness';
            }
            if (!empty($columnsToDelete)) {
                $table->dropColumn($columnsToDelete);
            }
        });
    }
};
