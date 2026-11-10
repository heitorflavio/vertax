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
 * @property list<string>|null $search_types
 * @property array<string, mixed>|null $api_config
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
        'search_types',
        'api_config',
        'is_active',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'reliability' => 'integer',
            'search_types' => 'array',
            'api_config' => 'array',
        ];
    }

    /**
     * Aceita buscar por este tipo (chave de DemoConfig::SEARCH_TYPE_FIELDS)?
     */
    public function supportsSearch(string $type): bool
    {
        return in_array($type, $this->search_types ?? [], true);
    }

    /** @return HasMany<DemoRecordSource, $this> */
    public function recordSources(): HasMany
    {
        return $this->hasMany(DemoRecordSource::class);
    }
}
