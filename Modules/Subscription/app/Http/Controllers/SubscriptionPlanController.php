<?php

declare(strict_types=1);

namespace Modules\Subscription\Http\Controllers;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Subscription\Contracts\SubscriptionPlanServiceInterface;
use Modules\Subscription\DTOs\CreateSubscriptionPlanDTO;
use Modules\Subscription\DTOs\UpdateSubscriptionPlanDTO;
use Modules\Subscription\Http\Requests\StoreSubscriptionPlanRequest;
use Modules\Subscription\Http\Requests\UpdateSubscriptionPlanRequest;
use Modules\Subscription\Http\Resources\Transformers\SubscriptionPlanTransformer;
use Modules\Subscription\Models\SubscriptionPlan;

class SubscriptionPlanController extends Controller
{
    public function __construct(
        private readonly SubscriptionPlanServiceInterface $service,
        private readonly SubscriptionPlanTransformer      $transformer,
    ) {}

    public function store(StoreSubscriptionPlanRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $dto = new CreateSubscriptionPlanDTO(
            name:         $validated['name'],
            price:        (float) $validated['price'],
            durationDays: (int) $validated['duration_days'],
            description:  $validated['description'] ?? null,
        );

        $plan = $this->service->store($dto);

        return ApiResponse::fractalCreated($plan, $this->transformer, __('subscription::messages.plan_created'));
    }

    public function update(UpdateSubscriptionPlanRequest $request, SubscriptionPlan $subscriptionPlan): JsonResponse
    {
        $validated = $request->validated();

        $dto = new UpdateSubscriptionPlanDTO(
            name:         $validated['name'] ?? null,
            description:  $validated['description'] ?? null,
            price:        isset($validated['price']) ? (float) $validated['price'] : null,
            durationDays: isset($validated['duration_days']) ? (int) $validated['duration_days'] : null,
        );

        $plan = $this->service->update($subscriptionPlan, $dto);

        return ApiResponse::fractal($plan, $this->transformer, __('subscription::messages.plan_updated'));
    }

    public function destroy(SubscriptionPlan $subscriptionPlan): JsonResponse
    {
        $this->service->delete($subscriptionPlan);

        return ApiResponse::noContent(__('subscription::messages.plan_deleted'));
    }
}
