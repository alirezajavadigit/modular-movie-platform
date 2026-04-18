<?php

declare(strict_types=1);

namespace Modules\Person\Http\Controllers;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Person\Contracts\PersonServiceInterface;
use Modules\Person\Http\Resources\Transformers\PersonTransformer;

class PersonTrashedController extends Controller
{
    public function __construct(
        private readonly PersonServiceInterface $service,
        private readonly PersonTransformer $transformer,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->input('per_page', 15);
        $persons = $this->service->getTrashed($perPage);

        return ApiResponse::paginated($persons, $this->transformer, __('person::messages.trashed'));
    }

    public function restore(int $id): JsonResponse
    {
        $person = $this->service->restore($id);

        return ApiResponse::fractal($person, $this->transformer, __('person::messages.restored'));
    }

    public function forceDelete(int $id): JsonResponse
    {
        $this->service->forceDelete($id);

        return ApiResponse::noContent(__('person::messages.force_deleted'));
    }
}
