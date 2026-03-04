<?php

namespace App\Services;

use App\Models\Advertisement;
use App\Models\SearchAlert;
use App\Mail\SearchAlertMail;
use Illuminate\Support\Facades\Mail;

class SearchAlertService
{
    /**
     * Check all active search alerts against a newly created advertisement.
     */
    public function checkAlerts(Advertisement $ad)
    {
        // 1. Find alerts where main categories match (type, city or region)
        $matchingAlerts = SearchAlert::where(function ($query) use ($ad) {
            $query->whereNull('type')
                ->orWhere('type', $ad->type);
        })->where(function ($query) use ($ad) {
            $query->whereNull('city')
                ->orWhere('city', $ad->city);
        })->where(function ($query) use ($ad) {
            $query->whereNull('region')
                ->orWhere('region', $ad->region);
        })->get();

        foreach ($matchingAlerts as $alert) {
            // 2. Check advanced filters (if present)
            if (!$this->matchesAdvancedFilters($ad, $alert->filters)) {
                continue;
            }

            // 3. Send notification
            try {
                Mail::to($alert->email)->send(new SearchAlertMail($ad, $alert->unsubscribe_token));

                // 4. Update last notified timestamp
                $alert->update(['last_notified_at' => now()]);
            } catch (\Exception $e) {
                \Log::error("Failed to send search alert email to {$alert->email} for ad {$ad->id}: " . $e->getMessage());
            }
        }
    }

    /**
     * Check if ad matches additional criteria like price range or dimensions.
     */
    protected function matchesAdvancedFilters(Advertisement $ad, $filters)
    {
        if (empty($filters)) {
            return true;
        }

        // Price check
        if (isset($filters['priceFrom']) && $ad->price < (float) $filters['priceFrom'])
            return false;
        if (isset($filters['priceTo']) && $ad->price > (float) $filters['priceTo'])
            return false;

        // Rental period check
        if (isset($filters['rentalPeriod']) && !empty($filters['rentalPeriod'])) {
            if ($ad->rental_period !== $filters['rentalPeriod'])
                return false;
        }

        // Width/Height check
        if (isset($filters['widthFrom']) && $ad->width < (float) $filters['widthFrom'])
            return false;
        if (isset($filters['widthTo']) && $ad->width > (float) $filters['widthTo'])
            return false;
        if (isset($filters['heightFrom']) && $ad->height < (float) $filters['heightFrom'])
            return false;
        if (isset($filters['heightTo']) && $ad->height > (float) $filters['heightTo'])
            return false;

        return true;
    }
}
