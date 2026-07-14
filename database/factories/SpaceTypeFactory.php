<?php

namespace Database\Factories;

use App\Models\Building;
use App\Models\SpaceType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SpaceType>
 */
class SpaceTypeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'building_id' => Building::factory(),
            'name' => fake()->randomElement(['Salas', 'Oficinas', 'Baños', 'Otros']),
            'icon' => null,
        ];
    }
}
