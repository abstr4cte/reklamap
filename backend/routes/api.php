<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdvertisementController;
use App\Http\Controllers\StorageController;
use App\Http\Controllers\ManagementController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::apiResource('advertisements', AdvertisementController::class);
Route::post('advertisements/{id}/increment-views', [AdvertisementController::class, 'incrementViews']);
Route::get('advertisements/{id}/similar', [AdvertisementController::class, 'similar']);
Route::post('reports', [AdvertisementController::class, 'report']);
Route::get('advertisements/{id}/pdf', [AdvertisementController::class, 'generatePdf']);
Route::get('advertisements/pdf/comparison', [AdvertisementController::class, 'generateComparisonPdf']);
Route::post('upload', [StorageController::class, 'upload']);

// Management token routes
Route::post('management/send-link', [ManagementController::class, 'sendManagementLink']);
Route::get('management/validate/{token}', [ManagementController::class, 'validateToken']);
