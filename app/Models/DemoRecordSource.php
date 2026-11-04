<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int|null $demo_record_id
 * @property int $demo_source_id
 * @property string $public_id
 * @property string|null $external_ref
 * @property array<string, mixed> $payload
 * @property int $reliability_score
 * @property int $match_score
 * @property array<int, string>|null $contributed_fields
 * @property Carbon $fetched_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class DemoRecordSource extends Model
{
    protected $fillable = [
        'demo_record_id',
        'demo_source_id',
        'public_id',
        'external_ref',
        'payload',
        'reliability_score',
        'match_score',
        'contributed_fields',
        'fetched_at',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'contributed_fields' => 'array',
            'reliability_score' => 'integer',
            'match_score' => 'integer',
            'fetched_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<DemoRecord, $this> */
    public function record(): BelongsTo
    {
        return $this->belongsTo(DemoRecord::class, 'demo_record_id');
    }

    /** @return BelongsTo<DemoSource, $this> */
    public function source(): BelongsTo
    {
        return $this->belongsTo(DemoSource::class, 'demo_source_id');
    }
}
