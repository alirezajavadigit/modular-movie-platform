<?php

declare(strict_types=1);

namespace Modules\Subscription\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Auth\Models\User;
use Modules\Payment\Contracts\PayableInterface;
use Modules\Payment\Enums\PaymentStatus;
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
        DB::transaction(function () use ($paymentId): void {
            $current = $this->lockedForUpdate();

            if ($current->status === SubscriptionStatus::ACTIVE) {
                if ($current->payment_id !== $paymentId) {
                    Log::error('A successful payment arrived for an already active subscription; manual financial reconciliation may be required.', [
                        'subscription_id'   => $current->id,
                        'payment_id'        => $paymentId,
                        'active_payment_id' => $current->payment_id,
                    ]);
                }

                $this->syncFrom($current);

                return;
            }

            $startsAt = now();

            $current->update([
                'payment_id' => $paymentId,
                'status'     => SubscriptionStatus::ACTIVE,
                'starts_at'  => $startsAt,
                'ends_at'    => $startsAt->copy()->addDays((int) $current->plan->duration_days),
            ]);

            $this->syncFrom($current);
        });
    }

    public function markPaymentFailed(int $paymentId): void
    {
        DB::transaction(function () use ($paymentId): void {
            $current = $this->lockedForUpdate();

            if ($current->status === SubscriptionStatus::ACTIVE || $current->hasOtherOpenPayment($paymentId)) {
                $this->syncFrom($current);

                return;
            }

            $current->update([
                'payment_id' => $paymentId,
                'status'     => SubscriptionStatus::CANCELED,
            ]);

            $this->syncFrom($current);
        });
    }

    private function lockedForUpdate(): self
    {
        return static::query()->whereKey($this->getKey())->lockForUpdate()->firstOrFail();
    }

    private function syncFrom(self $current): void
    {
        $this->setRawAttributes($current->getAttributes(), true);
    }

    private function hasOtherOpenPayment(int $paymentId): bool
    {
        return Payment::query()
            ->whereIn('payable_type', array_unique([static::class, $this->getMorphClass()]))
            ->where('payable_id', $this->getKey())
            ->whereKeyNot($paymentId)
            ->whereIn('status', [PaymentStatus::PENDING->value, PaymentStatus::SUCCESS->value])
            ->exists();
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
