<?php

namespace App\Http\Controllers;

use App\Models\SearchAlert;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SearchAlertController extends Controller
{
    /**
     * Store a new search alert.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'type' => 'nullable|string',
            'city' => 'nullable|string',
            'region' => 'nullable|string',
            'filters' => 'nullable|array',
        ]);

        // Check if alert already exists for this email and identical criteria
        $existing = SearchAlert::where('email', $validated['email'])
            ->where('type', $validated['type'] ?? null)
            ->where('city', $validated['city'] ?? null)
            ->where('region', $validated['region'] ?? null)
            ->first();

        if ($existing) {
            // Check if filters are also identical
            if (json_encode($existing->filters) === json_encode($validated['filters'] ?? [])) {
                return response()->json(['message' => 'Masz już aktywne powiadomienie dla tych kryteriów.'], 200);
            }
        }

        $alert = SearchAlert::create([
            'email' => $validated['email'],
            'type' => $validated['type'] ?? null,
            'city' => $validated['city'] ?? null,
            'region' => $validated['region'] ?? null,
            'filters' => $validated['filters'] ?? [],
            'unsubscribe_token' => Str::random(40),
        ]);

        return response()->json([
            'message' => 'Twoje powiadomienie zostało zapisane. Będziemy Cię informować o nowych ofertach!',
            'alert' => $alert
        ], 201);
    }

    /**
     * Unsubscribe from search alert.
     */
    public function unsubscribe($token)
    {
        $alert = SearchAlert::where('unsubscribe_token', $token)->first();

        if (!$alert) {
            return response()->json(['message' => 'Nieprawidłowy lub wygasły token subskrypcji.'], 404);
        }

        $alert->delete();

        // Można zwrócić widok Blade z potwierdzeniem
        return "Subskrypcja została pomyślnie usunięta. Nie będziesz już otrzymywać powiadomień dla tych kryteriów.";
    }
}
