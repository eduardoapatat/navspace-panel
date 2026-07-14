<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreNodeGroupRequest;
use App\Http\Requests\UpdateNodeGroupRequest;
use App\Models\Building;
use App\Models\NodeGroup;
use Illuminate\Http\RedirectResponse;

class NodeGroupController extends Controller
{
    /**
     * Store a new node group under the given building.
     */
    public function store(StoreNodeGroupRequest $request, Building $building): RedirectResponse
    {
        $building->nodeGroups()->create($request->validated());

        return to_route('buildings.nodes', $building);
    }

    /**
     * Update the given node group.
     */
    public function update(UpdateNodeGroupRequest $request, NodeGroup $nodeGroup): RedirectResponse
    {
        $nodeGroup->update($request->validated());

        return to_route('buildings.nodes', $nodeGroup->building_id);
    }

    /**
     * Delete the given node group. Its nodes and destinations are kept and
     * simply become ungrouped (node_group_id set to null).
     */
    public function destroy(NodeGroup $nodeGroup): RedirectResponse
    {
        $buildingId = $nodeGroup->building_id;

        $nodeGroup->delete();

        return to_route('buildings.nodes', $buildingId);
    }
}
