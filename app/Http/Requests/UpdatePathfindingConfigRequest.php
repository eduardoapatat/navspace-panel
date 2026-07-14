<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdatePathfindingConfigRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Validation rules for the A* Point Graph parameters the Unity app reads.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'max_distance' => ['required', 'numeric', 'min:0'],
            // Per-axis Y limit: nodes farther apart than this in height never
            // connect, so the graph can't jump between floors through a ceiling.
            // 0 means "no vertical limit".
            'max_y_distance' => ['required', 'numeric', 'min:0'],
            'thick_radius' => ['required', 'numeric', 'min:0'],
            'check_walls' => ['required', 'boolean'],
        ];
    }
}
