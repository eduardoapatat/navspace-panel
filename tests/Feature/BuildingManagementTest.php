<?php

use App\Models\Building;
use App\Models\Destination;
use App\Models\SpaceType;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

it('renders the management page with the building properties', function () {
    $building = Building::factory()->create(['name' => 'DICI']);

    $this->get(route('buildings.edit', $building))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('buildings/Edit')
            ->where('building.name', 'DICI')
        );
});

it('includes the building space types and destinations', function () {
    $building = Building::factory()->create();
    SpaceType::factory()->for($building)->create(['name' => 'Salas']);
    Destination::factory()->for($building)->create(['display_name' => 'Licancabur']);

    $this->get(route('buildings.edit', $building))
        ->assertInertia(fn (Assert $page) => $page
            ->has('building.space_types', 1)
            ->where('building.space_types.0.name', 'Salas')
            ->has('building.destinations', 1)
            ->where('building.destinations.0.display_name', 'Licancabur')
            ->has('scheduleTypeOptions')
        );
});
