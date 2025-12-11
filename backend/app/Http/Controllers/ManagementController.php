<?php

namespace App\Http\Controllers;

use App\Models\ManagementToken;
use App\Models\Advertisement;
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
    public function sendManagementLink(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email',
        ]);

        // Create a new token that expires in 24 hours
        $token = ManagementToken::create([
            'email' => $validated['email'],
            'expires_at' => Carbon::now()->addHours(24),
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
    public function validateToken($token)
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

        return response()->json([
            'valid' => true,
            'email' => $managementToken->email,
            'expires_at' => $managementToken->expires_at,
            'advertisements' => $advertisements
        ]);
    }
}
