<?php

use App\Models\Building;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

test('guests are redirected to login', function () {
    auth()->logout();

    $this->get(route('buildings.index'))->assertRedirect(route('login'));
});

it('renders the buildings index page with paginated data', function () {
    Building::factory()->count(3)->create();

    $this->get(route('buildings.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('buildings/Index')
            ->has('buildings.data', 3)
        );
});

it('filters buildings by the search term', function () {
    Building::factory()->create(['name' => 'DICI']);
    Building::factory()->create(['name' => 'Facultad de Ciencias']);

    $this->get(route('buildings.index', ['search' => 'DICI']))
        ->assertInertia(fn (Assert $page) => $page
            ->has('buildings.data', 1)
            ->where('buildings.data.0.name', 'DICI')
        );
});

it('sorts buildings by an allowed column', function () {
    Building::factory()->create(['name' => 'Zeta']);
    Building::factory()->create(['name' => 'Alfa']);

    $this->get(route('buildings.index', ['sort' => 'name', 'direction' => 'asc']))
        ->assertInertia(fn (Assert $page) => $page
            ->where('buildings.data.0.name', 'Alfa')
        );
});
