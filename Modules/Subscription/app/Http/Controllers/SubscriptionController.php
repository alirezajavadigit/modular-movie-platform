<?php

declare(strict_types=1);

namespace Modules\Subscription\Http\Controllers;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Subscription\Contracts\SubscriptionServiceInterface;
use Modules\Subscription\Http\Resources\Transformers\SubscriptionTransformer;
use Modules\Subscription\Models\Subscription;
use OpenApi\Attributes as OA;

class SubscriptionController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly SubscriptionServiceInterface $service,
        private readonly SubscriptionTransformer      $transformer,
    ) {}

    #[OA\Get(
        path: '/api/v1/admin/subscriptions',
        operationId: 'subscription.admin.index',
        summary: 'Admin: list all subscriptions with advanced filtering',
        security: [['bearerAuth' => []]],
        tags: ['Subscription'],
        parameters: [
            new OA\Parameter(ref: '#/components/parameters/SearchQuery'),
            new OA\Parameter(name: 'user_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'plan_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'status', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['active', 'canceled', 'pending', 'expired'])),
            new OA\Parameter(name: 'trashed', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['without', 'with', 'only'], default: 'without')),
            new OA\Parameter(ref: '#/components/parameters/Page'),
            new OA\Parameter(ref: '#/components/parameters/PerPage'),
        ],
    )]
    #[OA\Response(response: 200, ref: '#/components/responses/SubscriptionPage')]
    #[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
    #[OA\Response(response: 403, ref: '#/components/responses/Forbidden')]
    #[OA\Response(response: 500, ref: '#/components/responses/ServerError')]
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Subscription::class);

        $filters       = $request->only(['user_id', 'plan_id', 'status', 'trashed']);
        $perPage       = (int) $request->input('per_page', 15);
        $subscriptions = $this->service->adminFilter($filters, $perPage);

        return ApiResponse::paginated($subscriptions, $this->transformer, __('subscription::messages.index'));
    }

    #[OA\Get(
        path: '/api/v1/admin/subscriptions/{subscription}',
        operationId: 'subscription.admin.show',
        summary: 'Show a subscription',
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
    public function show(Subscription $subscription): JsonResponse
    {
        $this->authorize('view', $subscription);

        return ApiResponse::fractal($subscription, $this->transformer, __('subscription::messages.show'));
    }

    #[OA\Delete(
        path: '/api/v1/admin/subscriptions/{subscription}',
        operationId: 'subscription.admin.destroy',
        summary: 'Soft delete a subscription',
        security: [['bearerAuth' => []]],
        tags: ['Subscription'],
        parameters: [
            new OA\Parameter(name: 'subscription', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
    )]
    #[OA\Response(response: 204, ref: '#/components/responses/NoContent')]
    #[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
    #[OA\Response(response: 403, ref: '#/components/responses/Forbidden')]
    #[OA\Response(response: 404, ref: '#/components/responses/NotFound')]
    #[OA\Response(response: 500, ref: '#/components/responses/ServerError')]
    public function destroy(Subscription $subscription): JsonResponse
    {
        $this->authorize('delete', $subscription);

        $this->service->delete($subscription);

        return ApiResponse::noContent(__('subscription::messages.deleted'));
    }
}
