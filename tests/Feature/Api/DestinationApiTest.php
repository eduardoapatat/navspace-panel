<?php

use App\Enums\ScheduleType;
use App\Models\Destination;
use Database\Seeders\DiciBuildingSeeder;

it('returns the destination detail shown by the Unity app', function () {
    $this->seed(DiciBuildingSeeder::class);
    $destination = Destination::where('display_name', 'Licancabur')->first();

    $response = $this->getJson("/api/destinations/{$destination->id}");

    $response->assertOk()
        ->assertJsonPath('data.display_name', 'Licancabur')
        ->assertJsonPath('data.space_type.name', 'Salas')
        ->assertJsonPath('data.node.position_z', 6.3784238389144);
});

it('returns the text schedule and responsible for an attention destination', function () {
    $destination = Destination::factory()->create([
        'type' => ScheduleType::Attention->value,
        'schedule_text' => 'Lun–Vie 08:00–13:00',
        'responsible_name' => 'Prof. Pérez',
        'responsible_phone' => '+56 9 1234 5678',
    ]);

    $response = $this->getJson("/api/destinations/{$destination->id}");

    $response->assertOk()
        ->assertJsonPath('data.type', 'attention')
        ->assertJsonPath('data.schedule_text', 'Lun–Vie 08:00–13:00')
        ->assertJsonPath('data.responsible.name', 'Prof. Pérez')
        ->assertJsonPath('data.responsible.phone', '+56 9 1234 5678');
});

it('returns the responsible photo url when one is set', function () {
    $destination = Destination::factory()->create([
        'type' => ScheduleType::Attention->value,
        'responsible_name' => 'Prof. Pérez',
        'responsible_photo' => 'responsibles/prof.jpg',
    ]);

    $this->getJson("/api/destinations/{$destination->id}")
        ->assertOk()
        ->assertJsonPath('data.responsible.photo', url('storage/responsibles/prof.jpg'));
});

it('returns the type for an academic destination', function () {
    $destination = Destination::factory()->create([
        'type' => ScheduleType::Academic->value,
    ]);

    $this->getJson("/api/destinations/{$destination->id}")
        ->assertOk()
        ->assertJsonPath('data.type', 'academic');
});

it('omits the responsible and text schedule for an academic destination', function () {
    $destination = Destination::factory()->create([
        'type' => ScheduleType::Academic->value,
        'schedule_file' => 'schedules/horario.png',
        'schedule_text' => 'no debe salir',
        'responsible_name' => 'No Debe Salir',
        'responsible_phone' => '+56 9 0000 0000',
    ]);

    $this->getJson("/api/destinations/{$destination->id}")
        ->assertOk()
        ->assertJsonPath('data.schedule_file', url('storage/schedules/horario.png'))
        ->assertJsonPath('data.schedule_text', null)
        ->assertJsonPath('data.responsible', null);
});

it('omits the node block when the destination has no node', function () {
    $destination = Destination::factory()->create(['node_id' => null]);

    $response = $this->getJson("/api/destinations/{$destination->id}");

    $response->assertOk()
        ->assertJsonMissingPath('data.node');
});

it('returns 404 for an unknown destination', function () {
    $this->getJson('/api/destinations/999')->assertNotFound();
});
