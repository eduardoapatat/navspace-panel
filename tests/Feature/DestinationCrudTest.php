<?php

use App\Models\Building;
use App\Models\Destination;
use App\Models\Node;
use App\Models\SpaceType;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

it('stores a destination under a building without a node', function () {
    $building = Building::factory()->create();
    $spaceType = SpaceType::factory()->for($building)->create();

    $this->post(route('buildings.destinations.store', $building), [
        'space_type_id' => $spaceType->id,
        'display_name' => 'Licancabur',
        'type' => 'attention',
    ])->assertRedirect(route('buildings.edit', $building));

    $this->assertDatabaseHas('destinations', [
        'building_id' => $building->id,
        'space_type_id' => $spaceType->id,
        'display_name' => 'Licancabur',
        'node_id' => null,
    ]);
});

it('validates required fields', function () {
    $building = Building::factory()->create();

    $this->post(route('buildings.destinations.store', $building), [])
        ->assertSessionHasErrors(['space_type_id', 'display_name']);
});

it('rejects a space type from another building', function () {
    $building = Building::factory()->create();
    $otherSpaceType = SpaceType::factory()->create(); // different building

    $this->post(route('buildings.destinations.store', $building), [
        'space_type_id' => $otherSpaceType->id,
        'display_name' => 'Intruso',
        'type' => 'attention',
    ])->assertSessionHasErrors('space_type_id');
});

it('updates a destination', function () {
    $destination = Destination::factory()->create(['display_name' => 'Old']);
    $building = $destination->building;
    $spaceType = $building->spaceTypes()->create(['name' => 'Salas']);

    $this->put(route('destinations.update', $destination), [
        'space_type_id' => $spaceType->id,
        'display_name' => 'Socompa',
        'type' => 'attention',
    ])->assertRedirect(route('buildings.edit', $building->id));

    expect($destination->fresh()->display_name)->toBe('Socompa');
});

it('assigns a node from the same building to a destination', function () {
    $destination = Destination::factory()->create();
    $building = $destination->building;
    $node = Node::factory()->for($building)->create();

    $this->put(route('destinations.update', $destination), [
        'space_type_id' => $destination->space_type_id,
        'display_name' => $destination->display_name,
        'type' => $destination->type->value,
        'node_id' => $node->id,
    ])->assertRedirect();

    expect($destination->fresh()->node_id)->toBe($node->id);
});

it('rejects a node from another building', function () {
    $destination = Destination::factory()->create();
    $otherNode = Node::factory()->create(); // node on a different building

    $this->put(route('destinations.update', $destination), [
        'space_type_id' => $destination->space_type_id,
        'display_name' => $destination->display_name,
        'type' => $destination->type->value,
        'node_id' => $otherNode->id,
    ])->assertSessionHasErrors('node_id');
});

it('rejects assigning the initial node to a destination', function () {
    $destination = Destination::factory()->create();
    $building = $destination->building;
    $initial = Node::factory()->for($building)->create(['is_initial' => true]);

    $this->put(route('destinations.update', $destination), [
        'space_type_id' => $destination->space_type_id,
        'display_name' => $destination->display_name,
        'type' => $destination->type->value,
        'node_id' => $initial->id,
    ])->assertSessionHasErrors('node_id');

    expect($destination->fresh()->node_id)->toBeNull();
});

it('stores a responsible photo for an attention destination', function () {
    Storage::fake('public');
    $building = Building::factory()->create();
    $spaceType = SpaceType::factory()->for($building)->create();

    $this->post(route('buildings.destinations.store', $building), [
        'space_type_id' => $spaceType->id,
        'display_name' => 'Oficina 1',
        'type' => 'attention',
        'responsible_name' => 'Prof. Pérez',
        'responsible_photo' => UploadedFile::fake()->image('prof.jpg', 400, 300),
    ])->assertRedirect(route('buildings.edit', $building));

    $destination = Destination::first();
    expect($destination->responsible_photo)->not->toBeNull();
    Storage::disk('public')->assertExists($destination->responsible_photo);
});

it('replaces the responsible photo and deletes the old one on update', function () {
    Storage::fake('public');
    $destination = Destination::factory()->create([
        'responsible_photo' => UploadedFile::fake()->image('old.jpg', 400, 300)->store('responsibles', 'public'),
    ]);
    $oldPath = $destination->responsible_photo;

    $this->put(route('destinations.update', $destination), [
        'space_type_id' => $destination->space_type_id,
        'display_name' => $destination->display_name,
        'type' => $destination->type->value,
        'responsible_photo' => UploadedFile::fake()->image('new.jpg', 400, 300),
    ])->assertRedirect();

    $newPath = $destination->fresh()->responsible_photo;
    expect($newPath)->not->toBe($oldPath);
    Storage::disk('public')->assertExists($newPath);
    Storage::disk('public')->assertMissing($oldPath);
});

