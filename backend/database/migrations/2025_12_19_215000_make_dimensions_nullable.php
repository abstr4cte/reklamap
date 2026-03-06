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
            // Make width and height nullable (for citylight and other types that don't require dimensions)
            $table->decimal('width', 8, 2)->nullable()->change();
            $table->decimal('height', 8, 2)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('advertisements', function (Blueprint $table) {
            // Keep width and height nullable for rollback compatibility
            $table->decimal('width', 8, 2)->nullable()->change();
            $table->decimal('height', 8, 2)->nullable()->change();
        });
    }
};
