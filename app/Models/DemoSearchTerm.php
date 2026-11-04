<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $demo_record_id
 * @property string $type
 * @property string $value
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class DemoSearchTerm extends Model
{
    protected $fillable = [
        'demo_record_id',
        'type',
        'value',
    ];

    /** @return BelongsTo<DemoRecord, $this> */
    public function record(): BelongsTo
    {
        return $this->belongsTo(DemoRecord::class, 'demo_record_id');
    }
}
