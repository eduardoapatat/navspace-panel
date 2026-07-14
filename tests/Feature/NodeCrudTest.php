<?php

use App\Models\Building;
use App\Models\Node;
use App\Models\User;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

it('stores a node on a building', function () {
    $building = Building::factory()->create();

    $this->post(route('buildings.nodes.store', $building), [
        'position_x' => -6.54,
        'position_y' => 0,
        'position_z' => 6.155,
    ])->assertRedirect();

    $this->assertDatabaseHas('nodes', [
        'building_id' => $building->id,
        'position_z' => 6.155,
    ]);
});

it('requires numeric positions', function () {
    $building = Building::factory()->create();

    $this->post(route('buildings.nodes.store', $building), [
        'position_x' => 'left',
        'position_y' => 0,
        'position_z' => 0,
    ])->assertSessionHasErrors('position_x');
});

it('keeps only one initial node per building when creating', function () {
    $building = Building::factory()->create();
    $previousInitial = Node::factory()->for($building)->initial()->create();

    $this->post(route('buildings.nodes.store', $building), [
        'position_x' => 0,
        'position_y' => 0,
        'position_z' => 0,
        'is_initial' => true,
    ])->assertRedirect();

    expect($previousInitial->fresh()->is_initial)->toBeFalse();
    expect(Node::where('is_initial', true)->count())->toBe(1);
});

it('does not touch initial nodes of other buildings', function () {
    $otherBuilding = Building::factory()->create();
    $otherInitial = Node::factory()->for($otherBuilding)->initial()->create();

    $building = Building::factory()->create();

    $this->post(route('buildings.nodes.store', $building), [
        'position_x' => 0,
        'position_y' => 0,
        'position_z' => 0,
        'is_initial' => true,
    ])->assertRedirect();

    expect($otherInitial->fresh()->is_initial)->toBeTrue();
});

it('updates a node position', function () {
    $node = Node::factory()->create();

    $this->put(route('nodes.update', $node), [
        'position_x' => 1.5,
    ])->assertRedirect();

    expect($node->fresh()->position_x)->toBe(1.5);
});

it('promotes a node to initial and demotes the previous one', function () {
    $building = Building::factory()->create();
    $oldInitial = Node::factory()->for($building)->initial()->create();
    $node = Node::factory()->for($building)->create();

    $this->put(route('nodes.update', $node), [
        'is_initial' => true,
    ])->assertRedirect();

    expect($node->fresh()->is_initial)->toBeTrue();
    expect($oldInitial->fresh()->is_initial)->toBeFalse();
});

it('deletes a node', function () {
    $node = Node::factory()->create();

    $this->delete(route('nodes.destroy', $node))->assertRedirect();

    $this->assertDatabaseMissing('nodes', ['id' => $node->id]);
});

it('syncs the whole set of nodes for a building', function () {
    $building = Building::factory()->create();
    $stale = Node::factory()->for($building)->create();

    $this->put(route('buildings.nodes.sync', $building), [
        'nodes' => [
            ['label' => 'Entrada', 'position_x' => 1, 'position_y' => 0, 'position_z' => 2, 'is_initial' => true],
            ['position_x' => 3, 'position_y' => 0, 'position_z' => 4, 'is_initial' => false],
        ],
    ])->assertRedirect();

    // Nodes sent without an id are new; the stale one (absent from the payload) is removed.
    $this->assertDatabaseMissing('nodes', ['id' => $stale->id]);
    expect($building->nodes()->count())->toBe(2);
    expect($building->nodes()->where('is_initial', true)->count())->toBe(1);
    $this->assertDatabaseHas('nodes', ['building_id' => $building->id, 'label' => 'Entrada']);
});

it('saves heading on the initial node and clears it on the rest', function () {
    $building = Building::factory()->create();

    $this->put(route('buildings.nodes.sync', $building), [
        'nodes' => [
            ['position_x' => 1, 'position_y' => 0, 'position_z' => 2, 'is_initial' => true, 'heading' => 90],
            ['position_x' => 3, 'position_y' => 0, 'position_z' => 4, 'is_initial' => false, 'heading' => 200],
        ],
    ])->assertRedirect();

    // Heading is kept only for the initial node; ignored for regular ones.
    expect($building->nodes()->where('is_initial', true)->first()->heading)->toBe(90.0);
    expect($building->nodes()->where('is_initial', false)->first()->heading)->toBeNull();
});

it('exposes the initial node heading in the building API detail', function () {
    $building = Building::factory()->create();
    Node::factory()->for($building)->create(['is_initial' => true, 'heading' => 135]);

    $this->getJson("/api/buildings/{$building->id}")
        ->assertOk()
        ->assertJsonPath('data.nodes.0.heading', fn ($v) => (float) $v === 135.0);
});

it('validates the node heading range on sync', function () {
    $building = Building::factory()->create();

    $this->put(route('buildings.nodes.sync', $building), [
        'nodes' => [
            ['position_x' => 1, 'position_y' => 0, 'position_z' => 2, 'is_initial' => true, 'heading' => 999],
        ],
    ])->assertSessionHasErrors('nodes.0.heading');
});

it('updates existing nodes in place on sync, preserving their id', function () {
    $building = Building::factory()->create();
    $existing = Node::factory()->for($building)->create(['label' => 'Viejo', 'position_x' => 0]);

    $this->put(route('buildings.nodes.sync', $building), [
        'nodes' => [
            ['id' => $existing->id, 'label' => 'Nuevo', 'position_x' => 9, 'position_y' => 0, 'position_z' => 0, 'is_initial' => false],
        ],
    ])->assertRedirect();

    // Same id kept (so destinations that reference it are not orphaned).
    $this->assertDatabaseHas('nodes', ['id' => $existing->id, 'label' => 'Nuevo', 'position_x' => 9]);
    expect($building->nodes()->count())->toBe(1);
});

it('keeps a single initial node across the building when syncing', function () {
    $building = Building::factory()->create();
    Node::factory()->for($building)->initial()->create();

    $this->put(route('buildings.nodes.sync', $building), [
        'nodes' => [
            ['position_x' => 0, 'position_y' => 0, 'position_z' => 0, 'is_initial' => true],
        ],
    ])->assertRedirect();

    expect(Node::where('is_initial', true)->count())->toBe(1);
});

it('can clear all nodes of a building by syncing an empty set', function () {
    $building = Building::factory()->create();
    Node::factory()->for($building)->count(3)->create();

    $this->put(route('buildings.nodes.sync', $building), [
        'nodes' => [],
    ])->assertRedirect();

    expect($building->nodes()->count())->toBe(0);
});
