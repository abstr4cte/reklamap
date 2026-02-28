<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\ManagementToken;
use App\Models\Advertisement;

class VerifyManagementToken
{
    public function handle(Request $request, Closure $next): Response
    {
        // Pomiń OPTIONS (preflight)
        if ($request->isMethod('OPTIONS')) {
            return $next($request);
        }

        $tokenId = $request->header('X-Management-Token');

        if (!$tokenId) {
            return response()->json(['message' => 'Brak tokenu zarządzającego'], 403);
        }

        $managementToken = ManagementToken::find($tokenId);

        if (!$managementToken || $managementToken->isExpired()) {
            return response()->json(['message' => 'Token nieprawidłowy lub wygasł'], 401);
        }

        // Jeśli request dotyczy konkretnego ogłoszenia, sprawdź czy należy do właściciela tokena
        $listingId = $request->route('id') ?? $request->route('listing');
        if ($listingId) {
            $ad = Advertisement::find($listingId);
            if ($ad && $ad->owner_email !== $managementToken->email) {
                return response()->json(['message' => 'Brak uprawnień do tego ogłoszenia'], 403);
            }
        }

        // Przekaż email właściciela dalej do kontrolera jeśli potrzebny
        $request->attributes->set('management_email', $managementToken->email);

        return $next($request);
    }
}
