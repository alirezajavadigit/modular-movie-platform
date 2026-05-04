<?php

declare(strict_types=1);

namespace Modules\Subscription\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Subscription\Database\Factories\SubscriptionPlanFactory;
use Modules\Subscription\Enums\SubscriptionPlanStatus;

class SubscriptionPlan extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'price',
        'duration_days',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'price'         => 'decimal:2',
            'duration_days' => 'integer',
            'status'        => SubscriptionPlanStatus::class,
        ];
    }

    protected static function newFactory(): SubscriptionPlanFactory
    {
        return SubscriptionPlanFactory::new();
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class, 'plan_id');
    }
}
