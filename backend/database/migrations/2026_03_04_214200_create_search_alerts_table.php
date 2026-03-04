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
        Schema::create('search_alerts', function (Blueprint $blueprint) {
            $blueprint->id();
            $blueprint->string('email');
            $blueprint->string('type')->nullable();
            $blueprint->string('city')->nullable();
            $blueprint->string('region')->nullable();
            $blueprint->json('filters')->nullable();
            $blueprint->string('unsubscribe_token')->unique();
            $blueprint->timestamp('last_notified_at')->nullable();
            $blueprint->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('search_alerts');
    }
};
