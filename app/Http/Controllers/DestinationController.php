<?php

namespace App\Http\Controllers;

use App\Enums\ScheduleType;
use App\Http\Requests\StoreDestinationRequest;
use App\Http\Requests\UpdateDestinationRequest;
use App\Models\Building;
use App\Models\Destination;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;

class DestinationController extends Controller
{
    /**
     * Store a new destination under the given building.
     */
    public function store(StoreDestinationRequest $request, Building $building): RedirectResponse
    {
        $data = $request->validated();
        $data = $this->handleUploads($request, $data);
        $data = $this->normalizeScheduleFields($data);

        Destination::create($data);

        return to_route('buildings.edit', $building);
    }

    /**
     * Update the given destination, replacing its schedule PDF or responsible
     * photo when a new file is sent and deleting the previous one.
     */
    public function update(UpdateDestinationRequest $request, Destination $destination): RedirectResponse
    {
        $data = $request->validated();
        $data = $this->handleUploads($request, $data, $destination);
        $data = $this->normalizeScheduleFields($data, $destination);

        $destination->update($data);

        return to_route('buildings.edit', $destination->building_id);
    }

    /**
     * Delete the given destination and its stored files, if any.
     */
    public function destroy(Destination $destination): RedirectResponse
    {
        $buildingId = $destination->building_id;

        $this->deleteStoredFile($destination->schedule_file);
        $this->deleteStoredFile($destination->responsible_photo);
        $destination->delete();

        return to_route('buildings.edit', $buildingId);
    }

    /**
     * Stores the uploaded files (schedule PDF and responsible photo) and puts
     * their relative paths into the data. Replacing a file deletes the old one.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function handleUploads(
        StoreDestinationRequest|UpdateDestinationRequest $request,
        array $data,
        ?Destination $destination = null,
    ): array {
        $data = $this->handleFile($request, $data, 'schedule_file', 'schedules', $destination?->schedule_file);
        $data = $this->handleFile($request, $data, 'responsible_photo', 'responsibles', $destination?->responsible_photo);

        return $data;
    }

    /**
     * Stores an uploaded file under the given folder and writes its relative path
     * into the data. A new file replaces (and deletes) the old one; a "remove_{field}"
     * flag clears it; otherwise the existing path is kept untouched.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function handleFile(
        StoreDestinationRequest|UpdateDestinationRequest $request,
        array $data,
        string $field,
        string $folder,
        ?string $currentPath,
    ): array {
        if ($request->hasFile($field)) {
            $this->deleteStoredFile($currentPath);
            $data[$field] = $request->file($field)->store($folder, 'public');
        } elseif ($request->boolean("remove_{$field}")) {
            $this->deleteStoredFile($currentPath);
            $data[$field] = null;
        } else {
            // Don't overwrite the existing path when nothing was uploaded or removed.
            unset($data[$field]);
        }

        return $data;
    }

    /**
     * Keeps only the schedule fields that belong to the chosen type so a
     * destination never mixes both cases. Academic keeps just the schedule image;
     * attention keeps the text and the responsible person. The opposite fields are
     * cleared and their stored files deleted.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function normalizeScheduleFields(array $data, ?Destination $destination = null): array
    {
        $isAcademic = ($data['type'] ?? null) === ScheduleType::Academic->value;

        if ($isAcademic) {
            // Academic → only the schedule image; drop the text and the responsible.
            $this->clearFile($data, 'responsible_photo', $destination?->responsible_photo);
            $data['schedule_text'] = null;
            $data['responsible_name'] = null;
            $data['responsible_phone'] = null;
            $data['responsible_email'] = null;
        } else {
            // Attention → text + responsible; drop the schedule image.
            $this->clearFile($data, 'schedule_file', $destination?->schedule_file);
        }

        return $data;
    }

    /**
     * Clears a file field: deletes a just-stored file (if any) and the previous
     * one, then sets the field to null so the column is emptied on save.
     *
     * @param  array<string, mixed>  $data
     */
    private function clearFile(array &$data, string $field, ?string $currentPath): void
    {
        if (! empty($data[$field]) && $data[$field] !== $currentPath) {
            $this->deleteStoredFile($data[$field]);
        }
        $this->deleteStoredFile($currentPath);
        $data[$field] = null;
    }

    /**
     * Deletes a stored file from the public disk when a path is given.
     */
    private function deleteStoredFile(?string $path): void
    {
        if ($path) {
            Storage::disk('public')->delete($path);
        }
    }
}
