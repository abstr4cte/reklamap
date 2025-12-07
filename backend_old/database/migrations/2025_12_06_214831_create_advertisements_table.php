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
        Schema::create('advertisements', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('title');
            $table->string('type');
            $table->string('location');
            $table->string('city');
            $table->decimal('latitude', 10, 8);
            $table->decimal('longitude', 11, 8);
            $table->text('description');
            $table->decimal('price', 10, 2);
            $table->decimal('width', 8, 2);
            $table->decimal('height', 8, 2);
            $table->string('image_url')->nullable();
            $table->string('owner_email');
            $table->string('status')->default('active');
            $table->string('region');
            $table->string('orientation');
            $table->string('traffic_intensity');
            $table->string('price_unit');
            $table->boolean('has_lighting')->default(false);
            $table->boolean('has_image')->default(false);
            $table->boolean('price_includes_print')->default(false);
            $table->boolean('graphic_design_help')->default(false);
            $table->string('offer_type');
            $table->boolean('has_vat_invoice')->default(false);
            $table->integer('views')->default(0);
            $table->boolean('is_active')->default(true);
            $table->string('phone')->nullable();
            $table->string('contact_preference')->default('email');
            $table->json('images')->nullable();
            $table->date('available_from')->nullable();
            $table->boolean('price_negotiable')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('advertisements');
    }
};
