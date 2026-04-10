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
Route::get('listings/map-pins', [AdvertisementController::class, 'mapPins']);
Route::get('listings/{id}', [AdvertisementController::class, 'show']);

// Dodawanie ogłoszeń - rate limit 100/h (dla agencji), bez reCAPTCHA
Route::middleware('throttle:100,60')->post('listings', [AdvertisementController::class, 'store']);

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

// Wszystkie formularze publiczne - rate limit 10/h + reCAPTCHA
Route::middleware(['throttle:10,60', 'verify.recaptcha'])->group(function () {
    Route::post('listings/{id}/contact', [AdvertisementController::class, 'contactOwner']);
    Route::post('contact', [AdvertisementController::class, 'submitContact']);
    Route::post('newsletter/subscribe', [AdvertisementController::class, 'subscribeNewsletter']);
    Route::post('search-alerts', [\App\Http\Controllers\SearchAlertController::class, 'store']);
    Route::post('reports', [AdvertisementController::class, 'report']);
    Route::post('feedback', [AdvertisementController::class, 'submitFeedback']);
});

Route::get('search-alerts/unsubscribe/{token}', [\App\Http\Controllers\SearchAlertController::class, 'unsubscribe'])->name('search-alerts.unsubscribe');
Route::get('newsletter/unsubscribe/{token}', [\App\Http\Controllers\NewsletterController::class, 'unsubscribe'])->name('newsletter.unsubscribe');

// ─── Upload zdjęć (publiczny, tylko X-App-Key, rate limit 100/min) ────────────
Route::middleware('throttle:100,1')->post('upload', [StorageController::class, 'upload']);

// ─── Zarządzanie tokenami ─────────────────────────────────────────────────────
// Rate limit na wysyłanie linku (max 5 prób na godzinę z jednego IP) + reCAPTCHA
Route::middleware(['throttle:5,60', 'verify.recaptcha'])->post('management/send-link', [ManagementController::class, 'sendManagementLink']);
Route::get('management/validate/{token}', [ManagementController::class, 'validateToken']);

// ─── Wrażliwe operacje — wymagają X-App-Key + ważnego tokena zarządzającego ──
Route::middleware('management.token')->group(function () {
    Route::put('listings/{id}', [AdvertisementController::class, 'update']);
    Route::delete('listings/{id}', [AdvertisementController::class, 'destroy']);
    Route::patch('listings/{id}/status', [AdvertisementController::class, 'updateStatus']);
    Route::patch('advertisements/{id}/active', [AdvertisementController::class, 'updateActiveStatus']);
});
