<?php

declare(strict_types=1);

namespace Modules\Payment\Http\Controllers;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Payment\Contracts\PaymentServiceInterface;
use Modules\Payment\Http\Resources\Transformers\PaymentTransformer;
use Modules\Payment\Models\Payment;
use OpenApi\Attributes as OA;

class PaymentTrashedController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly PaymentServiceInterface $service,
        private readonly PaymentTransformer $transformer,
    ) {}

    #[OA\Get(
        path: '/api/v1/admin/payments/trashed',
        operationId: 'payment.admin.trashed',
        summary: 'List soft-deleted payments',
        security: [['bearerAuth' => []]],
        tags: ['Payment'],
        parameters: [
            new OA\Parameter(ref: '#/components/parameters/Page'),
            new OA\Parameter(ref: '#/components/parameters/PerPage'),
        ],
    )]
    #[OA\Response(response: 200, ref: '#/components/responses/PaymentPage')]
    #[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
    #[OA\Response(response: 403, ref: '#/components/responses/Forbidden')]
    #[OA\Response(response: 500, ref: '#/components/responses/ServerError')]
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewTrashed', Payment::class);

        $perPage = (int) $request->input('per_page', 15);
        $items   = $this->service->getTrashed($perPage);

        return ApiResponse::paginated($items, $this->transformer, __('payment::messages.trashed'));
    }

    #[OA\Patch(
        path: '/api/v1/admin/payments/{payment}/restore',
        operationId: 'payment.admin.restore',
        summary: 'Restore a soft-deleted payment',
        security: [['bearerAuth' => []]],
        tags: ['Payment'],
        parameters: [
            new OA\Parameter(name: 'payment', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
    )]
    #[OA\Response(response: 200, ref: '#/components/responses/PaymentItem')]
    #[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
    #[OA\Response(response: 403, ref: '#/components/responses/Forbidden')]
    #[OA\Response(response: 404, ref: '#/components/responses/NotFound')]
    #[OA\Response(response: 500, ref: '#/components/responses/ServerError')]
    public function restore(int $id): JsonResponse
    {
        $this->authorize('restore', Payment::withTrashed()->findOrFail($id));

        $payment = $this->service->restore($id);

        return ApiResponse::fractal($payment, $this->transformer, __('payment::messages.restored'));
    }

    #[OA\Delete(
        path: '/api/v1/admin/payments/{payment}/force-delete',
        operationId: 'payment.admin.forceDelete',
        summary: 'Permanently delete a payment',
        security: [['bearerAuth' => []]],
        tags: ['Payment'],
        parameters: [
            new OA\Parameter(name: 'payment', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
    )]
    #[OA\Response(response: 204, ref: '#/components/responses/NoContent')]
    #[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
    #[OA\Response(response: 403, ref: '#/components/responses/Forbidden')]
    #[OA\Response(response: 404, ref: '#/components/responses/NotFound')]
    #[OA\Response(response: 500, ref: '#/components/responses/ServerError')]
    public function forceDelete(int $id): JsonResponse
    {
        $this->authorize('forceDelete', Payment::withTrashed()->findOrFail($id));

        $this->service->forceDelete($id);

        return ApiResponse::noContent(__('payment::messages.force_deleted'));
    }
}
