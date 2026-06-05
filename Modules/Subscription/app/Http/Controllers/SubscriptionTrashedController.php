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

class SubscriptionTrashedController extends Controller
{
    protected static string $modelClass = Subscription::class;
    public function __construct(
        private readonly SubscriptionServiceInterface $service,
        private readonly SubscriptionTransformer      $transformer,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $perPage       = (int) $request->input('per_page', 15);
        $subscriptions = $this->service->getTrashed($perPage);

        return ApiResponse::paginated($subscriptions, $this->transformer, __('subscription::messages.trashed'));
    }

    public function restore(Subscription $subscription): JsonResponse
    {
        $subscription = $this->service->restore($subscription);

        return ApiResponse::fractal($subscription, $this->transformer, __('subscription::messages.restored'));
    }

    public function forceDelete(Subscription $subscription): JsonResponse
    {
        $this->service->forceDelete($subscription);

        return ApiResponse::noContent(__('subscription::messages.force_deleted'));
    }
}
