<?php

namespace Database\Factories;

use App\Enums\ScheduleType;
use App\Models\Building;
use App\Models\Destination;
use App\Models\SpaceType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Destination>
 */
class DestinationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * The destination hangs off a building directly, with a space type that
     * shares the same building. Grouping (node_group_id) and node are optional.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $building = Building::factory()->create();

        return [
            'building_id' => $building,
            'space_type_id' => SpaceType::factory()->for($building),
            'node_group_id' => null,
            'node_id' => null,
            'display_name' => fake()->words(2, true),
            'description' => fake()->sentence(),
            'type' => ScheduleType::Attention->value,
            'schedule_text' => 'Lunes a Viernes 10:00–19:00',
        ];
    }
}
