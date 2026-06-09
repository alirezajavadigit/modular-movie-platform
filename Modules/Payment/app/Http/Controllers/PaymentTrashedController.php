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

class PaymentTrashedController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly PaymentServiceInterface $service,
        private readonly PaymentTransformer $transformer,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewTrashed', Payment::class);

        $perPage = (int) $request->input('per_page', 15);
        $items   = $this->service->getTrashed($perPage);

        return ApiResponse::paginated($items, $this->transformer, __('payment::messages.trashed'));
    }

    public function restore(int $id): JsonResponse
    {
        $this->authorize('restore', Payment::withTrashed()->findOrFail($id));

        $payment = $this->service->restore($id);

        return ApiResponse::fractal($payment, $this->transformer, __('payment::messages.restored'));
    }

    public function forceDelete(int $id): JsonResponse
    {
        $this->authorize('forceDelete', Payment::withTrashed()->findOrFail($id));

        $this->service->forceDelete($id);

        return ApiResponse::noContent(__('payment::messages.force_deleted'));
    }
}
