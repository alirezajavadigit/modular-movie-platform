<?php

declare(strict_types=1);

namespace Modules\Subscription\Http\Controllers;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Subscription\Contracts\SubscriptionPlanServiceInterface;
use Modules\Subscription\Http\Resources\Transformers\SubscriptionPlanTransformer;
use Modules\Subscription\Models\SubscriptionPlan;

class SubscriptionPlanTrashedController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly SubscriptionPlanServiceInterface $service,
        private readonly SubscriptionPlanTransformer      $transformer,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewTrashed', SubscriptionPlan::class);

        $perPage = (int) $request->input('per_page', 15);
        $plans   = $this->service->getTrashed($perPage);

        return ApiResponse::paginated($plans, $this->transformer, __('subscription::messages.plans_trashed'));
    }

    public function restore(SubscriptionPlan $subscriptionPlan): JsonResponse
    {
        $this->authorize('restore', $subscriptionPlan);

        $plan = $this->service->restore($subscriptionPlan);

        return ApiResponse::fractal($plan, $this->transformer, __('subscription::messages.plan_restored'));
    }

    public function forceDelete(SubscriptionPlan $subscriptionPlan): JsonResponse
    {
        $this->authorize('forceDelete', $subscriptionPlan);

        $this->service->forceDelete($subscriptionPlan);

        return ApiResponse::noContent(__('subscription::messages.plan_force_deleted'));
    }
}
