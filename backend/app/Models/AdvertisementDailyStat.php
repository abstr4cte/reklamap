<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdvertisementDailyStat extends Model
{
    use HasFactory;

    protected $table = 'advertisement_daily_stats';

    protected $fillable = [
        'advertisement_id',
        'date',
        'views',
        'phone_clicks',
        'email_clicks',
    ];

    protected $casts = [
        'date' => 'date',
        'views' => 'integer',
        'phone_clicks' => 'integer',
        'email_clicks' => 'integer',
    ];

    /**
     * Relacja do ogłoszenia
     */
    public function advertisement(): BelongsTo
    {
        return $this->belongsTo(Advertisement::class);
    }

    /**
     * Pobierz lub stwórz wpis dla dzisiaj
     */
    public static function getTodayOrCreate(string $advertisementId): self
    {
        return self::firstOrCreate(
            [
                'advertisement_id' => $advertisementId,
                'date' => now()->toDateString(),
            ],
            [
                'views' => 0,
                'phone_clicks' => 0,
                'email_clicks' => 0,
            ]
        );
    }

    /**
     * Pobierz statystyki za ostatnie N dni
     */
    public static function getStatsForPeriod(string $advertisementId, int $days = 30): array
    {
        return self::where('advertisement_id', $advertisementId)
            ->where('date', '>=', now()->subDays($days)->toDateString())
            ->orderBy('date', 'asc')
            ->get()
            ->map(function ($stat) {
                return [
                    'date' => $stat->date->format('Y-m-d'),
                    'views' => $stat->views,
                    'phone_clicks' => $stat->phone_clicks,
                    'email_clicks' => $stat->email_clicks,
                    'total_clicks' => $stat->phone_clicks + $stat->email_clicks,
                ];
            })
            ->toArray();
    }
}
