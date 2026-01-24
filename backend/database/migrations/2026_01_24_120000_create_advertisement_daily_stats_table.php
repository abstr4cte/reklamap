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
        Schema::create('advertisement_daily_stats', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('advertisement_id');
            $table->date('date');
            $table->unsignedInteger('views')->default(0);
            $table->unsignedInteger('phone_clicks')->default(0);
            $table->unsignedInteger('email_clicks')->default(0);
            $table->timestamps();

            // Foreign key
            $table->foreign('advertisement_id')
                ->references('id')
                ->on('advertisements')
                ->onDelete('cascade');

            // Unique constraint - jeden wpis per ogłoszenie per dzień
            $table->unique(['advertisement_id', 'date']);

            // Indeksy dla szybszych zapytań
            $table->index('date');
            $table->index('advertisement_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('advertisement_daily_stats');
    }
};
