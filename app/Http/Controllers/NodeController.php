<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreNodeRequest;
use App\Http\Requests\SyncNodesRequest;
use App\Http\Requests\UpdateNodeRequest;
use App\Models\Building;
use App\Models\Node;
use App\Models\NodeConnectionOverride;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class NodeController extends Controller
{
    /**
     * Replace the building's whole set of nodes in one request (used by the
     * 3D editor's "Save" button, so edits stay local until confirmed).
     */
    public function sync(SyncNodesRequest $request, Building $building): RedirectResponse
    {
        $nodes = $request->validated('nodes');
        $overrides = $request->validated('connection_overrides');

        DB::transaction(function () use ($building, $nodes, $overrides): void {
            $keptIds = [];
            // Maps the editor's local_id (negative for brand new nodes) to the
            // real id, so connection overrides can be resolved further down.
            $idMap = [];

            foreach ($nodes as $node) {
                $isInitial = $node['is_initial'] ?? false;

                $attributes = [
                    'node_group_id' => $node['node_group_id'] ?? null,
                    'label' => $node['label'] ?? null,
                    'position_x' => $node['position_x'],
                    'position_y' => $node['position_y'],
                    'position_z' => $node['position_z'],
                    'is_initial' => $isInitial,
                    // Heading only applies to the initial node; clear it otherwise.
                    'heading' => $isInitial ? ($node['heading'] ?? null) : null,
                ];

                // Update the node in place when it already exists so destinations
                // keep their node_id; otherwise create a new one.
                if (! empty($node['id'])) {
                    $building->nodes()->whereKey($node['id'])->update($attributes);
                    $realId = (int) $node['id'];
                } else {
                    $realId = $building->nodes()->create($attributes)->id;
                }

                $keptIds[] = $realId;

                $localId = $node['local_id'] ?? $node['id'] ?? null;

                if ($localId !== null) {
                    $idMap[(int) $localId] = $realId;
                }
            }

            // Remove only the nodes the editor deleted (not in the payload).
            // With an empty payload, $keptIds is empty and all nodes are removed.
            // Their overrides go with them through the foreign key cascade.
            $building->nodes()
                ->when($keptIds !== [], fn ($query) => $query->whereKeyNot($keptIds))
                ->delete();

            if ($overrides !== null) {
                $this->replaceConnectionOverrides($building, $overrides, $idMap);
            }
        });

        $this->keepSingleInitialNode($building);

        return back();
    }

    /**
     * Store a new node on the given building.
     */
    public function store(StoreNodeRequest $request, Building $building): RedirectResponse
    {
        $node = $building->nodes()->create($request->validated());

        if ($node->is_initial) {
            $this->clearOtherInitialNodes($node);
        }

        return back();
    }

    /**
     * Update the given node's position or initial flag.
     */
    public function update(UpdateNodeRequest $request, Node $node): RedirectResponse
    {
        $node->update($request->validated());

        if ($node->is_initial) {
            $this->clearOtherInitialNodes($node);
        }

        return back();
    }

    /**
     * Delete the given node.
     */
    public function destroy(Node $node): RedirectResponse
    {
        $node->delete();

        return back();
    }

    /**
     * Replace the building's whole set of connection overrides, resolving the
     * editor's local ids into the real ones assigned above.
     *
     * Each pair is normalized (lowest id first) so A-B and B-A are the same
     * rule, and duplicates collapse into a single row.
     *
     * @param  array<int, array{a: int, b: int, type: string}>  $overrides
     * @param  array<int, int>  $idMap
     */
    private function replaceConnectionOverrides(Building $building, array $overrides, array $idMap): void
    {
        $building->connectionOverrides()->delete();

        $rows = [];

        foreach ($overrides as $override) {
            $a = $idMap[$override['a']] ?? null;
            $b = $idMap[$override['b']] ?? null;

            if ($a === null || $b === null || $a === $b) {
                continue;
            }

            $low = min($a, $b);
            $high = max($a, $b);

            // Keyed by the normalized pair, so a repeated pair keeps the last type.
            $rows["{$low}-{$high}"] = [
                'building_id' => $building->id,
                'node_a_id' => $low,
                'node_b_id' => $high,
                'type' => $override['type'],
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        if ($rows !== []) {
            NodeConnectionOverride::insert(array_values($rows));
        }
    }

    /**
     * Ensure only one initial (calibration) node exists per building by
     * unflagging every other node in the same building.
     */
    private function clearOtherInitialNodes(Node $node): void
    {
        Node::query()
            ->where('building_id', $node->building_id)
            ->whereKeyNot($node->id)
            ->where('is_initial', true)
            ->update(['is_initial' => false]);
    }

    /**
     * After a bulk sync, keep at most one initial node in the building by
     * demoting every other flagged node (the latest one wins).
     */
    private function keepSingleInitialNode(Building $building): void
    {
        $latestInitial = $building->nodes()
            ->where('is_initial', true)
            ->latest('id')
            ->first();

        if ($latestInitial !== null) {
            $this->clearOtherInitialNodes($latestInitial);
        }
    }
}
