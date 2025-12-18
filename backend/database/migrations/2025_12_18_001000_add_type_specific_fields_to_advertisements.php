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
            // Wariant powierzchni (billboard: standard/three_sided/backlit, citylight: single/double/digital, etc.)
            $table->string('variant')->nullable()->after('type');
            
            // Klasa drogi (dla billboardów)
            $table->string('road_class')->nullable()->after('traffic_intensity');
            
            // Kierunek ruchu (array: entry, exit, both)
            $table->json('traffic_direction')->nullable()->after('road_class');
            
            // Środowisko (indoor/outdoor/event)
            $table->string('environment')->nullable()->after('traffic_direction');
            
            // Pola dla ekranów LED
            $table->integer('spot_duration')->nullable()->after('environment'); // sekundy
            $table->integer('loop_duration')->nullable()->after('spot_duration'); // sekundy
            
            // Pola dla transportu
            $table->string('transport_scope')->nullable()->after('loop_duration'); // internal/external/full_vehicle
            $table->integer('vehicle_count')->nullable()->after('transport_scope');
            
            // Pola dla reklamy mobilnej
            $table->string('mobile_exposure_mode')->nullable()->after('vehicle_count'); // moving/stationary/mixed
            $table->string('operating_hours')->nullable()->after('mobile_exposure_mode');
            $table->text('route_area')->nullable()->after('operating_hours');
            
            // Dodatkowe pole dla ceny
            $table->boolean('price_includes_mounting')->default(false)->after('price_includes_print');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('advertisements', function (Blueprint $table) {
            $table->dropColumn([
                'variant',
                'road_class',
                'traffic_direction',
                'environment',
                'spot_duration',
                'loop_duration',
                'transport_scope',
                'vehicle_count',
                'mobile_exposure_mode',
                'operating_hours',
                'route_area',
                'price_includes_mounting'
            ]);
        });
    }
};
