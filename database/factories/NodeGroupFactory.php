<?php

namespace Database\Factories;

use App\Models\Building;
use App\Models\NodeGroup;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<NodeGroup>
 */
class NodeGroupFactory extends Factory
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
            'name' => 'Piso '.fake()->numberBetween(1, 5),
        ];
    }
}
