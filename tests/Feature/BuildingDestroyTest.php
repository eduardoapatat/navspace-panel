<?php

use App\Models\Building;
use App\Models\Destination;
use App\Models\NodeGroup;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

it('deletes the building and cascades to its groups and destinations', function () {
    $building = Building::factory()->create();
    $group = NodeGroup::factory()->for($building)->create();
    $destination = Destination::factory()->for($building)->create();

    $this->delete(route('buildings.destroy', $building))
        ->assertRedirect(route('buildings.index'));

    $this->assertDatabaseMissing('buildings', ['id' => $building->id]);
    $this->assertDatabaseMissing('node_groups', ['id' => $group->id]);
    $this->assertDatabaseMissing('destinations', ['id' => $destination->id]);
});

it('removes the stored .glb file when deleting the building', function () {
    Storage::fake('public');

    $path = UploadedFile::fake()->create('model.glb', 100)->store('models', 'public');
    $building = Building::factory()->create([
        'model_path' => $path,
    ]);

    $this->delete(route('buildings.destroy', $building))
        ->assertRedirect(route('buildings.index'));

    Storage::disk('public')->assertMissing($path);
});
