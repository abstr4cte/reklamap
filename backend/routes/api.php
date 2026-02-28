<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdvertisementController;
use App\Http\Controllers\StorageController;
use App\Http\Controllers\ManagementController;
use App\Http\Controllers\BlogController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// ─── Publiczne endpointy (tylko X-App-Key) ────────────────────────────────────
Route::get('listings', [AdvertisementController::class, 'index']);
Route::get('listings/{id}', [AdvertisementController::class, 'show']);
Route::post('listings', [AdvertisementController::class, 'store']);
Route::get('listings/{id}/similar', [AdvertisementController::class, 'similar']);
Route::get('listings/{id}/pdf', [AdvertisementController::class, 'generatePdf']);
Route::get('listings/pdf/comparison', [AdvertisementController::class, 'generateComparisonPdf']);

Route::post('listings/{id}/increment-views', [AdvertisementController::class, 'incrementViews']);
Route::post('listings/{id}/increment-phone-clicks', [AdvertisementController::class, 'incrementPhoneClicks']);
Route::post('listings/{id}/increment-email-clicks', [AdvertisementController::class, 'incrementEmailClicks']);

Route::get('listings/{id}/daily-stats', [AdvertisementController::class, 'getDailyStats']);
Route::post('listings/daily-stats/multiple', [AdvertisementController::class, 'getMultipleDailyStats']);

Route::get('blog', [BlogController::class, 'index']);
Route::get('blog/{slug}', [BlogController::class, 'show']);

Route::post('reports', [AdvertisementController::class, 'report']);
Route::post('feedback', [AdvertisementController::class, 'submitFeedback']);

// Rate limit na endpointy wysyłające maile (max 10 na 60 minut z jednego IP)
Route::middleware('throttle:10,60')->group(function () {
    Route::post('listings/{id}/contact', [AdvertisementController::class, 'contactOwner']);
    Route::post('contact', [AdvertisementController::class, 'submitContact']);
    Route::post('newsletter/subscribe', [AdvertisementController::class, 'subscribeNewsletter']);
});

// ─── Zarządzanie tokenami ─────────────────────────────────────────────────────
// Rate limit na wysyłanie linku (max 5 prób na godzinę z jednego IP)
Route::middleware('throttle:5,60')->post('management/send-link', [ManagementController::class, 'sendManagementLink']);
Route::get('management/validate/{token}', [ManagementController::class, 'validateToken']);

// ─── Wrażliwe operacje — wymagają X-App-Key + ważnego tokena zarządzającego ──
Route::middleware('management.token')->group(function () {
    Route::put('listings/{id}', [AdvertisementController::class, 'update']);
    Route::delete('listings/{id}', [AdvertisementController::class, 'destroy']);
    Route::patch('listings/{id}/status', [AdvertisementController::class, 'updateStatus']);
    Route::patch('advertisements/{id}/active', [AdvertisementController::class, 'updateActiveStatus']);
    Route::post('upload', [StorageController::class, 'upload']);
});
