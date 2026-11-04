<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $slug
 * @property string $name
 * @property string $description
 * @property string $price_label
 * @property int $price_cents
 * @property int $duration_minutes
 * @property bool $grants_hidden
 * @property array<int, string>|null $features
 * @property bool $is_active
 * @property int $sort_order
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class DemoPlan extends Model
{
    protected $fillable = [
        'slug',
        'name',
        'description',
        'price_label',
        'price_cents',
        'duration_minutes',
        'grants_hidden',
        'features',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'features' => 'array',
            'grants_hidden' => 'boolean',
            'is_active' => 'boolean',
            'price_cents' => 'integer',
            'duration_minutes' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    /** @return HasMany<DemoPlanActivation, $this> */
    public function activations(): HasMany
    {
        return $this->hasMany(DemoPlanActivation::class);
    }
}
