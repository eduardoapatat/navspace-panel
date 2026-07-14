<?php

use App\Models\Building;
use App\Models\Destination;
use Database\Seeders\DiciSeeder;

it('seeds the DICI building with its group, space type and destinations', function () {
    $this->seed(DiciSeeder::class);

    $building = Building::where('name', 'DICI')->first();

    expect($building)->not->toBeNull()
        ->and($building->nodeGroups)->toHaveCount(1)
        ->and($building->spaceTypes->pluck('name'))->toContain('Salas')
        ->and(Destination::count())->toBe(2);
});

it('seeds Licancabur and Socompa with their real Unity positions', function () {
    $this->seed(DiciSeeder::class);

    $licancabur = Destination::where('display_name', 'Licancabur')->first();
    $socompa = Destination::where('display_name', 'Socompa')->first();

    expect($licancabur->node->position_x)->toBe(-6.54)
        ->and($licancabur->node->position_z)->toBe(6.155)
        ->and($socompa->node->position_z)->toBe(7.609)
        ->and($licancabur->spaceType->name)->toBe('Salas');
});
