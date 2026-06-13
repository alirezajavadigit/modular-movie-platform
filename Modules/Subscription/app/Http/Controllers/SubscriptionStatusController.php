<?php

declare(strict_types=1);

namespace Modules\Subscription\Http\Controllers;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Modules\Subscription\Contracts\SubscriptionServiceInterface;
use Modules\Subscription\DTOs\CreateSubscriptionDTO;
use Modules\Subscription\Http\Requests\StoreSubscriptionRequest;
use Modules\Subscription\Http\Resources\Transformers\SubscriptionTransformer;
use Modules\Subscription\Models\Subscription;
use OpenApi\Attributes as OA;

class SubscriptionStatusController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly SubscriptionServiceInterface $service,
        private readonly SubscriptionTransformer      $transformer,
    ) {}

    #[OA\Post(
        path: '/api/v1/subscriptions/subscribe',
        operationId: 'subscription.subscribe',
        summary: 'Subscribe to a plan and receive a gateway checkout URL',
        security: [['bearerAuth' => []]],
        tags: ['Subscription'],
        requestBody: new OA\RequestBody(ref: '#/components/requestBodies/StoreSubscriptionRequest'),
    )]
    #[OA\Response(response: 200, ref: '#/components/responses/SubscriptionPaymentUrl')]
    #[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
    #[OA\Response(response: 403, ref: '#/components/responses/Forbidden')]
    #[OA\Response(response: 422, ref: '#/components/responses/ValidationError')]
    #[OA\Response(response: 500, ref: '#/components/responses/ServerError')]
    public function subscribe(StoreSubscriptionRequest $request): JsonResponse
    {
        $this->authorize('create', Subscription::class);

        $validated = $request->validated();

        $dto = new CreateSubscriptionDTO(
            userId: (int) auth()->id(),
            planId: (int) $validated['plan_id'],
            driver: $validated['driver'],
        );

        $paymentUrl = $this->service->subscribe($dto);

        return ApiResponse::success(['payment_url' => $paymentUrl], __('subscription::messages.subscribed'));
    }

    #[OA\Patch(
        path: '/api/v1/admin/subscriptions/{subscription}/activate',
        operationId: 'subscription.admin.activate',
        summary: 'Activate a subscription',
        security: [['bearerAuth' => []]],
        tags: ['Subscription'],
        parameters: [
            new OA\Parameter(name: 'subscription', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
    )]
    #[OA\Response(response: 200, ref: '#/components/responses/SubscriptionItem')]
    #[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
    #[OA\Response(response: 403, ref: '#/components/responses/Forbidden')]
    #[OA\Response(response: 404, ref: '#/components/responses/NotFound')]
    #[OA\Response(response: 500, ref: '#/components/responses/ServerError')]
    public function activate(Subscription $subscription): JsonResponse
    {
        $this->authorize('activate', $subscription);

        $subscription = $this->service->activate($subscription);

        return ApiResponse::fractal($subscription, $this->transformer, __('subscription::messages.activated'));
    }

    #[OA\Patch(
        path: '/api/v1/subscriptions/{subscription}/cancel',
        operationId: 'subscription.cancel',
        summary: 'Cancel a subscription of the authenticated user',
        security: [['bearerAuth' => []]],
        tags: ['Subscription'],
        parameters: [
            new OA\Parameter(name: 'subscription', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
    )]
    #[OA\Response(response: 200, ref: '#/components/responses/SubscriptionItem')]
    #[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
    #[OA\Response(response: 403, ref: '#/components/responses/Forbidden')]
    #[OA\Response(response: 404, ref: '#/components/responses/NotFound')]
    #[OA\Response(response: 500, ref: '#/components/responses/ServerError')]
    public function cancel(Subscription $subscription): JsonResponse
    {
        $this->authorize('cancel', $subscription);

        $subscription = $this->service->cancel($subscription);

        return ApiResponse::fractal($subscription, $this->transformer, __('subscription::messages.canceled'));
    }
}
