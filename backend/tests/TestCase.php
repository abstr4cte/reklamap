<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Returns headers array with the X-App-Key required by all API routes.
     */
    protected function appKeyHeaders(): array
    {
        return ['X-App-Key' => config('app.internal_app_key')];
    }

    /**
     * Returns the valid data payload for creating a billboard advertisement.
     */
    protected function validBillboardData(array $overrides = []): array
    {
        return array_merge([
            'title'              => 'Test Billboard in Warsaw',
            'type'               => 'billboard',
            'variant'            => 'standard',
            'city'               => 'Warszawa',
            'location'           => 'ul. Testowa 1',
            'latitude'           => 52.23,
            'longitude'          => 21.01,
            'description'        => 'A test billboard description.',
            'price'              => 1000,
            'price_unit'         => 'month',
            'width'              => 6,
            'height'             => 3,
            'traffic_intensity'  => 'high',
            'owner_email'        => 'test@example.com',
            'phone'              => '123456789',
            'contact_preference' => 'email',
            'offer_type'         => 'rent',
            'orientation'        => 'horizontal',
        ], $overrides);
    }
}
