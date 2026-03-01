<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Http;

class VerifyRecaptcha
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Skip verification if reCAPTCHA is not configured
        if (!config('services.recaptcha.secret')) {
            return $next($request);
        }

        // Get the reCAPTCHA token from request
        $token = $request->input('recaptcha_token') ?? $request->header('X-Recaptcha-Token');

        if (!$token) {
            return response()->json([
                'message' => 'reCAPTCHA token is missing',
                'errors' => ['recaptcha' => ['reCAPTCHA verification failed']]
            ], 422);
        }

        // Verify token with Google
        try {
            $response = Http::asForm()->post('https://www.google.com/recaptcha/api/siteverify', [
                'secret' => config('services.recaptcha.secret'),
                'response' => $token,
            ]);

            $data = $response->json();

            // Check if verification was successful
            if (!$data['success'] || ($data['score'] ?? 1) < 0.5) {
                return response()->json([
                    'message' => 'reCAPTCHA verification failed',
                    'errors' => ['recaptcha' => ['Verification failed. Please try again.']]
                ], 422);
            }

            return $next($request);
        } catch (\Exception $e) {
            \Log::error('reCAPTCHA verification error: ' . $e->getMessage());

            // Allow request to proceed if verification fails (don't block users)
            return $next($request);
        }
    }
}
