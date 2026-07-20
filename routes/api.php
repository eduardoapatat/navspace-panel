<?php

use App\Http\Controllers\Api\BuildingController;
use App\Http\Controllers\Api\DestinationController;
use App\Http\Controllers\Api\HealthController;
use Illuminate\Support\Facades\Route;

// Public endpoints consumed by the Unity app.
Route::get('/health', [HealthController::class, 'index']);
Route::get('/buildings', [BuildingController::class, 'index']);
Route::get('/buildings/{building}', [BuildingController::class, 'show']);
Route::get('/destinations/{destination}', [DestinationController::class, 'show']);
