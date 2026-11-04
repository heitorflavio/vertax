<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $demo_record_id
 * @property string|null $session_id
 * @property bool $plan_active
 * @property int $trust_score
 * @property int $sources_count
 * @property string|null $feedback
 * @property Carbon|null $feedback_at
 * @property string|null $ip_hash
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class DemoConsultation extends Model
{
    protected $fillable = [
        'demo_record_id',
        'session_id',
        'plan_active',
        'trust_score',
        'sources_count',
        'feedback',
        'feedback_at',
        'ip_hash',
    ];

    protected function casts(): array
    {
        return [
            'plan_active' => 'boolean',
            'trust_score' => 'integer',
            'sources_count' => 'integer',
            'feedback_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<DemoRecord, $this> */
    public function record(): BelongsTo
    {
        return $this->belongsTo(DemoRecord::class, 'demo_record_id');
    }
}
