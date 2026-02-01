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
            $table->string('resolution')->nullable()->comment('Rozdzielczość ekranu (np. 1920x1080)');
            $table->decimal('pixel_pitch', 5, 2)->nullable()->comment('Pixel pitch w milimetrach');
            $table->integer('brightness')->nullable()->comment('Jasność ekranu w nitach');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('advertisements', function (Blueprint $table) {
            $table->dropColumn(['resolution', 'pixel_pitch', 'brightness']);
        });
    }
};
