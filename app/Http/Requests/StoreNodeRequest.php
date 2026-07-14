<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreNodeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Validation rules for creating a node.
     *
     * Positions are floats relative to the building's .glb origin.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'node_group_id' => ['nullable', 'integer', 'exists:node_groups,id'],
            'label' => ['nullable', 'string', 'max:255'],
            'position_x' => ['required', 'numeric'],
            'position_y' => ['required', 'numeric'],
            'position_z' => ['required', 'numeric'],
            'is_initial' => ['boolean'],
        ];
    }
}
