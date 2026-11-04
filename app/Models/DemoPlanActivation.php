<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int|null $demo_plan_id
 * @property string $plan_slug
 * @property string $session_id
 * @property Carbon $activated_at
 * @property Carbon $expires_at
 * @property string|null $ip_hash
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class DemoPlanActivation extends Model
{
    protected $fillable = [
        'demo_plan_id',
        'plan_slug',
        'session_id',
        'activated_at',
        'expires_at',
        'ip_hash',
    ];

    protected function casts(): array
    {
        return [
            'activated_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<DemoPlan, $this> */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(DemoPlan::class, 'demo_plan_id');
    }
}
