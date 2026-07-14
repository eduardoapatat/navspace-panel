<?php

use App\Models\Building;
use Database\Seeders\DiciSeeder;

it('lists every building for the Unity selector', function () {
    Building::factory()->count(3)->create();

    $response = $this->getJson('/api/buildings');

    $response->assertOk()
        ->assertJsonCount(3, 'data')
        ->assertJsonStructure([
            'data' => [
                ['id', 'name', 'address', 'image_url', 'model_url'],
            ],
        ]);
});

it('returns a building with its nodes, groups and destinations', function () {
    $this->seed(DiciSeeder::class);
    $building = Building::where('name', 'DICI')->first();

    $response = $this->getJson("/api/buildings/{$building->id}");

    $response->assertOk()
        ->assertJsonPath('data.name', 'DICI')
        ->assertJsonCount(1, 'data.node_groups')
        ->assertJsonCount(2, 'data.nodes')
        ->assertJsonCount(2, 'data.destinations')
        ->assertJsonPath('data.destinations.0.display_name', 'Licancabur');
});
