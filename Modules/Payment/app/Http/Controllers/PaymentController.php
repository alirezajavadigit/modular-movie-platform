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

class PaymentController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly PaymentServiceInterface $service,
        private readonly PaymentTransformer $transformer,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Payment::class);

        $perPage = (int) $request->input('per_page', 15);
        $items   = $this->service->paginate($perPage);

        return ApiResponse::paginated($items, $this->transformer, __('payment::messages.index'));
    }

    public function show(int $id): JsonResponse
    {
        $payment = $this->service->findById($id);
        $this->authorize('view', $payment);

        return ApiResponse::fractal($payment, $this->transformer, __('payment::messages.show'));
    }

    public function destroy(int $id): JsonResponse
    {
        $this->authorize('delete', Payment::findOrFail($id));

        $this->service->delete($id);

        return ApiResponse::noContent(__('payment::messages.deleted'));
    }
}
