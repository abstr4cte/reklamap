<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class StorageController extends Controller
{
    public function upload(Request $request)
    {
        \Log::info('Upload request received');
        
        $request->validate([
            'file' => 'required|image|max:10240', // 10MB max
        ]);

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $filename = Str::random(40) . '.' . $file->getClientOriginalExtension();
            
            \Log::info('File received: ' . $file->getClientOriginalName());
            \Log::info('Generated filename: ' . $filename);
            
            // Jawnie używamy dysku 'public'
            $path = $file->storeAs('advertisements', $filename, 'public');
            \Log::info('File stored at path: ' . $path);

            // Zapisz tylko względną ścieżkę do zdjęcia zamiast pełnego URL
            // Dzięki temu aplikacja będzie działać poprawnie nawet po zmianie domeny
            $relativePath = 'advertisements/' . $filename;
            \Log::info('Returning relative path: ' . $relativePath);
            
            return response()->json($relativePath);
        }

        return response()->json(['error' => 'No file uploaded'], 400);
    }
}
