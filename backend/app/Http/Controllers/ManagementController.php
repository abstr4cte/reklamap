<?php

namespace App\Http\Controllers;

use App\Models\ManagementToken;
use App\Models\Advertisement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use App\Mail\ManagementLink;

class ManagementController extends Controller
{
    /**
     * Generate a management token and send an email with the link.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function sendManagementLink(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => 'required|email',
        ]);

        if (!Advertisement::where('owner_email', $validated['email'])->exists()) {
            return response()->json([
                'message' => 'Brak ogłoszeń przypisanych do tego e-maila.',
            ], 422);
        }

        // Delete any existing tokens for this email
        ManagementToken::where('email', $validated['email'])->delete();

        // Create a new token that expires in 30 days
        $token = ManagementToken::create([
            'email' => $validated['email'],
            'expires_at' => Carbon::now()->addDays(30)
        ]);

        // Send email with management link
        Mail::to($validated['email'])->send(new ManagementLink($token));

        return response()->json([
            'message' => 'Management link has been sent to your email',
        ]);
    }

    /**
     * Validate a management token and return associated advertisements.
     *
     * @param  string  $token
     * @return \Illuminate\Http\Response
     */
    public function validateToken(string $token): JsonResponse
    {
        $managementToken = ManagementToken::find($token);

        if (!$managementToken || $managementToken->isExpired()) {
            return response()->json([
                'message' => 'Invalid or expired token',
                'valid' => false
            ], 401);
        }

        // Get all advertisements associated with this email
        $advertisements = Advertisement::where('owner_email', $managementToken->email)->get();

        // Append aggregated daily stats for the last 30 days
        foreach ($advertisements as $ad) {
            $stats = \App\Models\AdvertisementDailyStat::where('advertisement_id', $ad->id)
                ->where('date', '>=', Carbon::now()->subDays(30))
                ->get();

            $ad->views_30d = $stats->sum('views');
            $ad->phone_clicks_30d = $stats->sum('phone_clicks');
            $ad->email_clicks_30d = $stats->sum('email_clicks');
        }

        return response()->json([
            'valid' => true,
            'email' => $managementToken->email,
            'expires_at' => $managementToken->expires_at,
            'listings' => $advertisements
        ]);
    }
}
