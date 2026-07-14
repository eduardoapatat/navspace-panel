<?php

use App\Models\Building;
use App\Models\Node;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

it('renders the edit form with the building data', function () {
    $building = Building::factory()->create(['name' => 'DICI']);

    $this->get(route('buildings.edit', $building))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('buildings/Edit')
            ->where('building.name', 'DICI')
        );
});

it('updates the name and address', function () {
    $building = Building::factory()->create(['name' => 'Old', 'address' => 'Old']);

    $this->put(route('buildings.update', $building), [
        'name' => 'DICI',
        'address' => 'Arica',
    ])->assertRedirect(route('buildings.index'));

    expect($building->fresh()->name)->toBe('DICI')
        ->and($building->fresh()->address)->toBe('Arica');
});

it('replaces the .glb model and deletes the previous file', function () {
    Storage::fake('public');

    $building = Building::factory()->create();
    $oldPath = UploadedFile::fake()->create('old.glb', 100)->store('models', 'public');
    $building->update(['model_path' => $oldPath]);

    $this->put(route('buildings.update', $building), [
        'name' => $building->name,
        'address' => $building->address,
        'model' => UploadedFile::fake()->create('new.glb', 100),
    ])->assertRedirect(route('buildings.index'));

    Storage::disk('public')->assertMissing($oldPath);
    expect(Storage::disk('public')->allFiles('models'))->toHaveCount(1);
    expect($building->fresh()->model_path)->not->toBe($oldPath);
});

it('removes the .glb model without deleting the building nodes', function () {
    Storage::fake('public');

    $building = Building::factory()->create();
    $path = UploadedFile::fake()->create('model.glb', 100)->store('models', 'public');
    $building->update(['model_path' => $path]);
    $node = Node::factory()->for($building)->create();

    $this->put(route('buildings.update', $building), [
        'name' => $building->name,
        'address' => $building->address,
        'remove_model' => '1',
    ])->assertRedirect(route('buildings.index'));

    Storage::disk('public')->assertMissing($path);
    expect($building->fresh()->model_path)->toBeNull();
    // The nodes survive: they are independent rows, not tied to the model file.
    $this->assertDatabaseHas('nodes', ['id' => $node->id]);
});

it('removes the building photo', function () {
    Storage::fake('public');

    $building = Building::factory()->create();
    $path = UploadedFile::fake()->image('photo.jpg')->store('buildings', 'public');
    $building->update(['image_path' => $path]);

    $this->put(route('buildings.update', $building), [
        'name' => $building->name,
        'address' => $building->address,
        'remove_image' => '1',
    ])->assertRedirect(route('buildings.index'));

    Storage::disk('public')->assertMissing($path);
    expect($building->fresh()->image_path)->toBeNull();
});

it('keeps the existing model when no new file is sent', function () {
    Storage::fake('public');

    $building = Building::factory()->create([
        'model_path' => 'models/keep.glb',
    ]);

    $this->put(route('buildings.update', $building), [
        'name' => 'DICI',
        'address' => 'Arica',
    ])->assertRedirect(route('buildings.index'));

    expect($building->fresh()->model_path)->toBe('models/keep.glb')
        ->and($building->fresh()->model_url)->toBe(url('storage/models/keep.glb'));
});
