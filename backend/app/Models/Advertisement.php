<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Advertisement extends Model
{
    use HasFactory;

    /**
     * The "type" of the auto-incrementing ID.
     *
     * @var string
     */
    protected $keyType = 'string';

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

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
        'price_unit',
        'has_lighting',
        'has_image',
        'price_includes_print',
        'graphic_design_help',
        'offer_type',
        'has_vat_invoice',
        'views',
        'is_active',
        'phone',
        'contact_preference',
        'images',
        'available_from',
        'price_negotiable',
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
        'has_lighting' => 'boolean',
        'has_image' => 'boolean',
        'price_includes_print' => 'boolean',
        'graphic_design_help' => 'boolean',
        'has_vat_invoice' => 'boolean',
        'is_active' => 'boolean',
        'images' => 'array',
        'available_from' => 'date',
        'price_negotiable' => 'boolean',
    ];

    /**
     * Boot function from Laravel.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->{$model->getKeyName()})) {
                $model->{$model->getKeyName()} = (string) Str::uuid();
            }
        });
    }
}
