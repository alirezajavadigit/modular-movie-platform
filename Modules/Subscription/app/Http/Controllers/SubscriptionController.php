<?php

declare(strict_types=1);

namespace Modules\Subscription\Http\Controllers;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Subscription\Contracts\SubscriptionServiceInterface;
use Modules\Subscription\Http\Resources\Transformers\SubscriptionTransformer;
use Modules\Subscription\Models\Subscription;

class SubscriptionController extends Controller
{
    public function __construct(
        private readonly SubscriptionServiceInterface $service,
        private readonly SubscriptionTransformer      $transformer,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $perPage       = (int) $request->input('per_page', 15);
        $subscriptions = $this->service->paginate($perPage);

        return ApiResponse::paginated($subscriptions, $this->transformer, __('subscription::messages.index'));
    }

    public function show(Subscription $subscription): JsonResponse
    {
        return ApiResponse::fractal($subscription, $this->transformer, __('subscription::messages.show'));
    }

    public function destroy(Subscription $subscription): JsonResponse
    {
        $this->service->delete($subscription);

        return ApiResponse::noContent(__('subscription::messages.deleted'));
    }
}
