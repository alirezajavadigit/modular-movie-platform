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

class SubscriptionTrashedController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly SubscriptionServiceInterface $service,
        private readonly SubscriptionTransformer      $transformer,
    ) {}

    #[OA\Get(
        path: '/api/v1/admin/subscriptions/trashed',
        operationId: 'subscription.admin.trashed',
        summary: 'List soft-deleted subscriptions',
        security: [['bearerAuth' => []]],
        tags: ['Subscription'],
        parameters: [
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
        $this->authorize('viewTrashed', Subscription::class);

        $perPage       = (int) $request->input('per_page', 15);
        $subscriptions = $this->service->getTrashed($perPage);

        return ApiResponse::paginated($subscriptions, $this->transformer, __('subscription::messages.trashed'));
    }

    #[OA\Patch(
        path: '/api/v1/admin/subscriptions/{subscription}/restore',
        operationId: 'subscription.admin.restore',
        summary: 'Restore a soft-deleted subscription',
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
    public function restore(Subscription $subscription): JsonResponse
    {
        $this->authorize('restore', $subscription);

        $subscription = $this->service->restore($subscription);

        return ApiResponse::fractal($subscription, $this->transformer, __('subscription::messages.restored'));
    }

    #[OA\Delete(
        path: '/api/v1/admin/subscriptions/{subscription}/force-delete',
        operationId: 'subscription.admin.forceDelete',
        summary: 'Permanently delete a subscription',
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
    public function forceDelete(Subscription $subscription): JsonResponse
    {
        $this->authorize('forceDelete', $subscription);

        $this->service->forceDelete($subscription);

        return ApiResponse::noContent(__('subscription::messages.force_deleted'));
    }
}