it('removes the responsible photo when the flag is sent', function () {
    Storage::fake('public');
    $destination = Destination::factory()->create([
        'responsible_photo' => UploadedFile::fake()->image('prof.jpg', 300, 400)->store('responsibles', 'public'),
    ]);
    $path = $destination->responsible_photo;

    $this->put(route('destinations.update', $destination), [
        'space_type_id' => $destination->space_type_id,
        'display_name' => $destination->display_name,
        'type' => $destination->type->value,
        'remove_responsible_photo' => '1',
    ])->assertRedirect();

    Storage::disk('public')->assertMissing($path);
    expect($destination->fresh()->responsible_photo)->toBeNull();
});

it('removes the schedule PDF when the flag is sent', function () {
    Storage::fake('public');
    $destination = Destination::factory()->create([
        'type' => 'academic',
        'schedule_file' => UploadedFile::fake()->create('horario.pdf', 100)->store('schedules', 'public'),
    ]);
    $path = $destination->schedule_file;

    $this->put(route('destinations.update', $destination), [
        'space_type_id' => $destination->space_type_id,
        'display_name' => $destination->display_name,
        'type' => 'academic',
        'remove_schedule_file' => '1',
    ])->assertRedirect();

    Storage::disk('public')->assertMissing($path);
    expect($destination->fresh()->schedule_file)->toBeNull();
});

it('clears the responsible and text schedule when switched to academic', function () {
    Storage::fake('public');
    $destination = Destination::factory()->create([
        'type' => 'attention',
        'schedule_text' => 'Lun–Vie 09:00–17:00',
        'responsible_name' => 'Prof. Pérez',
        'responsible_phone' => '+56 9 1234 5678',
        'responsible_email' => 'perez@uta.cl',
        'responsible_photo' => UploadedFile::fake()->image('prof.jpg', 300, 400)->store('responsibles', 'public'),
    ]);
    $photo = $destination->responsible_photo;

    $this->put(route('destinations.update', $destination), [
        'space_type_id' => $destination->space_type_id,
        'display_name' => $destination->display_name,
        'type' => 'academic',
    ])->assertRedirect();

    $fresh = $destination->fresh();
    expect($fresh->type->value)->toBe('academic');
    expect($fresh->schedule_text)->toBeNull();
    expect($fresh->responsible_name)->toBeNull();
    expect($fresh->responsible_phone)->toBeNull();
    expect($fresh->responsible_email)->toBeNull();
    expect($fresh->responsible_photo)->toBeNull();
    Storage::disk('public')->assertMissing($photo);
});

it('clears the schedule file when switched to attention', function () {
    Storage::fake('public');
    $destination = Destination::factory()->create([
        'type' => 'academic',
        'schedule_file' => UploadedFile::fake()->image('horario.png', 800, 400)->store('schedules', 'public'),
    ]);
    $file = $destination->schedule_file;

    $this->put(route('destinations.update', $destination), [
        'space_type_id' => $destination->space_type_id,
        'display_name' => $destination->display_name,
        'type' => 'attention',
        'schedule_text' => 'Lun–Vie 09:00–17:00',
        'responsible_name' => 'Prof. Pérez',
    ])->assertRedirect();

    $fresh = $destination->fresh();
    expect($fresh->type->value)->toBe('attention');
    expect($fresh->schedule_file)->toBeNull();
    expect($fresh->schedule_text)->toBe('Lun–Vie 09:00–17:00');
    expect($fresh->responsible_name)->toBe('Prof. Pérez');
    Storage::disk('public')->assertMissing($file);
});

it('deletes the responsible photo when the destination is deleted', function () {
    Storage::fake('public');
    $destination = Destination::factory()->create([
        'responsible_photo' => UploadedFile::fake()->image('prof.jpg', 400, 300)->store('responsibles', 'public'),
    ]);
    $path = $destination->responsible_photo;

    $this->delete(route('destinations.destroy', $destination))->assertRedirect();

    Storage::disk('public')->assertMissing($path);
});

it('deletes a destination', function () {
    $destination = Destination::factory()->create();

    $this->delete(route('destinations.destroy', $destination))
        ->assertRedirect(route('buildings.edit', $destination->building_id));

    $this->assertDatabaseMissing('destinations', ['id' => $destination->id]);
});
