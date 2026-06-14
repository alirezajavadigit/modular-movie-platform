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

class PaymentController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly PaymentServiceInterface $service,
        private readonly PaymentTransformer $transformer,
    ) {}

    #[OA\Get(
        path: '/api/v1/admin/payments',
        operationId: 'payment.admin.index',
        summary: 'List all payments',
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
        $this->authorize('viewAny', Payment::class);

        $perPage = (int) $request->input('per_page', 15);
        $items   = $this->service->paginate($perPage);

        return ApiResponse::paginated($items, $this->transformer, __('payment::messages.index'));
    }

    #[OA\Get(
        path: '/api/v1/admin/payments/{payment}',
        operationId: 'payment.admin.show',
        summary: 'Show a payment',
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
    public function show(int $id): JsonResponse
    {
        $payment = $this->service->findById($id);
        $this->authorize('view', $payment);

        return ApiResponse::fractal($payment, $this->transformer, __('payment::messages.show'));
    }

    #[OA\Delete(
        path: '/api/v1/admin/payments/{payment}',
        operationId: 'payment.admin.destroy',
        summary: 'Soft delete a payment',
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
    public function destroy(int $id): JsonResponse
    {
        $this->authorize('delete', Payment::findOrFail($id));

        $this->service->delete($id);

        return ApiResponse::noContent(__('payment::messages.deleted'));
    }
}
