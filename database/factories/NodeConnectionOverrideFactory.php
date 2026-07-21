<?php

namespace Database\Factories;

use App\Enums\ConnectionOverrideType;
use App\Models\Building;
use App\Models\Node;
use App\Models\NodeConnectionOverride;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<NodeConnectionOverride>
 */
class NodeConnectionOverrideFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * Both nodes are created under the same building, since a rule only makes
     * sense between two nodes of the same graph.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $building = Building::factory();

        return [
            'building_id' => $building,
            'node_a_id' => Node::factory()->for($building),
            'node_b_id' => Node::factory()->for($building),
            'type' => ConnectionOverrideType::Blocked->value,
        ];
    }

    /**
     * The pair must never be connected (a wall sits between them).
     */
    public function blocked(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => ConnectionOverrideType::Blocked->value,
        ]);
    }

    /**
     * The pair must always be connected (stairs between floors).
     */
    public function forced(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => ConnectionOverrideType::Forced->value,
        ]);
    }
}
