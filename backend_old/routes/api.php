<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::apiResource('advertisements', AdvertisementController::class);
Route::post('advertisements/{id}/increment-views', [AdvertisementController::class, 'incrementViews']);
Route::get('advertisements/{id}/similar', [AdvertisementController::class, 'similar']);
Route::post('reports', [AdvertisementController::class, 'report']);
Route::post('upload', [StorageController::class, 'upload']);
