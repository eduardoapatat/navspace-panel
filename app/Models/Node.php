<?php

namespace App\Models;

use Database\Factories\NodeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $building_id
 * @property int|null $node_group_id
 * @property string|null $label
 * @property float $position_x
 * @property float $position_y
 * @property float $position_z
 * @property bool $is_initial
 * @property float|null $heading
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['building_id', 'node_group_id', 'label', 'position_x', 'position_y', 'position_z', 'is_initial', 'heading'])]
class Node extends Model
{
    /** @use HasFactory<NodeFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'position_x' => 'float',
            'position_y' => 'float',
            'position_z' => 'float',
            'is_initial' => 'boolean',
            'heading' => 'float',
        ];
    }

    /**
     * The building this node belongs to.
     *
     * @return BelongsTo<Building, $this>
     */
    public function building(): BelongsTo
    {
        return $this->belongsTo(Building::class);
    }

    /**
     * The optional group this node is organized under (nullable).
     *
     * @return BelongsTo<NodeGroup, $this>
     */
    public function nodeGroup(): BelongsTo
    {
        return $this->belongsTo(NodeGroup::class);
    }

    /**
     * The destinations anchored to this node.
     *
     * @return HasMany<Destination, $this>
     */
    public function destinations(): HasMany
    {
        return $this->hasMany(Destination::class);
    }
}
