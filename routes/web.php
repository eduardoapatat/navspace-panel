<?php

use App\Http\Controllers\BuildingController;
use App\Http\Controllers\DestinationController;
use App\Http\Controllers\NodeController;
use App\Http\Controllers\NodeGroupController;
use App\Http\Controllers\SpaceTypeController;
use Illuminate\Support\Facades\Route;

// Entry point: there is no landing or dashboard page. Guests go to login,
// authenticated users go to the buildings list (the app's real home).
Route::get('/', function () {
    if (auth()->check()) {
        return to_route('buildings.index');
    }

    return to_route('login');
})->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    // The 3D node editor for a building (dedicated full-screen page).
    Route::get('buildings/{building}/nodes', [BuildingController::class, 'editNodes'])
        ->name('buildings.nodes');

    // Save the A* pathfinding parameters (used by Unity) for a building.
    Route::put('buildings/{building}/pathfinding', [BuildingController::class, 'updatePathfinding'])
        ->name('buildings.pathfinding');

    // The management page lives on edit; there is no standalone show page.
    Route::resource('buildings', BuildingController::class)
        ->except(['show']);

    // Node groups (optional labels like "Piso 1") are managed in the context
    // of their building (no standalone pages).
    Route::resource('buildings.node-groups', NodeGroupController::class)
        ->only(['store', 'update', 'destroy'])
        ->shallow();

    // Space types are managed in the context of their building.
    Route::resource('buildings.space-types', SpaceTypeController::class)
        ->only(['store', 'update', 'destroy'])
        ->shallow();

    // Destinations are managed in the context of their building.
    Route::resource('buildings.destinations', DestinationController::class)
        ->only(['store', 'update', 'destroy'])
        ->shallow();

    // Navigation nodes belong to a building and are placed from the 3D editor.
    // Bulk save: the editor keeps edits local and syncs the whole set at once.
    Route::put('buildings/{building}/nodes/sync', [NodeController::class, 'sync'])
        ->name('buildings.nodes.sync');
    Route::resource('buildings.nodes', NodeController::class)
        ->only(['store', 'update', 'destroy'])
        ->shallow();
});

require __DIR__.'/settings.php';
