<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\BuildingDetailResource;
use App\Http\Resources\BuildingResource;
use App\Models\Building;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class BuildingController extends Controller
{
    /**
     * List every navigable space for the Unity selector.
     */
    public function index(): AnonymousResourceCollection
    {
        return BuildingResource::collection(Building::all());
    }

    /**
     * Return a single space with its model, nodes, groups and destinations.
     */
    public function show(Building $building): BuildingDetailResource
    {
        $building->load([
            'nodeGroups',
            'nodes',
            'connectionOverrides',
            'destinations.spaceType',
            'destinations.node',
        ]);

        return new BuildingDetailResource($building);
    }
}
