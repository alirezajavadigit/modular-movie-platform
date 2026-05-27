<?php

declare(strict_types=1);

namespace Modules\Subscription\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Auth\Models\User;
use Modules\Payment\Contracts\PayableInterface;
use Modules\Payment\Models\Payment;
use Modules\Subscription\Database\Factories\SubscriptionFactory;
use Modules\Subscription\Enums\SubscriptionStatus;

class Subscription extends Model implements PayableInterface
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'plan_id',
        'payment_id',
        'starts_at',
        'ends_at',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at'   => 'datetime',
            'status'    => SubscriptionStatus::class,
        ];
    }

    protected static function newFactory(): SubscriptionFactory
    {
        return SubscriptionFactory::new();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class, 'plan_id');
    }

    public function payment(): MorphOne
    {
        return $this->morphOne(Payment::class, 'payable');
    }

    public function activate(int $paymentId): void
    {
        if ($this->status === SubscriptionStatus::ACTIVE) {
            return;
        }

        $startsAt = now();

        $this->update([
            'payment_id' => $paymentId,
            'status'     => SubscriptionStatus::ACTIVE,
            'starts_at'  => $startsAt,
            'ends_at'    => $startsAt->copy()->addDays((int) $this->plan->duration_days),
        ]);
    }

    public function markPaymentFailed(int $paymentId): void
    {
        if ($this->status === SubscriptionStatus::ACTIVE) {
            return;
        }

        $this->update([
            'payment_id' => $paymentId,
            'status'     => SubscriptionStatus::CANCELED,
        ]);
    }

    public function getPayableId(): int|string
    {
        return $this->id;
    }

    public function getPayableAmount(): float
    {
        return (float) $this->plan->price;
    }

    public function getPayableDescription(): string
    {
        return "Subscription to {$this->plan->name}";
    }
}
