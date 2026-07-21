<?php

namespace App\Http\Requests;

use App\Enums\ConnectionOverrideType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class SyncNodesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Validation rules for syncing a building's full set of nodes at once.
     *
     * Connection overrides travel in the same request because a brand new node
     * has no database id yet: they reference the editor's local_id, which the
     * controller resolves once every node has been persisted.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'nodes' => ['present', 'array'],
            'nodes.*.id' => ['nullable', 'integer', 'exists:nodes,id'],
            // The editor's stable key for a node: its id when it already exists,
            // a negative temporary id when it was just placed.
            'nodes.*.local_id' => ['nullable', 'integer'],
            'nodes.*.node_group_id' => ['nullable', 'integer', 'exists:node_groups,id'],
            'nodes.*.label' => ['nullable', 'string', 'max:255'],
            'nodes.*.position_x' => ['required', 'numeric'],
            'nodes.*.position_y' => ['required', 'numeric'],
            'nodes.*.position_z' => ['required', 'numeric'],
            'nodes.*.is_initial' => ['boolean'],
            'nodes.*.heading' => ['nullable', 'numeric', 'min:0', 'max:360'],
            // Omitting the key leaves the existing overrides untouched; sending
            // an empty array clears them, like the node set itself.
            'connection_overrides' => ['sometimes', 'array'],
            'connection_overrides.*.a' => ['required', 'integer'],
            'connection_overrides.*.b' => ['required', 'integer', 'different:connection_overrides.*.a'],
            'connection_overrides.*.type' => ['required', Rule::enum(ConnectionOverrideType::class)],
        ];
    }

    /**
     * Both sides of an override must point at a node included in this payload,
     * which also guarantees the pair belongs to the building being synced.
     *
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $localIds = $this->submittedLocalIds();

                foreach ($this->collect('connection_overrides') as $index => $override) {
                    foreach (['a', 'b'] as $side) {
                        $value = is_array($override) ? ($override[$side] ?? null) : null;

                        if (! is_numeric($value) || ! in_array((int) $value, $localIds, true)) {
                            $validator->errors()->add(
                                "connection_overrides.{$index}.{$side}",
                                'El nodo referenciado no viene en el conjunto enviado.'
                            );
                        }
                    }
                }
            },
        ];
    }

    /**
     * Keys the payload's nodes can be referenced by: their local_id, falling
     * back to the id for clients that only send existing nodes.
     *
     * @return array<int, int>
     */
    private function submittedLocalIds(): array
    {
        $ids = [];

        foreach ($this->collect('nodes') as $node) {
            if (! is_array($node)) {
                continue;
            }

            $key = $node['local_id'] ?? $node['id'] ?? null;

            if (is_numeric($key)) {
                $ids[] = (int) $key;
            }
        }

        return $ids;
    }
}
