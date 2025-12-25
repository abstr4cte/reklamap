<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyAppKey
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->isMethod('OPTIONS')) {
            return $next($request);
        }

        $provided = $request->header('X-App-Key');
        $expected = config('app.internal_app_key');

        if (!$expected || !$provided || !hash_equals((string) $expected, (string) $provided)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return $next($request);
    }
}
