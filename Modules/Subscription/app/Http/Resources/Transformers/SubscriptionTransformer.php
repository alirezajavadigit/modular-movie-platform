<?php

declare(strict_types=1);

namespace Modules\Subscription\Http\Resources\Transformers;

use League\Fractal\Resource\Item;
use League\Fractal\Resource\NullResource;
use League\Fractal\TransformerAbstract;
use Modules\Subscription\Models\Subscription;

class SubscriptionTransformer extends TransformerAbstract
{
    protected array $availableIncludes = [
        'plan',
        'user',
    ];

    public function transform(Subscription $subscription): array
    {
        return [
            'id'         => $subscription->id,
            'user_id'    => $subscription->user_id,
            'plan_id'    => $subscription->plan_id,
            'payment_id' => $subscription->payment_id,
            'starts_at'  => $subscription->starts_at?->toIso8601String(),
            'ends_at'    => $subscription->ends_at?->toIso8601String(),
            'status'     => $subscription->status->value,
            'status_label' => $subscription->status->label(),
            'created_at' => $subscription->created_at?->toIso8601String(),
            'updated_at' => $subscription->updated_at?->toIso8601String(),
            'deleted_at' => $subscription->deleted_at?->toIso8601String(),
        ];
    }

    public function includePlan(Subscription $subscription): Item|NullResource
    {
        $plan = $subscription->plan;

        if (!$plan) {
            return $this->null();
        }

        return $this->item($plan, new SubscriptionPlanTransformer());
    }

    public function includeUser(Subscription $subscription): Item|NullResource
    {
        $user = $subscription->user;

        if (!$user) {
            return $this->null();
        }

        return $this->item($user, function ($user) {
            return [
                'id'    => $user->id,
                'name'  => $user->name,
                'email' => $user->email,
            ];
        });
    }
}
