<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $slug
 * @property string $name
 * @property string $description
 * @property string $category
 * @property string|null $icon
 * @property string $badge
 * @property bool $is_demo
 * @property bool $is_active
 * @property int $sort_order
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class DemoModule extends Model
{
    protected $fillable = [
        'slug',
        'name',
        'description',
        'category',
        'icon',
        'badge',
        'is_demo',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_demo' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }
}
