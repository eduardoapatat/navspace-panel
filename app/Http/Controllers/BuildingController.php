<?php

namespace App\Http\Controllers;

use App\Enums\ScheduleType;
use App\Http\Requests\BuildingIndexRequest;
use App\Http\Requests\StoreBuildingRequest;
use App\Http\Requests\UpdateBuildingRequest;
use App\Http\Requests\UpdatePathfindingConfigRequest;
use App\Models\Building;
use App\Support\GlbInspector;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class BuildingController extends Controller
{
    /**
     * Display a paginated, searchable and sortable listing of buildings.
     */
    public function index(BuildingIndexRequest $request): Response
    {
        $buildings = Building::query()
            ->withFilters($request->validated())
            ->withSorting($request->sort(), $request->direction())
            ->paginate($request->perPage(), ['*'], 'page', $request->page())
            ->withQueryString();

        return Inertia::render('buildings/Index', [
            'buildings' => $buildings,
            'filters' => $request->only(['search']),
        ]);
    }

    /**
     * Show the 3D node editor for the building, loading its nodes and groups.
     */
    public function editNodes(Building $building): Response
    {
        $building->load([
            'nodeGroups' => fn ($query) => $query->orderBy('name'),
            'nodes' => fn ($query) => $query->orderBy('id'),
        ]);

        return Inertia::render('buildings/nodes/Editor', [
            'building' => $building,
        ]);
    }

    /**
     * Save the A* Point Graph parameters used by the Unity app for this building.
     */
    public function updatePathfinding(UpdatePathfindingConfigRequest $request, Building $building): RedirectResponse
    {
        $building->update(['pathfinding_config' => $request->validated()]);

        return back();
    }

    /**
     * Show the form for creating a new building.
     */
    public function create(): Response
    {
        return Inertia::render('buildings/Create');
    }

    /**
     * Store a newly created building, optionally with its .glb model.
     */
    public function store(StoreBuildingRequest $request): RedirectResponse
    {
        $data = $request->safe()->only(['name', 'address']);

        if ($request->hasFile('image')) {
            $data['image_path'] = $request->file('image')->store('buildings', 'public');
        }

        if ($request->hasFile('model')) {
            $data['model_path'] = $request->file('model')->store('models', 'public');
            // Auto-detect the exporter to fix Unity's 180° flip of Blender glb.
            $data['model_yaw'] = GlbInspector::detectModelYaw($data['model_path']);
        }

        Building::create($data);

        return to_route('buildings.index');
    }

    /**
     * Show the full management page for the building: its editable properties,
     * space types and destinations (node groups are handled in the 3D editor).
     */
    public function edit(Building $building): Response
    {
        $building->load([
            'nodeGroups' => fn ($query) => $query->orderBy('name'),
            'nodes' => fn ($query) => $query->orderBy('id'),
            'spaceTypes' => fn ($query) => $query->orderBy('name'),
            'destinations' => fn ($query) => $query
                ->with(['spaceType', 'node'])
                ->orderBy('display_name'),
        ]);

        return Inertia::render('buildings/Edit', [
            'building' => $building,
            'scheduleTypeOptions' => ScheduleType::options(),
        ]);
    }

    /**
     * Update the given building, replacing its .glb model when a new one is sent.
     */
    public function update(UpdateBuildingRequest $request, Building $building): RedirectResponse
    {
        // model_yaw here is the manual override; a fresh model upload below
        // re-detects it from the exporter and wins over the submitted value.
        $data = $request->safe()->only(['name', 'address', 'model_yaw']);

        // A new file replaces the old one; the "remove_*" flag clears it. Removing
        // the .glb never deletes the building's nodes: they are independent rows,
        // only their coordinates become meaningless if a different model is loaded.
        if ($request->hasFile('image')) {
            $this->deleteStoredFile($building->image_path);
            $data['image_path'] = $request->file('image')->store('buildings', 'public');
        } elseif ($request->boolean('remove_image')) {
            $this->deleteStoredFile($building->image_path);
            $data['image_path'] = null;
        }

        if ($request->hasFile('model')) {
            $this->deleteStoredFile($building->model_path);
            $data['model_path'] = $request->file('model')->store('models', 'public');
            // Auto-detect the exporter to fix Unity's 180° flip of Blender glb.
            $data['model_yaw'] = GlbInspector::detectModelYaw($data['model_path']);
        } elseif ($request->boolean('remove_model')) {
            $this->deleteStoredFile($building->model_path);
            $data['model_path'] = null;
            $data['model_yaw'] = 0;
        }

        $building->update($data);

        return to_route('buildings.index');
    }

    /**
     * Delete the building along with its stored image and .glb model. Node
     * groups, space types, destinations and schedules are removed by cascade.
     */
    public function destroy(Building $building): RedirectResponse
    {
        $this->deleteStoredFile($building->image_path);
        $this->deleteStoredFile($building->model_path);

        $building->delete();

        return to_route('buildings.index');
    }

    /**
     * Remove a stored public-disk file given its relative path, if any.
     */
    private function deleteStoredFile(?string $path): void
    {
        if ($path === null || $path === '') {
            return;
        }

        Storage::disk('public')->delete($path);
    }
}
