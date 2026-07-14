<?php

namespace App\Http\Requests;

use App\Enums\ScheduleType;
use App\Models\Building;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDestinationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Attach the building from the route so the destination always belongs to it.
     */
    protected function prepareForValidation(): void
    {
        /** @var Building $building */
        $building = $this->route('building');

        $this->merge(['building_id' => $building->id]);
    }

    /**
     * Validation rules for creating a destination within a building.
     *
     * The space type, the optional group and the optional node must all belong
     * to the building in the route, so a destination never mixes data from
     * another building.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var Building $building */
        $building = $this->route('building');

        return [
            'building_id' => ['required', Rule::in([$building->id])],
            'space_type_id' => [
                'required',
                Rule::exists('space_types', 'id')->where('building_id', $building->id),
            ],
            'display_name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            // The node must belong to the same building and must NOT be the
            // initial (calibration) node, which is not a navigable destination.
            'node_id' => [
                'nullable',
                Rule::exists('nodes', 'id')
                    ->where('building_id', $building->id)
                    ->where('is_initial', 0),
            ],
            // Schedule fields: a fixed type, a PDF (academic) or free text/contact
            // (attention). All optional; Unity shows whatever is present.
            'type' => ['required', Rule::enum(ScheduleType::class)],
            // The academic schedule is now an image (horizontal), not a PDF: the
            // Quest browser doesn't render PDFs, so Unity shows it inline instead.
            'schedule_file' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
            'schedule_text' => ['nullable', 'string', 'max:255'],
            'responsible_name' => ['nullable', 'string', 'max:255'],
            'responsible_phone' => ['nullable', 'string', 'max:50'],
            'responsible_email' => ['nullable', 'email', 'max:255'],
            // Photo of the responsible person, shown in the Unity panel (4:3).
            'responsible_photo' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
        ];
    }
}
