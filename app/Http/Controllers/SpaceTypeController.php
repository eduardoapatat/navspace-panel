<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSpaceTypeRequest;
use App\Http\Requests\UpdateSpaceTypeRequest;
use App\Models\Building;
use App\Models\SpaceType;
use Illuminate\Http\RedirectResponse;

class SpaceTypeController extends Controller
{
    /**
     * Store a new space type under the given building.
     */
    public function store(StoreSpaceTypeRequest $request, Building $building): RedirectResponse
    {
        $building->spaceTypes()->create($request->validated());

        return to_route('buildings.edit', $building);
    }

    /**
     * Update the given space type.
     */
    public function update(UpdateSpaceTypeRequest $request, SpaceType $spaceType): RedirectResponse
    {
        $spaceType->update($request->validated());

        return to_route('buildings.edit', $spaceType->building_id);
    }

    /**
     * Delete the given space type.
     */
    public function destroy(SpaceType $spaceType): RedirectResponse
    {
        $buildingId = $spaceType->building_id;

        $spaceType->delete();

        return to_route('buildings.edit', $buildingId);
    }
}
