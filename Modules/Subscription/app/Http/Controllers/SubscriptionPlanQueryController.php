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
use OpenApi\Attributes as OA;

class SubscriptionPlanQueryController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly SubscriptionPlanServiceInterface $service,
        private readonly SubscriptionPlanTransformer      $transformer,
    ) {}

    #[OA\Get(
        path: '/api/v1/admin/subscription-plans',
        operationId: 'subscriptionPlan.admin.index',
        summary: 'Admin: list all subscription plans with advanced filtering',
        security: [['bearerAuth' => []]],
        tags: ['Subscription'],
        parameters: [
            new OA\Parameter(name: 'status', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['active', 'inactive'])),
            new OA\Parameter(name: 'trashed', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['without', 'with', 'only'], default: 'without')),
            new OA\Parameter(ref: '#/components/parameters/Page'),
            new OA\Parameter(ref: '#/components/parameters/PerPage'),
        ],
    )]
    #[OA\Response(response: 200, ref: '#/components/responses/SubscriptionPlanPage')]
    #[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
    #[OA\Response(response: 403, ref: '#/components/responses/Forbidden')]
    #[OA\Response(response: 500, ref: '#/components/responses/ServerError')]
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', SubscriptionPlan::class);

        $filters = $request->only(['status', 'trashed']);
        $perPage = (int) $request->input('per_page', 15);
        $plans   = $this->service->adminFilter($filters, $perPage);

        return ApiResponse::paginated($plans, $this->transformer, __('subscription::messages.plans_index'));
    }

    #[OA\Get(
        path: '/api/v1/subscription-plans',
        operationId: 'subscriptionPlan.public.index',
        summary: 'List active subscription plans',
        tags: ['Subscription'],
        parameters: [
            new OA\Parameter(ref: '#/components/parameters/Page'),
            new OA\Parameter(ref: '#/components/parameters/PerPage'),
        ],
    )]
    #[OA\Response(response: 200, ref: '#/components/responses/SubscriptionPlanPage')]
    #[OA\Response(response: 500, ref: '#/components/responses/ServerError')]
    public function publicIndex(Request $request): JsonResponse
    {
        $perPage = (int) $request->input('per_page', 15);
        $plans   = $this->service->getActivePaginate($perPage);

        return ApiResponse::paginated($plans, $this->transformer, __('subscription::messages.plans_index'));
    }

    #[OA\Get(
        path: '/api/v1/subscription-plans/{subscriptionPlan}',
        operationId: 'subscriptionPlan.public.show',
        summary: 'Show a subscription plan',
        tags: ['Subscription'],
        parameters: [
            new OA\Parameter(name: 'subscriptionPlan', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
    )]
    #[OA\Response(response: 200, ref: '#/components/responses/SubscriptionPlanItem')]
    #[OA\Response(response: 404, ref: '#/components/responses/NotFound')]
    #[OA\Response(response: 500, ref: '#/components/responses/ServerError')]
    public function publicShow(SubscriptionPlan $subscriptionPlan): JsonResponse
    {
        return ApiResponse::fractal($subscriptionPlan, $this->transformer, __('subscription::messages.plan_show'));
    }
}
