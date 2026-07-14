<?php

namespace App\Models;

use App\Concerns\Filterable;
use Database\Factories\BuildingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property string $address
 * @property string|null $image_path
 * @property string|null $model_path
 * @property int $model_yaw
 * @property string|null $image_url
 * @property string|null $model_url
 * @property array<string, mixed>|null $pathfinding_config
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'address', 'image_path', 'model_path', 'model_yaw', 'pathfinding_config'])]
class Building extends Model
{
    /** @use HasFactory<BuildingFactory> */
    use Filterable, HasFactory;

    /**
     * Expose the derived URLs whenever the model is serialized to the panel or API.
     *
     * @var list<string>
     */
    protected $appends = ['image_url', 'model_url'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'pathfinding_config' => 'array',
        ];
    }

    /**
     * Absolute URL of the building photo, derived from the stored relative path
     * so it follows the current request host (local domain or LAN IP).
     *
     * @return Attribute<string|null, never>
     */
    protected function imageUrl(): Attribute
    {
        return Attribute::get(fn (): ?string => $this->image_path === null
            ? null
            : url('storage/'.$this->image_path));
    }

    /**
     * Absolute URL of the .glb model, derived from the stored relative path so it
     * follows the current request host (local domain or LAN IP).
     *
     * @return Attribute<string|null, never>
     */
    protected function modelUrl(): Attribute
    {
        return Attribute::get(fn (): ?string => $this->model_path === null
            ? null
            : url('storage/'.$this->model_path));
    }

    /**
     * Columns the search box matches against.
     *
     * @return array<int, string>
     */
    protected function searchable(): array
    {
        return ['name', 'address'];
    }

    /**
     * Columns allowed for sorting.
     *
     * @return array<int, string>
     */
    protected function sortable(): array
    {
        return ['name', 'address', 'created_at'];
    }

    /**
     * Show the newest buildings first by default.
     *
     * @return array{0: string, 1: string}
     */
    protected function defaultSort(): array
    {
        return ['created_at', 'desc'];
    }

    /**
     * The optional node groups defined for this building (e.g. "Piso 1").
     *
     * @return HasMany<NodeGroup, $this>
     */
    public function nodeGroups(): HasMany
    {
        return $this->hasMany(NodeGroup::class);
    }

    /**
     * The space types defined for this building.
     *
     * @return HasMany<SpaceType, $this>
     */
    public function spaceTypes(): HasMany
    {
        return $this->hasMany(SpaceType::class);
    }

    /**
     * The navigation nodes placed in this building.
     *
     * @return HasMany<Node, $this>
     */
    public function nodes(): HasMany
    {
        return $this->hasMany(Node::class);
    }

    /**
     * Every destination in this building.
     *
     * @return HasMany<Destination, $this>
     */
    public function destinations(): HasMany
    {
        return $this->hasMany(Destination::class);
    }
}
