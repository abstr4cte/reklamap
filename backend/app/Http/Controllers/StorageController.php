<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class StorageController extends Controller
{
    public function upload(Request $request)
    {
        $request->validate([
            'file' => 'required|image|max:10240', // 10MB max
        ]);

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $filename = Str::random(40) . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('public/advertisements', $filename);

            // Return public URL
            // Assuming storage:link is run, public/storage maps to storage/app/public
            $url = asset('storage/advertisements/' . $filename);

            // If running on localhost:8000 via artisan serve, asset() might point to localhost
            // We might need to adjust based on environment

            return response()->json($url); // Return just the string as expected by frontend? Or JSON object?
            // Frontend expects string? Let's check api.ts usage.
            // api.ts: return await api.storage.upload(item.file) -> returns string
            // But usually API returns JSON.
            // I'll return JSON and update frontend to extract it, OR return string directly (less standard but works).
            // Let's return JSON ['url' => $url] and update frontend.
        }

        return response()->json(['error' => 'No file uploaded'], 400);
    }
}
