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
        Schema::table('newsletter_subscribers', function (Blueprint $table) {
            $table->string('unsubscribe_token')->nullable()->unique()->after('email');
        });

        // Generate tokens for existing subscribers
        $subscribers = \App\Models\Newsletter::all();
        foreach ($subscribers as $subscriber) {
            $subscriber->update([
                'unsubscribe_token' => \Illuminate\Support\Str::random(40)
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('newsletter_subscribers', function (Blueprint $table) {
            $table->dropColumn('unsubscribe_token');
        });
    }
};
