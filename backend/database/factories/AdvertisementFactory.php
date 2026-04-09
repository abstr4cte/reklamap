<?php

namespace Database\Factories;

use App\Models\Advertisement;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Advertisement Factory
 * 
 * Generates fake advertisement data for testing.
 * Used by PHPUnit tests to create test data quickly.
 */
class AdvertisementFactory extends Factory
{
    protected $model = Advertisement::class;

    public function definition(): array
    {
        $types = ['billboard', 'citylight', 'led_screen', 'banner', 'wall', 'totem', 'transport', 'mobile', 'other'];
        $type = fake()->randomElement($types);

        // Dimensions depend on type
        $dimensions = $this->getDimensionsForType($type);

        return [
            'title' => fake()->sentence(4),
            'description' => fake()->paragraph(3),
            'type' => $type,
            'variant' => $this->getVariantForType($type),
            'city' => fake()->randomElement(['Warszawa', 'Kraków', 'Wrocław', 'Poznań', 'Gdańsk']),
            'location' => fake()->streetAddress(),
            'latitude' => fake()->latitude(49, 55),
            'longitude' => fake()->longitude(14, 24),
            'price' => fake()->numberBetween(500, 10000),
            'price_unit' => fake()->randomElement(['day', 'week', 'month', 'year']),
            'width' => $dimensions['width'],
            'height' => $dimensions['height'],
            'traffic_intensity' => fake()->randomElement(['low', 'medium', 'high']),
            'status' => fake()->randomElement(['active', 'rented', 'soon_available']),
            'orientation' => fake()->randomElement(['horizontal', 'vertical']),
            'owner_email' => fake()->safeEmail(),
            'phone' => fake()->phoneNumber(),
            'contact_preference' => 'email',
            'offer_type' => 'rent',
            'has_image' => fake()->boolean(70), // 70% have images
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * Get appropriate dimensions for advertisement type
     */
    private function getDimensionsForType(string $type): array
    {
        return match ($type) {
            'billboard' => [
                'width' => fake()->randomElement([6, 12, 18]),
                'height' => fake()->randomElement([3, 4, 6]),
            ],
            'led_screen' => [
                'width' => fake()->randomFloat(1, 1.5, 5),  // 1.5m - 5m
                'height' => fake()->randomFloat(1, 1, 3),   // 1m - 3m
            ],
            'citylight' => [
                'width' => fake()->randomFloat(2, 1, 2),
                'height' => fake()->randomFloat(2, 1.5, 3),
            ],
            'banner' => [
                'width' => fake()->randomElement([3, 5, 10]),
                'height' => fake()->randomElement([1, 2, 3]),
            ],
            'wall' => [
                'width' => fake()->randomElement([10, 15, 20]),
                'height' => fake()->randomElement([5, 10, 15]),
            ],
            'totem' => [
                'width' => fake()->randomFloat(2, 0.5, 2),
                'height' => fake()->randomFloat(2, 2, 5),
            ],
            default => [
                'width' => null,
                'height' => null,
            ],
        };
    }

    /**
     * Get appropriate variant for advertisement type
     */
    private function getVariantForType(string $type): ?string
    {
        return match ($type) {
            'billboard' => fake()->randomElement(['standard', 'three_sided', 'backlit']),
            'citylight' => fake()->randomElement(['single', 'double', 'digital']),
            'led_screen' => fake()->randomElement(['standard', 'interactive']),
            'totem' => fake()->randomElement(['single_sided', 'double_sided', 'multi_sided']),
            'transport' => fake()->randomElement(['bus', 'tram', 'metro', 'stop']),
            'mobile' => fake()->randomElement(['trailer', 'car', 'bike', 'other']),
            default => null,
        };
    }

    /**
     * State for active advertisements
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
        ]);
    }

    /**
     * State for rented advertisements
     */
    public function rented(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'rented',
        ]);
    }

    /**
     * State for LED screen with specific dimensions
     */
    public function ledScreen(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'led_screen',
            'variant' => 'standard',
            'width' => 2.5,  // meters
            'height' => 1.5, // meters
        ]);
    }

    /**
     * State for billboard with high traffic
     */
    public function billboardHighTraffic(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'billboard',
            'variant' => 'standard',
            'width' => 6,
            'height' => 3,
            'traffic_intensity' => 'high',
        ]);
    }

    /**
     * State for advertisement in Warsaw
     */
    public function inWarsaw(): static
    {
        return $this->state(fn (array $attributes) => [
            'city' => 'Warszawa',
            'latitude' => 52.2297,
            'longitude' => 21.0122,
        ]);
    }
}
