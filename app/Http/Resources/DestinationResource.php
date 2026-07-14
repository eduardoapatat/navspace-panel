<?php

namespace App\Http\Resources;

use App\Enums\ScheduleType;
use App\Models\Destination;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Destination detail shown by the Unity app on arrival.
 *
 * @mixin Destination
 */
class DestinationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // The type is authoritative: academic exposes only the schedule image;
        // attention exposes the text schedule and the responsible. Fields of the
        // other case are never sent, even if stale data lingers in the columns.
        $isAcademic = $this->type === ScheduleType::Academic;

        return [
            'id' => $this->id,
            'display_name' => $this->display_name,
            'description' => $this->description,
            'space_type' => [
                'name' => $this->spaceType->name,
                'icon' => $this->spaceType->icon,
            ],
            // Fixed type the Unity app reads to decide how to show the schedule:
            // 'academic' → open the image; 'attention' → show the text + responsible.
            'type' => $this->type->value,
            'schedule_file' => $isAcademic ? $this->schedule_file_url : null,
            'schedule_text' => $isAcademic ? null : $this->schedule_text,
            'responsible' => (! $isAcademic && $this->responsible_name) ? [
                'name' => $this->responsible_name,
                'phone' => $this->responsible_phone,
                'email' => $this->responsible_email,
                'photo' => $this->responsible_photo_url,
            ] : null,
            'node' => $this->when($this->node !== null, fn (): array => [
                'id' => $this->node->id,
                'position_x' => $this->node->position_x,
                'position_y' => $this->node->position_y,
                'position_z' => $this->node->position_z,
            ]),
        ];
    }
}
