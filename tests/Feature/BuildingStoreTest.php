<?php

use App\Models\Building;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

it('renders the create form', function () {
    $this->get(route('buildings.create'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('buildings/Create'));
});

it('stores a building without a model', function () {
    $this->post(route('buildings.store'), [
        'name' => 'DICI',
        'address' => 'Arica',
    ])->assertRedirect(route('buildings.index'));

    $this->assertDatabaseHas('buildings', [
        'name' => 'DICI',
        'address' => 'Arica',
        'model_path' => null,
    ]);
});

it('stores a building with a .glb model and exposes its url', function () {
    Storage::fake('public');

    $this->post(route('buildings.store'), [
        'name' => 'DICI',
        'address' => 'Arica',
        'model' => UploadedFile::fake()->create('dici.glb', 100),
    ])->assertRedirect(route('buildings.index'));

    $building = Building::firstWhere('name', 'DICI');

    expect($building->model_url)->not->toBeNull();
    expect(Storage::disk('public')->allFiles('models'))->toHaveCount(1);
});

it('validates required fields', function () {
    $this->post(route('buildings.store'), [])
        ->assertSessionHasErrors(['name', 'address']);
});

it('rejects a non-glb upload', function () {
    Storage::fake('public');

    $this->post(route('buildings.store'), [
        'name' => 'DICI',
        'address' => 'Arica',
        'model' => UploadedFile::fake()->create('notes.pdf', 100),
    ])->assertSessionHasErrors('model');
});
