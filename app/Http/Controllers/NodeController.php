<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreNodeRequest;
use App\Http\Requests\SyncNodesRequest;
use App\Http\Requests\UpdateNodeRequest;
use App\Models\Building;
use App\Models\Node;
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

        DB::transaction(function () use ($building, $nodes): void {
            $keptIds = [];

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
                    $keptIds[] = $node['id'];
                } else {
                    $keptIds[] = $building->nodes()->create($attributes)->id;
                }
            }

            // Remove only the nodes the editor deleted (not in the payload).
            // With an empty payload, $keptIds is empty and all nodes are removed.
            $building->nodes()
                ->when($keptIds !== [], fn ($query) => $query->whereKeyNot($keptIds))
                ->delete();
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
