<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $slug
 * @property string $name
 * @property string $category
 * @property string|null $description
 * @property int $reliability
 * @property bool $is_active
 * @property string|null $notes
 * @property int $record_sources_count
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class DemoSource extends Model
{
    protected $fillable = [
        'slug',
        'name',
        'category',
        'description',
        'reliability',
        'is_active',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'reliability' => 'integer',
        ];
    }

    /** @return HasMany<DemoRecordSource, $this> */
    public function recordSources(): HasMany
    {
        return $this->hasMany(DemoRecordSource::class);
    }
}
