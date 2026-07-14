<?php

use App\Models\Building;
use App\Models\User;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

it('saves the pathfinding config on the building', function () {
    $building = Building::factory()->create();

    $this->put(route('buildings.pathfinding', $building), [
        'max_distance' => 7.5,
        'max_y_distance' => 1.5,
        'thick_radius' => 0.3,
        'check_walls' => true,
    ])->assertRedirect();

    expect($building->fresh()->pathfinding_config)->toBe([
        'max_distance' => 7.5,
        'max_y_distance' => 1.5,
        'thick_radius' => 0.3,
        'check_walls' => true,
    ]);
});

it('validates the pathfinding config', function () {
    $building = Building::factory()->create();

    $this->put(route('buildings.pathfinding', $building), [
        'max_distance' => -1,
        'max_y_distance' => -1,
        'thick_radius' => 'x',
    ])->assertSessionHasErrors(['max_distance', 'max_y_distance', 'thick_radius', 'check_walls']);
});

it('exposes the pathfinding config in the building API detail', function () {
    $building = Building::factory()->create([
        'pathfinding_config' => [
            'max_distance' => 9.0,
            'thick_radius' => 0.5,
            'check_walls' => false,
        ],
    ]);

    $this->getJson("/api/buildings/{$building->id}")
        ->assertOk()
        ->assertJsonPath('data.pathfinding_config.max_distance', fn ($v) => (float) $v === 9.0)
        ->assertJsonPath('data.pathfinding_config.check_walls', false);
});

it('returns default pathfinding config when none is set', function () {
    $building = Building::factory()->create(['pathfinding_config' => null]);

    $this->getJson("/api/buildings/{$building->id}")
        ->assertOk()
        ->assertJsonPath('data.pathfinding_config.max_distance', fn ($v) => (float) $v === 5.0)
        ->assertJsonPath('data.pathfinding_config.max_y_distance', fn ($v) => (float) $v === 1.0)
        ->assertJsonPath('data.pathfinding_config.check_walls', false);
});
