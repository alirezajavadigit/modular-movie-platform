<?php

declare(strict_types=1);

namespace Modules\Subscription\Http\Controllers;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Subscription\Contracts\SubscriptionServiceInterface;
use Modules\Subscription\DTOs\CreateSubscriptionDTO;
use Modules\Subscription\Http\Requests\StoreSubscriptionRequest;
use Modules\Subscription\Http\Resources\Transformers\SubscriptionTransformer;

class SubscriptionStatusController extends Controller
{
    public function __construct(
        private readonly SubscriptionServiceInterface $service,
        private readonly SubscriptionTransformer      $transformer,
    ) {}

    public function subscribe(StoreSubscriptionRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $dto = new CreateSubscriptionDTO(
            userId:  (int) auth()->id(),
            planId:  (int) $validated['plan_id'],
            driver:  $validated['driver'],
        );

        $paymentUrl = $this->service->subscribe($dto);

        return ApiResponse::success(['payment_url' => $paymentUrl], __('subscription::messages.subscribed'));
    }

    public function activate(int $id): JsonResponse
    {
        $subscription = $this->service->activate($id);

        return ApiResponse::fractal($subscription, $this->transformer, __('subscription::messages.activated'));
    }

    public function cancel(int $id): JsonResponse
    {
        $subscription = $this->service->cancel($id);

        return ApiResponse::fractal($subscription, $this->transformer, __('subscription::messages.canceled'));
    }
}
