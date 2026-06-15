<?php

declare(strict_types=1);

namespace Modules\Subscription\Http\Controllers;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Modules\Subscription\Contracts\SubscriptionPlanServiceInterface;
use Modules\Subscription\Http\Resources\Transformers\SubscriptionPlanTransformer;
use Modules\Subscription\Models\SubscriptionPlan;
use OpenApi\Attributes as OA;

class SubscriptionPlanStatusController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly SubscriptionPlanServiceInterface $service,
        private readonly SubscriptionPlanTransformer      $transformer,
    ) {}

    #[OA\Patch(
        path: '/api/v1/admin/subscription-plans/{subscriptionPlan}/activate',
        operationId: 'subscriptionPlan.admin.activate',
        summary: 'Activate a subscription plan',
        security: [['bearerAuth' => []]],
        tags: ['Subscription'],
        parameters: [
            new OA\Parameter(name: 'subscriptionPlan', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
    )]
    #[OA\Response(response: 200, ref: '#/components/responses/SubscriptionPlanItem')]
    #[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
    #[OA\Response(response: 403, ref: '#/components/responses/Forbidden')]
    #[OA\Response(response: 404, ref: '#/components/responses/NotFound')]
    #[OA\Response(response: 500, ref: '#/components/responses/ServerError')]
    public function activate(SubscriptionPlan $subscriptionPlan): JsonResponse
    {
        $this->authorize('activate', $subscriptionPlan);

        $plan = $this->service->activate($subscriptionPlan);

        return ApiResponse::fractal($plan, $this->transformer, __('subscription::messages.plan_activated'));
    }

    #[OA\Patch(
        path: '/api/v1/admin/subscription-plans/{subscriptionPlan}/deactivate',
        operationId: 'subscriptionPlan.admin.deactivate',
        summary: 'Deactivate a subscription plan',
        security: [['bearerAuth' => []]],
        tags: ['Subscription'],
        parameters: [
            new OA\Parameter(name: 'subscriptionPlan', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
    )]
    #[OA\Response(response: 200, ref: '#/components/responses/SubscriptionPlanItem')]
    #[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
    #[OA\Response(response: 403, ref: '#/components/responses/Forbidden')]
    #[OA\Response(response: 404, ref: '#/components/responses/NotFound')]
    #[OA\Response(response: 500, ref: '#/components/responses/ServerError')]
    public function deactivate(SubscriptionPlan $subscriptionPlan): JsonResponse
    {
        $this->authorize('deactivate', $subscriptionPlan);

        $plan = $this->service->deactivate($subscriptionPlan);

        return ApiResponse::fractal($plan, $this->transformer, __('subscription::messages.plan_deactivated'));
    }
}
