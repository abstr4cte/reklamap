<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdvertisementController;
use App\Http\Controllers\StorageController;
use App\Http\Controllers\ManagementController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::apiResource('listings', AdvertisementController::class);
Route::patch('listings/{id}/status', [AdvertisementController::class, 'updateStatus']);
Route::patch('advertisements/{id}/active', [AdvertisementController::class, 'updateActiveStatus']);
Route::post('listings/{id}/increment-views', [AdvertisementController::class, 'incrementViews']);
Route::get('listings/{id}/similar', [AdvertisementController::class, 'similar']);
Route::post('listings/{id}/contact', [AdvertisementController::class, 'contactOwner']);
Route::post('reports', [AdvertisementController::class, 'report']);
Route::post('feedback', [AdvertisementController::class, 'submitFeedback']);
Route::post('contact', [AdvertisementController::class, 'submitContact']);
Route::post('newsletter/subscribe', [AdvertisementController::class, 'subscribeNewsletter']);
Route::get('listings/{id}/pdf', [AdvertisementController::class, 'generatePdf']);
Route::get('listings/pdf/comparison', [AdvertisementController::class, 'generateComparisonPdf']);
Route::post('upload', [StorageController::class, 'upload']);

// Management token routes
Route::post('management/send-link', [ManagementController::class, 'sendManagementLink']);
Route::get('management/validate/{token}', [ManagementController::class, 'validateToken']);
