<?php

use App\Models\Building;
use App\Models\SpaceType;

it('belongs to a building', function () {
    $building = Building::factory()->create();
    $spaceType = SpaceType::factory()->for($building)->create(['name' => 'Salas']);

    expect($spaceType->building->id)->toBe($building->id)
        ->and($spaceType->name)->toBe('Salas')
        ->and($spaceType->icon)->toBeNull();
});

it('is shared at the building level, not the floor level', function () {
    $building = Building::factory()->create();
    SpaceType::factory()->count(2)->for($building)->create();

    expect($building->spaceTypes)->toHaveCount(2);
});

it('is deleted when its building is deleted', function () {
    $spaceType = SpaceType::factory()->create();

    $spaceType->building->delete();

    $this->assertDatabaseMissing('space_types', ['id' => $spaceType->id]);
});
