<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Laravel\Facades\Image;

class StorageController extends Controller
{
    public function upload(Request $request)
    {
        Log::info('Upload request received');

        // Accept also HEIC/HEIF from iPhones and other mobile formats
        $request->validate([
            'file' => [
                'required',
                'file',
                'max:10240', // 10MB max
                function ($attribute, $value, $fail) {
                    $mimeType = $value->getMimeType();
                    $allowedMimes = [
                        'image/jpeg', 'image/jpg', 'image/png', 'image/gif',
                        'image/webp', 'image/bmp', 'image/tiff',
                        'image/heic', 'image/heif', // iPhone formats
                        'image/x-ci-raw', 'image/avif',
                    ];
                    if (!in_array($mimeType, $allowedMimes)) {
                        // Also check by extension as fallback
                        $ext = strtolower($value->getClientOriginalExtension());
                        $allowedExts = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'tiff', 'tif', 'heic', 'heif', 'avif'];
                        if (!in_array($ext, $allowedExts)) {
                            $fail('Plik musi być obrazem (JPG, PNG, WebP, HEIC itp.)');
                        }
                    }
                },
            ],
        ]);

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $baseFilename = Str::random(40);

            Log::info('File received: ' . $file->getClientOriginalName());
            Log::info('File MIME type: ' . $file->getMimeType());
            Log::info('Generated base filename: ' . $baseFilename);

            $jpgFilename = $baseFilename . '.jpg';
            $webpFilename = $baseFilename . '.webp';

            $advertisementsPath = storage_path('app/public/advertisements/');

            // Ensure directory exists
            if (!file_exists($advertisementsPath)) {
                mkdir($advertisementsPath, 0755, true);
            }

            try {
                // Read image through Intervention Image - this handles EXIF orientation
                // auto-correction is configured in config/image.php (autoOrientation: true)
                $img = Image::read($file->getRealPath());

                // Resize if too large (max 1920px width), preserve aspect ratio
                if ($img->width() > 1920) {
                    $img->scale(width: 1920);
                }

                // Save as JPG (fallback)
                $jpgFullPath = $advertisementsPath . $jpgFilename;
                $img->toJpeg(90)->save($jpgFullPath);
                Log::info('JPG stored at: advertisements/' . $jpgFilename);

                // Save as WebP for better performance
                $webpFullPath = $advertisementsPath . $webpFilename;
                $img->toWebp(85)->save($webpFullPath);
                Log::info('WebP created: advertisements/' . $webpFilename);

                // Return both paths
                return response()->json([
                    'jpg'     => 'advertisements/' . $jpgFilename,
                    'webp'    => 'advertisements/' . $webpFilename,
                    'default' => 'advertisements/' . $jpgFilename,
                ]);

            } catch (\Exception $e) {
                Log::error('Image processing failed: ' . $e->getMessage());
                Log::error('Stack trace: ' . $e->getTraceAsString());

                // Fallback: try to store original file if Intervention Image fails
                try {
                    $jpgPath = $file->storeAs('advertisements', $jpgFilename, 'public');
                    Log::info('Fallback JPG stored at: ' . $jpgPath);

                    return response()->json([
                        'jpg'     => 'advertisements/' . $jpgFilename,
                        'default' => 'advertisements/' . $jpgFilename,
                    ]);
                } catch (\Exception $e2) {
                    Log::error('Fallback storage also failed: ' . $e2->getMessage());
                    return response()->json(['error' => 'Nie można przetworzyć zdjęcia: ' . $e->getMessage()], 500);
                }
            }
        }

        return response()->json(['error' => 'Brak pliku do przesłania'], 400);
    }
}
