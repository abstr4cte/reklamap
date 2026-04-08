<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Advertisement extends Model
{
    use HasFactory;

    // Standardowe auto-inkrementowane ID

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'title',
        'type',
        'location',
        'city',
        'latitude',
        'longitude',
        'description',
        'price',
        'width',
        'height',
        'image_url',
        'owner_email',
        'status',
        'region',
        'orientation',
        'traffic_intensity',
        'rental_period',
        'price_unit',
        'has_backlight',
        'has_image',
        'price_includes_print',
        'price_includes_mounting',
        'graphic_design_help',
        'offer_type',
        'has_vat_invoice',
        'is_active',
        'is_verified',
        'phone',
        'contact_preference',
        'images',
        'available_from',
        'price_negotiable',
        // Nowe pola specyficzne dla typów
        'variant',
        'road_class',
        'traffic_direction',
        'traffic_type',
        'environment',
        'transport_scope',
        'vehicle_count',
        'mobile_exposure_mode',
        'operating_hours',
        'route_area',
        'campaign_duration',
        'slug',
        'map_screenshot_path',
        // Pola techniczne dla LED screens
        'resolution',
        'pixel_pitch',
        'brightness',
        // Nowe pola dla rozszerzonych opcji
        'lighting_type',
        'daily_passengers',
        'operating_zone',
        'ambient_light_control',
        'lighting_type_banner',
        // OTS
        'estimated_daily_views',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'price' => 'decimal:2',
        'width' => 'decimal:2',
        'height' => 'decimal:2',
        'has_backlight' => 'boolean',
        'has_image' => 'boolean',
        'price_includes_print' => 'boolean',
        'price_includes_mounting' => 'boolean',
        'graphic_design_help' => 'boolean',
        'has_vat_invoice' => 'boolean',
        'is_active' => 'boolean',
        'images' => 'array',
        'traffic_direction' => 'array',
        'traffic_type' => 'array',
        'available_from' => 'datetime',
        'price_negotiable' => 'boolean',
        'vehicle_count' => 'integer',
        'campaign_duration' => 'integer',
        'pixel_pitch' => 'decimal:2',
        'brightness' => 'integer',
        'daily_passengers' => 'integer',
        'ambient_light_control' => 'boolean',
        'estimated_daily_views' => 'integer',
    ];

    protected $appends = ['display_status', 'full_url'];

    /**
     * Oblicz wyświetlany status na podstawie available_from
     * Jeśli status=active i available_from jest w przyszłości -> "soon_available"
     * W przeciwnym razie -> używamy status z bazy
     */
    public function getDisplayStatusAttribute(): string
    {
        // Jeśli ma w bazie ustawione "wkrótce dostępne", ale data minęła -> jest wolne
        if ($this->status === 'soon_available' && $this->available_from && $this->available_from->lte(now())) {
            return 'active';
        }

        // Jeśli status to active i mamy datę available_from w przyszłości
        if ($this->status === 'active' && $this->available_from && $this->available_from->gt(now())) {
            return 'soon_available';
        }

        return $this->status;
    }

    public function getFullUrlAttribute(): string
    {
        $typeMapping = [
            'billboard' => 'billboardy',
            'citylight' => 'citylighty',
            'led_screen' => 'ekrany-led',
            'banner' => 'banery',
            'wall' => 'sciany-reklamowe',
            'totem' => 'totemy-reklamowe',
            'transport' => 'reklama-w-transporcie',
            'mobile' => 'reklama-mobilna',
            'other' => 'inne'
        ];

        $typeUrl = $typeMapping[$this->type] ?? 'inne';
        $citySlug = \Illuminate\Support\Str::slug($this->city);

        return "/powierzchnia-reklamowa/{$typeUrl}/{$citySlug}/{$this->slug}";
    }

    /**
     * Relacja do statystyk dziennych
     */
    public function dailyStats()
    {
        return $this->hasMany(AdvertisementDailyStat::class);
    }

    // Usunięto metodę boot generującą UUID
}
