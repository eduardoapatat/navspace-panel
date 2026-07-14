<?php

use App\Models\Building;
use App\Models\NodeGroup;

it('belongs to a building', function () {
    $building = Building::factory()->create();
    $group = NodeGroup::factory()->for($building)->create([
        'name' => 'Primer piso',
    ]);

    expect($group->building->id)->toBe($building->id)
        ->and($group->name)->toBe('Primer piso');
});

it('is listed under its building node groups relation', function () {
    $building = Building::factory()->create();
    NodeGroup::factory()->count(3)->for($building)->create();

    expect($building->nodeGroups)->toHaveCount(3);
});

it('is deleted when its building is deleted', function () {
    $group = NodeGroup::factory()->create();

    $group->building->delete();

    $this->assertDatabaseMissing('node_groups', ['id' => $group->id]);
});
