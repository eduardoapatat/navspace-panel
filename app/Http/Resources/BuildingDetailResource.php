<?php

namespace App\Http\Resources;

use App\Models\Building;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Full building payload the Unity app downloads when a space is selected:
 * the model, its navigation nodes and the destinations to navigate to.
 *
 * Nodes hang off the building directly (a flat list is exactly what Unity
 * needs to build the A* graph); node groups are only optional labels.
 *
 * @mixin Building
 */
class BuildingDetailResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'address' => $this->address,
            'image_url' => $this->image_url,
            'model_url' => $this->model_url,
            // Yaw correction (degrees) Unity applies to the loaded model, to fix
            // the 180° flip of Blender-exported glb. 0 for other exporters.
            'model_yaw' => $this->model_yaw,
            'pathfinding_config' => $this->pathfinding_config ?? self::defaultPathfindingConfig(),
            // Optional grouping labels (e.g. "Piso 1"). Purely organizational.
            'node_groups' => $this->nodeGroups->map(fn ($group): array => [
                'id' => $group->id,
                'name' => $group->name,
            ]),
            // All navigation nodes, so Unity can build the full A* graph
            // (paths, not just destinations) and find the initial node.
            'nodes' => $this->nodes->map(fn ($node): array => [
                'id' => $node->id,
                'node_group_id' => $node->node_group_id,
                'label' => $node->label,
                'position_x' => $node->position_x,
                'position_y' => $node->position_y,
                'position_z' => $node->position_z,
                'is_initial' => (bool) $node->is_initial,
                // Facing direction; only set on the initial node (null otherwise).
                'heading' => $node->heading,
            ]),
            // Manual exceptions Unity applies after building the graph: 'blocked'
            // pairs are disconnected (a wall sits between them), 'forced' pairs
            // are linked even when distance or height would rule them out (stairs).
            'connection_overrides' => $this->connectionOverrides->map(fn ($override): array => [
                'a' => $override->node_a_id,
                'b' => $override->node_b_id,
                'type' => $override->type->value,
            ]),
            'destinations' => DestinationResource::collection($this->whenLoaded('destinations')),
        ];
    }

    /**
     * Fallback A* parameters so Unity always receives a usable config.
     *
     * @return array{max_distance: float, max_y_distance: float, thick_radius: float, check_walls: bool}
     */
    private static function defaultPathfindingConfig(): array
    {
        return [
            'max_distance' => 5.0,
            // Vertical limit so nodes on different floors never connect. 0 = off.
            'max_y_distance' => 1.0,
            'thick_radius' => 0.0,
            // Wall raycast disabled by default: it caused errors and is not used.
            'check_walls' => false,
        ];
    }
}
