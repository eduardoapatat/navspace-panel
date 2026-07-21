<?php

namespace App\Models;

use App\Enums\ConnectionOverrideType;
use Database\Factories\NodeConnectionOverrideFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A manual exception to how Unity links two nodes in the A* graph: either they
 * must never be connected (a wall sits between them) or they must always be,
 * even when distance or height would rule the connection out (stairs).
 *
 * The pair is always stored normalized, with the lowest node id in node_a_id.
 *
 * @property int $id
 * @property int $building_id
 * @property int $node_a_id
 * @property int $node_b_id
 * @property ConnectionOverrideType $type
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['building_id', 'node_a_id', 'node_b_id', 'type'])]
class NodeConnectionOverride extends Model
{
    /** @use HasFactory<NodeConnectionOverrideFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => ConnectionOverrideType::class,
        ];
    }

    /**
     * The building this rule belongs to.
     *
     * @return BelongsTo<Building, $this>
     */
    public function building(): BelongsTo
    {
        return $this->belongsTo(Building::class);
    }

    /**
     * The first node of the pair (always the lowest id).
     *
     * @return BelongsTo<Node, $this>
     */
    public function nodeA(): BelongsTo
    {
        return $this->belongsTo(Node::class, 'node_a_id');
    }

    /**
     * The second node of the pair.
     *
     * @return BelongsTo<Node, $this>
     */
    public function nodeB(): BelongsTo
    {
        return $this->belongsTo(Node::class, 'node_b_id');
    }
}
