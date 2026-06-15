<?php

declare(strict_types=1);

namespace Modules\Subscription\Http\Controllers;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Modules\Subscription\Contracts\SubscriptionPlanServiceInterface;
use Modules\Subscription\DTOs\CreateSubscriptionPlanDTO;
use Modules\Subscription\DTOs\UpdateSubscriptionPlanDTO;
use Modules\Subscription\Http\Requests\StoreSubscriptionPlanRequest;
use Modules\Subscription\Http\Requests\UpdateSubscriptionPlanRequest;
use Modules\Subscription\Http\Resources\Transformers\SubscriptionPlanTransformer;
use Modules\Subscription\Models\SubscriptionPlan;
use OpenApi\Attributes as OA;

class SubscriptionPlanController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly SubscriptionPlanServiceInterface $service,
        private readonly SubscriptionPlanTransformer      $transformer,
    ) {}

    #[OA\Post(
        path: '/api/v1/admin/subscription-plans',
        operationId: 'subscriptionPlan.admin.store',
        summary: 'Create a subscription plan',
        security: [['bearerAuth' => []]],
        tags: ['Subscription'],
        requestBody: new OA\RequestBody(ref: '#/components/requestBodies/StoreSubscriptionPlanRequest'),
    )]
    #[OA\Response(response: 201, ref: '#/components/responses/SubscriptionPlanCreated')]
    #[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
    #[OA\Response(response: 403, ref: '#/components/responses/Forbidden')]
    #[OA\Response(response: 422, ref: '#/components/responses/ValidationError')]
    #[OA\Response(response: 500, ref: '#/components/responses/ServerError')]
    public function store(StoreSubscriptionPlanRequest $request): JsonResponse
    {
        $this->authorize('create', SubscriptionPlan::class);

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

    #[OA\Put(
        path: '/api/v1/admin/subscription-plans/{subscriptionPlan}',
        operationId: 'subscriptionPlan.admin.update',
        summary: 'Update a subscription plan',
        security: [['bearerAuth' => []]],
        tags: ['Subscription'],
        requestBody: new OA\RequestBody(ref: '#/components/requestBodies/UpdateSubscriptionPlanRequest'),
        parameters: [
            new OA\Parameter(name: 'subscriptionPlan', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
    )]
    #[OA\Response(response: 200, ref: '#/components/responses/SubscriptionPlanItem')]
    #[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
    #[OA\Response(response: 403, ref: '#/components/responses/Forbidden')]
    #[OA\Response(response: 404, ref: '#/components/responses/NotFound')]
    #[OA\Response(response: 422, ref: '#/components/responses/ValidationError')]
    #[OA\Response(response: 500, ref: '#/components/responses/ServerError')]
    public function update(UpdateSubscriptionPlanRequest $request, SubscriptionPlan $subscriptionPlan): JsonResponse
    {
        $this->authorize('update', $subscriptionPlan);

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

    #[OA\Delete(
        path: '/api/v1/admin/subscription-plans/{subscriptionPlan}',
        operationId: 'subscriptionPlan.admin.destroy',
        summary: 'Soft delete a subscription plan',
        security: [['bearerAuth' => []]],
        tags: ['Subscription'],
        parameters: [
            new OA\Parameter(name: 'subscriptionPlan', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
    )]
    #[OA\Response(response: 204, ref: '#/components/responses/NoContent')]
    #[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
    #[OA\Response(response: 403, ref: '#/components/responses/Forbidden')]
    #[OA\Response(response: 404, ref: '#/components/responses/NotFound')]
    #[OA\Response(response: 500, ref: '#/components/responses/ServerError')]
    public function destroy(SubscriptionPlan $subscriptionPlan): JsonResponse
    {
        $this->authorize('delete', $subscriptionPlan);

        $this->service->delete($subscriptionPlan);

        return ApiResponse::noContent(__('subscription::messages.plan_deleted'));
    }
}
