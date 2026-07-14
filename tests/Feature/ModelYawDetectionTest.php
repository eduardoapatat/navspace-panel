<?php

use App\Models\Building;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

/**
 * Builds a minimal valid binary .glb whose glTF `asset.generator` is the given
 * string, so the exporter-detection can be exercised without a real model file.
 */
function fakeGlb(string $generator): string
{
    $json = json_encode([
        'asset' => ['generator' => $generator, 'version' => '2.0'],
        'scenes' => [], 'nodes' => [],
    ]);
    while (strlen($json) % 4 !== 0) {
        $json .= ' ';
    }

    $jsonChunk = pack('V', strlen($json)).'JSON'.$json;
    $total = 12 + strlen($jsonChunk);

    return 'glTF'.pack('V', 2).pack('V', $total).$jsonChunk;
}

it('auto-detects a 180° yaw correction for Blender-exported models', function () {
    Storage::fake('public');
    $building = Building::factory()->create(['model_yaw' => 0]);

    $this->put(route('buildings.update', $building), [
        'name' => $building->name,
        'address' => $building->address,
        'model' => UploadedFile::fake()->createWithContent('m.glb', fakeGlb('Khronos glTF Blender I/O v5.1.20')),
    ])->assertRedirect();

    expect($building->fresh()->model_yaw)->toBe(180);
});

it('uses no yaw correction for non-Blender models', function () {
    Storage::fake('public');
    $building = Building::factory()->create(['model_yaw' => 180]);

    $this->put(route('buildings.update', $building), [
        'name' => $building->name,
        'address' => $building->address,
        'model' => UploadedFile::fake()->createWithContent('m.glb', fakeGlb('Sketchfab-13.12.0')),
    ])->assertRedirect();

    expect($building->fresh()->model_yaw)->toBe(0);
});

it('accepts a manual yaw override when no new model is uploaded', function () {
    $building = Building::factory()->create(['model_yaw' => 0, 'model_path' => 'models/x.glb']);

    $this->put(route('buildings.update', $building), [
        'name' => $building->name,
        'address' => $building->address,
        'model_yaw' => 90,
    ])->assertRedirect();

    expect($building->fresh()->model_yaw)->toBe(90);
});

it('exposes the model yaw in the building API detail', function () {
    $building = Building::factory()->create(['model_yaw' => 180]);

    $this->getJson("/api/buildings/{$building->id}")
        ->assertOk()
        ->assertJsonPath('data.model_yaw', 180);
});
