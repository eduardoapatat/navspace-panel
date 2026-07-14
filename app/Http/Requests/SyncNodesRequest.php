<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class SyncNodesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Validation rules for syncing a building's full set of nodes at once.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'nodes' => ['present', 'array'],
            'nodes.*.id' => ['nullable', 'integer', 'exists:nodes,id'],
            'nodes.*.node_group_id' => ['nullable', 'integer', 'exists:node_groups,id'],
            'nodes.*.label' => ['nullable', 'string', 'max:255'],
            'nodes.*.position_x' => ['required', 'numeric'],
            'nodes.*.position_y' => ['required', 'numeric'],
            'nodes.*.position_z' => ['required', 'numeric'],
            'nodes.*.is_initial' => ['boolean'],
            'nodes.*.heading' => ['nullable', 'numeric', 'min:0', 'max:360'],
        ];
    }
}
