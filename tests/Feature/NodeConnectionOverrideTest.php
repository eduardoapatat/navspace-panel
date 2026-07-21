<?php

use App\Enums\ConnectionOverrideType;
use App\Models\Building;
use App\Models\Node;
use App\Models\NodeConnectionOverride;

it('casts the type to the enum', function () {
    $override = NodeConnectionOverride::factory()->forced()->create();

    expect($override->type)->toBe(ConnectionOverrideType::Forced);
});

it('exposes the pair and its building', function () {
    $building = Building::factory()->create();
    $a = Node::factory()->for($building)->create();
    $b = Node::factory()->for($building)->create();

    $override = NodeConnectionOverride::factory()->create([
        'building_id' => $building->id,
        'node_a_id' => $a->id,
        'node_b_id' => $b->id,
    ]);

    expect($override->nodeA->id)->toBe($a->id)
        ->and($override->nodeB->id)->toBe($b->id)
        ->and($override->building->id)->toBe($building->id)
        ->and($building->connectionOverrides)->toHaveCount(1);
});

it('drops the rule when either node is deleted', function () {
    $building = Building::factory()->create();
    $a = Node::factory()->for($building)->create();
    $b = Node::factory()->for($building)->create();

    NodeConnectionOverride::factory()->create([
        'building_id' => $building->id,
        'node_a_id' => $a->id,
        'node_b_id' => $b->id,
    ]);

    $b->delete();

    expect(NodeConnectionOverride::count())->toBe(0);
});

it('drops the rule when the building is deleted', function () {
    $override = NodeConnectionOverride::factory()->create();

    $override->building->delete();

    expect(NodeConnectionOverride::count())->toBe(0);
});
