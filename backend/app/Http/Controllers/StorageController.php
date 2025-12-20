<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Laravel\Facades\Image;

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
            $baseFilename = Str::random(40);
            
            \Log::info('File received: ' . $file->getClientOriginalName());
            \Log::info('Generated base filename: ' . $baseFilename);
            
            // Save original JPG/PNG
            $originalExt = $file->getClientOriginalExtension();
            $jpgFilename = $baseFilename . '.jpg';
            
            // Store original as JPG (fallback)
            $jpgPath = $file->storeAs('advertisements', $jpgFilename, 'public');
            \Log::info('JPG stored at: ' . $jpgPath);
            
            // Convert to WebP for better performance
            try {
                $img = Image::read($file);
                
                // Resize if too large (max 1920px width)
                if ($img->width() > 1920) {
                    $img->scale(width: 1920);
                }
                
                // Save as WebP
                $webpFilename = $baseFilename . '.webp';
                $webpFullPath = storage_path('app/public/advertisements/' . $webpFilename);
                $img->toWebp(85)->save($webpFullPath);
                
                \Log::info('WebP created: ' . $webpFilename);
                
                // Return both paths
                return response()->json([
                    'jpg' => 'advertisements/' . $jpgFilename,
                    'webp' => 'advertisements/' . $webpFilename,
                    'default' => 'advertisements/' . $jpgFilename // Fallback
                ]);
            } catch (\Exception $e) {
                \Log::error('WebP conversion failed: ' . $e->getMessage());
                
                // If WebP conversion fails, return only JPG
                return response()->json([
                    'jpg' => 'advertisements/' . $jpgFilename,
                    'default' => 'advertisements/' . $jpgFilename
                ]);
            }
        }

        return response()->json(['error' => 'No file uploaded'], 400);
    }
}
