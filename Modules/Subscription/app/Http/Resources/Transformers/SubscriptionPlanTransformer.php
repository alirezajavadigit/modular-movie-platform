<?php

declare(strict_types=1);

namespace Modules\Subscription\Http\Resources\Transformers;

use League\Fractal\TransformerAbstract;
use Modules\Subscription\Models\SubscriptionPlan;

class SubscriptionPlanTransformer extends TransformerAbstract
{
    public function transform(SubscriptionPlan $plan): array
    {
        return [
            'id'            => $plan->id,
            'name'          => $plan->name,
            'description'   => $plan->description,
            'price'         => $plan->price,
            'duration_days' => $plan->duration_days,
            'status'        => $plan->status->value,
            'status_label'  => $plan->status->label(),
            'created_at'    => $plan->created_at?->toIso8601String(),
            'updated_at'    => $plan->updated_at?->toIso8601String(),
            'deleted_at'    => $plan->deleted_at?->toIso8601String(),
        ];
    }
}
