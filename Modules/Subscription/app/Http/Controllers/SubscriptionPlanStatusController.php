<?php

declare(strict_types=1);

namespace Modules\Subscription\Http\Controllers;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Subscription\Contracts\SubscriptionPlanServiceInterface;
use Modules\Subscription\Http\Resources\Transformers\SubscriptionPlanTransformer;
use Modules\Subscription\Models\SubscriptionPlan;

class SubscriptionPlanStatusController extends Controller
{
    protected static string $modelClass = SubscriptionPlan::class;
    public function __construct(
        private readonly SubscriptionPlanServiceInterface $service,
        private readonly SubscriptionPlanTransformer      $transformer,
    ) {}

    public function activate(SubscriptionPlan $subscriptionPlan): JsonResponse
    {
        $plan = $this->service->activate($subscriptionPlan);

        return ApiResponse::fractal($plan, $this->transformer, __('subscription::messages.plan_activated'));
    }

    public function deactivate(SubscriptionPlan $subscriptionPlan): JsonResponse
    {
        $plan = $this->service->deactivate($subscriptionPlan);

        return ApiResponse::fractal($plan, $this->transformer, __('subscription::messages.plan_deactivated'));
    }
}
