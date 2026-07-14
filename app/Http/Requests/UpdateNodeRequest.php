<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateNodeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Validation rules for updating a node.
     *
     * All fields are optional so the editor can move a node or toggle its
     * initial flag independently.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'node_group_id' => ['sometimes', 'nullable', 'integer', 'exists:node_groups,id'],
            'label' => ['sometimes', 'nullable', 'string', 'max:255'],
            'position_x' => ['sometimes', 'numeric'],
            'position_y' => ['sometimes', 'numeric'],
            'position_z' => ['sometimes', 'numeric'],
            'is_initial' => ['sometimes', 'boolean'],
        ];
    }
}
