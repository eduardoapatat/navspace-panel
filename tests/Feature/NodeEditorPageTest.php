<?php

use App\Models\Building;
use App\Models\Node;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

it('renders the node editor with the building nodes', function () {
    $building = Building::factory()->create([
        'model_path' => 'models/dici.glb',
    ]);
    Node::factory()->for($building)->create(['position_z' => 6.155]);

    $this->get(route('buildings.nodes', $building))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('buildings/nodes/Editor')
            ->where('building.model_url', url('storage/models/dici.glb'))
            ->has('building.nodes', 1)
            ->where('building.nodes.0.position_z', 6.155)
        );
});
