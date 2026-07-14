<?php

namespace Database\Factories;

use App\Models\Building;
use App\Models\Node;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Node>
 */
class NodeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * The node hangs off a building directly; grouping is optional so
     * node_group_id defaults to null.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'building_id' => Building::factory(),
            'node_group_id' => null,
            'label' => null,
            'position_x' => fake()->randomFloat(3, -20, 20),
            'position_y' => 0,
            'position_z' => fake()->randomFloat(3, -20, 20),
            'is_initial' => false,
        ];
    }

    /**
     * Mark the node as the building's calibration anchor.
     */
    public function initial(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_initial' => true,
        ]);
    }
}
