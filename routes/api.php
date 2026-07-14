<?php

use App\Http\Controllers\Api\BuildingController;
use App\Http\Controllers\Api\DestinationController;
use Illuminate\Support\Facades\Route;

// Public endpoints consumed by the Unity app.
Route::get('/buildings', [BuildingController::class, 'index']);
Route::get('/buildings/{building}', [BuildingController::class, 'show']);
Route::get('/destinations/{destination}', [DestinationController::class, 'show']);
