<?php

use App\Models\Building;
use App\Models\Node;

it('stores its 3D position as floats and belongs to a building', function () {
    $building = Building::factory()->create();
    $node = Node::factory()->for($building)->create([
        'position_x' => -6.54,
        'position_y' => 0,
        'position_z' => 6.155,
    ]);

    expect($node->building->id)->toBe($building->id)
        ->and($node->position_x)->toBe(-6.54)
        ->and($node->position_z)->toBe(6.155)
        ->and($node->is_initial)->toBeFalse();
});

it('can be flagged as the calibration anchor', function () {
    $node = Node::factory()->initial()->create();

    expect($node->is_initial)->toBeTrue();
});

it('can optionally belong to a node group', function () {
    $node = Node::factory()->create();

    expect($node->node_group_id)->toBeNull();
});

it('is deleted when its building is deleted', function () {
    $node = Node::factory()->create();

    $node->building->delete();

    $this->assertDatabaseMissing('nodes', ['id' => $node->id]);
});
