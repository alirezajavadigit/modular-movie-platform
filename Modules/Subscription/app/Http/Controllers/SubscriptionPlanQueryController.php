<?php

declare(strict_types=1);

namespace Modules\Subscription\Http\Controllers;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Subscription\Contracts\SubscriptionPlanServiceInterface;
use Modules\Subscription\Http\Resources\Transformers\SubscriptionPlanTransformer;

class SubscriptionPlanQueryController extends Controller
{
    public function __construct(
        private readonly SubscriptionPlanServiceInterface $service,
        private readonly SubscriptionPlanTransformer      $transformer,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->input('per_page', 15);
        $plans   = $this->service->paginate($perPage);

        return ApiResponse::paginated($plans, $this->transformer, __('subscription::messages.plans_index'));
    }

    public function show(int $id): JsonResponse
    {
        $plan = $this->service->findById($id);

        return ApiResponse::fractal($plan, $this->transformer, __('subscription::messages.plan_show'));
    }
}
