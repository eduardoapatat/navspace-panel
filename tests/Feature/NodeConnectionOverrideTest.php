<?php

use App\Enums\ConnectionOverrideType;
use App\Models\Building;
use App\Models\Node;
use App\Models\NodeConnectionOverride;
use App\Models\User;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

/**
 * A node entry as the 3D editor sends it: local_id is the editor's stable key
 * (negative while the node has never been saved).
 *
 * @return array<string, mixed>
 */
function nodePayload(int $localId, ?int $id = null, float $x = 0.0): array
{
    return [
        'local_id' => $localId,
        'id' => $id,
        'position_x' => $x,
        'position_y' => 0,
        'position_z' => 0,
        'is_initial' => false,
    ];
}

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

it('resolves overrides that reference brand new nodes', function () {
    $building = Building::factory()->create();

    $this->put(route('buildings.nodes.sync', $building), [
        'nodes' => [nodePayload(-1), nodePayload(-2, x: 1)],
        'connection_overrides' => [['a' => -1, 'b' => -2, 'type' => 'blocked']],
    ])->assertRedirect();

    $ids = $building->nodes()->orderBy('id')->pluck('id');
    $override = NodeConnectionOverride::sole();

    expect($override->node_a_id)->toBe($ids[0])
        ->and($override->node_b_id)->toBe($ids[1])
        ->and($override->type)->toBe(ConnectionOverrideType::Blocked)
        ->and($override->building_id)->toBe($building->id);
});

it('normalizes the pair so the lowest node id comes first', function () {
    $building = Building::factory()->create();

    // Sent the other way around: -2 is created second, so it gets the higher id.
    $this->put(route('buildings.nodes.sync', $building), [
        'nodes' => [nodePayload(-1), nodePayload(-2, x: 1)],
        'connection_overrides' => [['a' => -2, 'b' => -1, 'type' => 'forced']],
    ])->assertRedirect();

    $ids = $building->nodes()->orderBy('id')->pluck('id');
    $override = NodeConnectionOverride::sole();

    expect($override->node_a_id)->toBe($ids[0])
        ->and($override->node_b_id)->toBe($ids[1]);
});

it('collapses a pair sent in both directions into one rule', function () {
    $building = Building::factory()->create();

    $this->put(route('buildings.nodes.sync', $building), [
        'nodes' => [nodePayload(-1), nodePayload(-2, x: 1)],
        'connection_overrides' => [
            ['a' => -1, 'b' => -2, 'type' => 'blocked'],
            ['a' => -2, 'b' => -1, 'type' => 'blocked'],
        ],
    ])->assertRedirect();

    expect(NodeConnectionOverride::count())->toBe(1);
});

it('replaces the whole set of overrides', function () {
    $building = Building::factory()->create();
    $a = Node::factory()->for($building)->create();
    $b = Node::factory()->for($building)->create();
    NodeConnectionOverride::factory()->create([
        'building_id' => $building->id,
        'node_a_id' => $a->id,
        'node_b_id' => $b->id,
    ]);

    $this->put(route('buildings.nodes.sync', $building), [
        'nodes' => [nodePayload($a->id, $a->id), nodePayload($b->id, $b->id, x: 1)],
        'connection_overrides' => [],
    ])->assertRedirect();

    expect(NodeConnectionOverride::count())->toBe(0)
        ->and($building->nodes()->count())->toBe(2);
});

it('leaves the overrides untouched when the key is omitted', function () {
    $building = Building::factory()->create();
    $a = Node::factory()->for($building)->create();
    $b = Node::factory()->for($building)->create();
    NodeConnectionOverride::factory()->create([
        'building_id' => $building->id,
        'node_a_id' => $a->id,
        'node_b_id' => $b->id,
    ]);

    $this->put(route('buildings.nodes.sync', $building), [
        'nodes' => [nodePayload($a->id, $a->id), nodePayload($b->id, $b->id, x: 1)],
    ])->assertRedirect();

    expect(NodeConnectionOverride::count())->toBe(1);
});

it('removes the overrides of a node deleted through the sync', function () {
    $building = Building::factory()->create();
    $a = Node::factory()->for($building)->create();
    $b = Node::factory()->for($building)->create();
    NodeConnectionOverride::factory()->create([
        'building_id' => $building->id,
        'node_a_id' => $a->id,
        'node_b_id' => $b->id,
    ]);

    // $b is left out of the payload, so it is deleted and its rule cascades.
    $this->put(route('buildings.nodes.sync', $building), [
        'nodes' => [nodePayload($a->id, $a->id)],
    ])->assertRedirect();

    expect(NodeConnectionOverride::count())->toBe(0);
});

it('rejects an override joining a node with itself', function () {
    $building = Building::factory()->create();

    $this->put(route('buildings.nodes.sync', $building), [
        'nodes' => [nodePayload(-1)],
        'connection_overrides' => [['a' => -1, 'b' => -1, 'type' => 'blocked']],
    ])->assertSessionHasErrors('connection_overrides.0.b');
});

it('rejects an override referencing a node outside the payload', function () {
    $building = Building::factory()->create();

    $this->put(route('buildings.nodes.sync', $building), [
        'nodes' => [nodePayload(-1)],
        'connection_overrides' => [['a' => -1, 'b' => -99, 'type' => 'blocked']],
    ])->assertSessionHasErrors('connection_overrides.0.b');
});

it('rejects an unknown override type', function () {
    $building = Building::factory()->create();

    $this->put(route('buildings.nodes.sync', $building), [
        'nodes' => [nodePayload(-1), nodePayload(-2, x: 1)],
        'connection_overrides' => [['a' => -1, 'b' => -2, 'type' => 'maybe']],
    ])->assertSessionHasErrors('connection_overrides.0.type');
});

it('serializes the overrides for the Unity app', function () {
    $building = Building::factory()->create();
    $a = Node::factory()->for($building)->create();
    $b = Node::factory()->for($building)->create();
    NodeConnectionOverride::factory()->forced()->create([
        'building_id' => $building->id,
        'node_a_id' => min($a->id, $b->id),
        'node_b_id' => max($a->id, $b->id),
    ]);

    $this->getJson("/api/buildings/{$building->id}")
        ->assertOk()
        ->assertJsonCount(1, 'data.connection_overrides')
        ->assertJsonPath('data.connection_overrides.0.a', min($a->id, $b->id))
        ->assertJsonPath('data.connection_overrides.0.b', max($a->id, $b->id))
        ->assertJsonPath('data.connection_overrides.0.type', 'forced');
});

it('returns an empty override list when there are none', function () {
    $building = Building::factory()->create();

    $this->getJson("/api/buildings/{$building->id}")
        ->assertOk()
        ->assertJsonPath('data.connection_overrides', []);
});
