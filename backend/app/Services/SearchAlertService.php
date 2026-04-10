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
            // Skip if the alert subscriber is the author of the advertisement
            if (strtolower($alert->email) === strtolower($ad->owner_email)) {
                continue;
            }

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
     * Alerts match only on type/city/region — no advanced filter checks.
     */
    protected function matchesAdvancedFilters(Advertisement $ad, $filters): bool
    {
        return true;
    }
}
