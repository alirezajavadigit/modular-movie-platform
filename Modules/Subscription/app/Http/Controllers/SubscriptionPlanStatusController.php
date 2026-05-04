<?php

declare(strict_types=1);

namespace Modules\Subscription\Http\Controllers;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Subscription\Contracts\SubscriptionPlanServiceInterface;
use Modules\Subscription\Http\Resources\Transformers\SubscriptionPlanTransformer;

class SubscriptionPlanStatusController extends Controller
{
    public function __construct(
        private readonly SubscriptionPlanServiceInterface $service,
        private readonly SubscriptionPlanTransformer      $transformer,
    ) {}

    public function activate(int $id): JsonResponse
    {
        $plan = $this->service->activate($id);

        return ApiResponse::fractal($plan, $this->transformer, __('subscription::messages.plan_activated'));
    }

    public function deactivate(int $id): JsonResponse
    {
        $plan = $this->service->deactivate($id);

        return ApiResponse::fractal($plan, $this->transformer, __('subscription::messages.plan_deactivated'));
    }
}
