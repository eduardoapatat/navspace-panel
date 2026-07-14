<?php

use App\Models\Building;
use App\Models\NodeGroup;
use App\Models\User;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

it('stores a node group under a building', function () {
    $building = Building::factory()->create();

    $this->post(route('buildings.node-groups.store', $building), [
        'name' => 'Primer piso',
    ])->assertRedirect(route('buildings.nodes', $building));

    $this->assertDatabaseHas('node_groups', [
        'building_id' => $building->id,
        'name' => 'Primer piso',
    ]);
});

it('validates the node group fields', function () {
    $building = Building::factory()->create();

    $this->post(route('buildings.node-groups.store', $building), [])
        ->assertSessionHasErrors(['name']);
});

it('updates a node group', function () {
    $group = NodeGroup::factory()->create(['name' => 'Old']);

    $this->put(route('node-groups.update', $group), [
        'name' => 'Tercer piso',
    ])->assertRedirect(route('buildings.nodes', $group->building_id));

    expect($group->fresh()->name)->toBe('Tercer piso');
});

it('deletes a node group', function () {
    $group = NodeGroup::factory()->create();

    $this->delete(route('node-groups.destroy', $group))
        ->assertRedirect(route('buildings.nodes', $group->building_id));

    $this->assertDatabaseMissing('node_groups', ['id' => $group->id]);
});
