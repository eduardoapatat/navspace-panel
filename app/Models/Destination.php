<?php

namespace App\Models;

use App\Enums\ScheduleType;
use Database\Factories\DestinationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $building_id
 * @property int $space_type_id
 * @property int|null $node_id
 * @property string $display_name
 * @property string|null $description
 * @property ScheduleType $type
 * @property string|null $schedule_file
 * @property string|null $schedule_text
 * @property string|null $responsible_name
 * @property string|null $responsible_phone
 * @property string|null $responsible_email
 * @property string|null $responsible_photo
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'building_id', 'space_type_id', 'node_id',
    'display_name', 'description', 'type', 'schedule_file', 'schedule_text',
    'responsible_name', 'responsible_phone', 'responsible_email', 'responsible_photo',
])]
class Destination extends Model
{
    /** @use HasFactory<DestinationFactory> */
    use HasFactory;

    /**
     * The building this destination belongs to.
     *
     * @return BelongsTo<Building, $this>
     */
    public function building(): BelongsTo
    {
        return $this->belongsTo(Building::class);
    }

    /**
     * The space type this destination is classified under.
     *
     * @return BelongsTo<SpaceType, $this>
     */
    public function spaceType(): BelongsTo
    {
        return $this->belongsTo(SpaceType::class);
    }

    /**
     * The navigation node this destination is anchored to (nullable in the MVP).
     *
     * @return BelongsTo<Node, $this>
     */
    public function node(): BelongsTo
    {
        return $this->belongsTo(Node::class);
    }

    /**
     * Attribute casts.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => ScheduleType::class,
        ];
    }

    /**
     * Public URL of the academic schedule PDF, or null when none is uploaded.
     * Derived from the stored relative path so it always matches the request host.
     */
    public function getScheduleFileUrlAttribute(): ?string
    {
        return $this->schedule_file ? url('storage/'.$this->schedule_file) : null;
    }

    /**
     * Public URL of the responsible person's photo, or null when none is uploaded.
     * Derived from the stored relative path so it always matches the request host.
     */
    public function getResponsiblePhotoUrlAttribute(): ?string
    {
        return $this->responsible_photo ? url('storage/'.$this->responsible_photo) : null;
    }
}
