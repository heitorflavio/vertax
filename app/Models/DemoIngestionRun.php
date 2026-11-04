<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $status
 * @property int $sources_synced
 * @property int $payloads_ingested
 * @property int $records_merged
 * @property int|null $duration_ms
 * @property string|null $message
 * @property Carbon|null $started_at
 * @property Carbon|null $finished_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class DemoIngestionRun extends Model
{
    protected $fillable = [
        'status',
        'sources_synced',
        'payloads_ingested',
        'records_merged',
        'duration_ms',
        'message',
        'started_at',
        'finished_at',
    ];

    protected function casts(): array
    {
        return [
            'sources_synced' => 'integer',
            'payloads_ingested' => 'integer',
            'records_merged' => 'integer',
            'duration_ms' => 'integer',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }
}
