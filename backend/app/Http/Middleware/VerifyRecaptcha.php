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

        // Skip verification in local and testing environments
        if (app()->environment('local', 'testing')) {
            return $next($request);
        }

        // Get the reCAPTCHA token from request
        $token = $request->input('recaptcha_token') ?? $request->header('X-Recaptcha-Token');

        if (!$token) {
            // Fail-open, tak samo jak przy wyjątku z zapytania do Google (niżej): pusty token
            // najczęściej oznacza, że grecaptcha.execute() nie zdążył w 5 s (wolne łącze,
            // ad-blocker/rozszerzenie prywatności blokujące recaptcha/api.js) — realny
            // wystawca traci wtedy ogłoszenie bez żadnego jasnego komunikatu (audyt SEO
            // 2026-09-18, SEO_TECH_AUDIT.md). Trasy z tym middlewarem mają już throttle
            // (np. 'throttle:10,60') jako właściwą warstwę ochrony przed spamem — blokowanie
            // TU tylko odcina realne zgłoszenia, nie boty (bot równie łatwo wyśle pusty token
            // co żaden).
            \Log::warning('reCAPTCHA token missing — przepuszczono (fail-open)', [
                'path' => $request->path(),
                'ip' => $request->ip(),
            ]);

            return $next($request);
        }

        // Verify token with Google
        try {
            $response = Http::asForm()->post('https://www.google.com/recaptcha/api/siteverify', [
                'secret' => config('services.recaptcha.secret'),
                'response' => $token,
            ]);

            $data = $response->json();
            $score = $data['score'] ?? 0;

            // Log the score for debugging
            \Log::info('reCAPTCHA verification', [
                'success' => $data['success'] ?? false,
                'score' => $score,
                'action' => $data['action'] ?? 'unknown',
                'hostname' => $data['hostname'] ?? 'unknown'
            ]);

            // Check if verification was successful
            // Lowered threshold from 0.5 to 0.3 for better UX (Google recommends 0.5, but can be adjusted)
            if (!$data['success'] || $score < 0.3) {
                \Log::warning('reCAPTCHA verification failed', [
                    'score' => $score,
                    'success' => $data['success'] ?? false,
                    'error-codes' => $data['error-codes'] ?? []
                ]);

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
