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

class SubscriptionPlanQueryController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly SubscriptionPlanServiceInterface $service,
        private readonly SubscriptionPlanTransformer      $transformer,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', SubscriptionPlan::class);

        $perPage = (int) $request->input('per_page', 15);
        $plans   = $this->service->paginate($perPage);

        return ApiResponse::paginated($plans, $this->transformer, __('subscription::messages.plans_index'));
    }

    public function publicIndex(Request $request): JsonResponse
    {
        $perPage = (int) $request->input('per_page', 15);
        $plans   = $this->service->getActivePaginate($perPage);

        return ApiResponse::paginated($plans, $this->transformer, __('subscription::messages.plans_index'));
    }

    public function publicShow(SubscriptionPlan $subscriptionPlan): JsonResponse
    {
        return ApiResponse::fractal($subscriptionPlan, $this->transformer, __('subscription::messages.plan_show'));
    }
}
