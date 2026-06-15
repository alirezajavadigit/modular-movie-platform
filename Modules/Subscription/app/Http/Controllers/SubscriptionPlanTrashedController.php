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

class SubscriptionPlanTrashedController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly SubscriptionPlanServiceInterface $service,
        private readonly SubscriptionPlanTransformer      $transformer,
    ) {}

    #[OA\Get(
        path: '/api/v1/admin/subscription-plans/trashed',
        operationId: 'subscriptionPlan.admin.trashed',
        summary: 'List soft-deleted subscription plans',
        security: [['bearerAuth' => []]],
        tags: ['Subscription'],
        parameters: [
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
        $this->authorize('viewTrashed', SubscriptionPlan::class);

        $perPage = (int) $request->input('per_page', 15);
        $plans   = $this->service->getTrashed($perPage);

        return ApiResponse::paginated($plans, $this->transformer, __('subscription::messages.plans_trashed'));
    }

    #[OA\Patch(
        path: '/api/v1/admin/subscription-plans/{subscriptionPlan}/restore',
        operationId: 'subscriptionPlan.admin.restore',
        summary: 'Restore a soft-deleted subscription plan',
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
    public function restore(SubscriptionPlan $subscriptionPlan): JsonResponse
    {
        $this->authorize('restore', $subscriptionPlan);

        $plan = $this->service->restore($subscriptionPlan);

        return ApiResponse::fractal($plan, $this->transformer, __('subscription::messages.plan_restored'));
    }

    #[OA\Delete(
        path: '/api/v1/admin/subscription-plans/{subscriptionPlan}/force-delete',
        operationId: 'subscriptionPlan.admin.forceDelete',
        summary: 'Permanently delete a subscription plan',
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
    public function forceDelete(SubscriptionPlan $subscriptionPlan): JsonResponse
    {
        $this->authorize('forceDelete', $subscriptionPlan);

        $this->service->forceDelete($subscriptionPlan);

        return ApiResponse::noContent(__('subscription::messages.plan_force_deleted'));
    }
}
