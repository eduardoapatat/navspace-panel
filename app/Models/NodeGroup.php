<?php

namespace App\Models;

use Database\Factories\NodeGroupFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * An optional label used to group a building's nodes and destinations
 * (e.g. "Piso 1", "Sótano"). Grouping is entirely optional: nodes and
 * destinations hang off the building directly and may leave node_group_id null.
 *
 * @property int $id
 * @property int $building_id
 * @property string $name
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['building_id', 'name'])]
class NodeGroup extends Model
{
    /** @use HasFactory<NodeGroupFactory> */
    use HasFactory;

    /**
     * The building this group belongs to.
     *
     * @return BelongsTo<Building, $this>
     */
    public function building(): BelongsTo
    {
        return $this->belongsTo(Building::class);
    }

    /**
     * The navigation nodes grouped under this label.
     *
     * @return HasMany<Node, $this>
     */
    public function nodes(): HasMany
    {
        return $this->hasMany(Node::class);
    }

    /**
     * The destinations grouped under this label.
     *
     * @return HasMany<Destination, $this>
     */
    public function destinations(): HasMany
    {
        return $this->hasMany(Destination::class);
    }
}
