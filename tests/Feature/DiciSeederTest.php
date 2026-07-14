<?php

use App\Models\Building;
use App\Models\Destination;
use Database\Seeders\DiciBuildingSeeder;

it('seeds the DICI building with its group, space type and destinations', function () {
    $this->seed(DiciBuildingSeeder::class);

    $building = Building::where('name', 'Departamento Informática')->first();

    expect($building)->not->toBeNull()
        ->and($building->nodeGroups)->toHaveCount(4)
        ->and($building->spaceTypes->pluck('name'))->toContain('Salas')
        ->and(Destination::count())->toBe(9);
});

it('seeds Licancabur and Socompa with their real Unity positions', function () {
    $this->seed(DiciBuildingSeeder::class);

    $licancabur = Destination::where('display_name', 'Licancabur')->first();
    $socompa = Destination::where('display_name', 'Socompa')->first();

    expect($licancabur->node->position_x)->toBe(6.0865453111029)
        ->and($licancabur->node->position_z)->toBe(6.3784238389144)
        ->and($socompa->node->position_z)->toBe(7.7936628931091)
        ->and($licancabur->spaceType->name)->toBe('Salas');
});
