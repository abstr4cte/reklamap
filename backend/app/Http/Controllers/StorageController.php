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

        $request->validate([
            'file' => [
                'required',
                'file',
                'max:10240', // 10MB max
                function ($attribute, $value, $fail) {
                    $mimeType = $value->getMimeType();
                    $ext = strtolower($value->getClientOriginalExtension());

                    Log::info("Validating file - MIME: {$mimeType}, Extension: {$ext}");

                    // Accept if MIME type starts with 'image/'
                    if (str_starts_with($mimeType, 'image/')) {
                        return; // Valid
                    }

                    // Accept common image extensions as fallback (some mobile browsers send wrong MIME)
                    $allowedExts = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'tiff', 'tif', 'heic', 'heif', 'avif'];
                    if (in_array($ext, $allowedExts)) {
                        return; // Valid by extension
                    }

                    // Reject everything else
                    $fail("Nieobsługiwany format pliku (typ: {$mimeType}, rozszerzenie: .{$ext}). Dodaj zdjęcie w formacie JPG, PNG lub HEIC.");
                },
            ],
        ]);

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $baseFilename = Str::random(40);

            Log::info('File received: ' . $file->getClientOriginalName());
            Log::info('File MIME type: ' . $file->getMimeType());
            Log::info('Generated base filename: ' . $baseFilename);

            $jpgFilename  = $baseFilename . '.jpg';
            $webpFilename = $baseFilename . '.webp';

            $advertisementsPath = storage_path('app/public/advertisements/');

            // Ensure directory exists
            if (!file_exists($advertisementsPath)) {
                mkdir($advertisementsPath, 0755, true);
            }

            try {
                // Read image through Intervention Image
                // autoOrientation: true in config/image.php corrects EXIF rotation from mobile phones
                $img = Image::read($file->getRealPath());

                // Resize if too large (max 1920px width), preserve aspect ratio
                if ($img->width() > 1920) {
                    $img->scale(width: 1920);
                }

                // Save as JPG
                $jpgFullPath = $advertisementsPath . $jpgFilename;
                $img->toJpeg(90)->save($jpgFullPath);
                Log::info('JPG stored at: advertisements/' . $jpgFilename);

                // Save as WebP for better performance
                $webpFullPath = $advertisementsPath . $webpFilename;
                $img->toWebp(85)->save($webpFullPath);
                Log::info('WebP created: advertisements/' . $webpFilename);

                return response()->json([
                    'jpg'     => 'advertisements/' . $jpgFilename,
                    'webp'    => 'advertisements/' . $webpFilename,
                    'default' => 'advertisements/' . $jpgFilename,
                ]);

            } catch (\Exception $e) {
                Log::error('Image processing failed: ' . $e->getMessage());
                Log::error('Stack trace: ' . $e->getTraceAsString());

                // Fallback: store original file if Intervention Image fails
                try {
                    $jpgPath = $file->storeAs('advertisements', $jpgFilename, 'public');
                    Log::info('Fallback raw file stored at: ' . $jpgPath);

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
