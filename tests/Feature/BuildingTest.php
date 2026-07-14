<?php

use App\Models\Building;

it('persists a building with its fields', function () {
    $building = Building::factory()->create([
        'name' => 'DICI',
        'address' => 'Av. General Velásquez 1775, Arica',
    ]);

    expect($building->name)->toBe('DICI')
        ->and($building->address)->toBe('Av. General Velásquez 1775, Arica')
        ->and($building->model_url)->toBeNull();

    $this->assertDatabaseHas('buildings', ['name' => 'DICI']);
});

it('derives the model url from the stored relative path', function () {
    $building = Building::factory()->create([
        'model_path' => 'models/dici.glb',
    ]);

    expect($building->model_url)->toBe(url('storage/models/dici.glb'));
});
