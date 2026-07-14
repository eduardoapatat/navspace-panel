<?php

use App\Models\Building;
use App\Models\SpaceType;
use App\Models\User;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

it('stores a space type under a building', function () {
    $building = Building::factory()->create();

    $this->post(route('buildings.space-types.store', $building), [
        'name' => 'Salas',
    ])->assertRedirect(route('buildings.edit', $building));

    $this->assertDatabaseHas('space_types', [
        'building_id' => $building->id,
        'name' => 'Salas',
    ]);
});

it('validates the space type name', function () {
    $building = Building::factory()->create();

    $this->post(route('buildings.space-types.store', $building), [])
        ->assertSessionHasErrors('name');
});

it('updates a space type', function () {
    $spaceType = SpaceType::factory()->create(['name' => 'Old']);

    $this->put(route('space-types.update', $spaceType), [
        'name' => 'Oficinas',
    ])->assertRedirect(route('buildings.edit', $spaceType->building_id));

    expect($spaceType->fresh()->name)->toBe('Oficinas');
});

it('deletes a space type', function () {
    $spaceType = SpaceType::factory()->create();

    $this->delete(route('space-types.destroy', $spaceType))
        ->assertRedirect(route('buildings.edit', $spaceType->building_id));

    $this->assertDatabaseMissing('space_types', ['id' => $spaceType->id]);
});
