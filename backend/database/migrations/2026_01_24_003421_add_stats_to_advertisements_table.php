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
        Schema::table('advertisements', function (Blueprint $blueprint) {
            $blueprint->integer('phone_clicks')->default(0)->after('views');
            $blueprint->integer('email_clicks')->default(0)->after('phone_clicks');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('advertisements', function (Blueprint $blueprint) {
            $blueprint->dropColumn(['phone_clicks', 'email_clicks']);
        });
    }
};
