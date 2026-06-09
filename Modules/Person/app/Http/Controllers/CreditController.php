<?php

declare(strict_types=1);

namespace Modules\Person\Http\Controllers;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Person\Contracts\CreditServiceInterface;
use Modules\Person\Models\Credit;
use Modules\Person\DTOs\CreateCreditDTO;
use Modules\Person\DTOs\UpdateCreditDTO;
use Modules\Person\Http\Requests\StoreCreditRequest;
use Modules\Person\Http\Requests\UpdateCreditRequest;
use Modules\Person\Http\Resources\Transformers\CreditTransformer;

class CreditController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly CreditServiceInterface $service,
        private readonly CreditTransformer $transformer,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Credit::class);

        $personId = (int) $request->input('person_id', 0);
        $perPage  = (int) $request->input('per_page', 15);

        $credits = $personId > 0
            ? $this->service->getByPerson($personId, $perPage)
            : $this->service->getByRole((string) $request->input('role', 'actor'), $perPage);

        return ApiResponse::paginated($credits, $this->transformer, __('person::messages.credits_index'));
    }

    public function store(StoreCreditRequest $request): JsonResponse
    {
        $this->authorize('create', Credit::class);

        $data = $request->validated();

        $dto = new CreateCreditDTO(
            personId: (int) $data['person_id'],
            creditableType: (string) $data['creditable_type'],
            creditableId: (int) $data['creditable_id'],
            role: (string) $data['role'],
            characterName: $data['character_name'] ?? null,
            creditedAs: $data['credited_as'] ?? null,
            department: $data['department'] ?? null,
            order: (int) ($data['order'] ?? 0),
        );

        $credit = $this->service->store($dto);

        return ApiResponse::fractalCreated($credit, $this->transformer, __('person::messages.credit_created'));
    }

    public function show(int $id): JsonResponse
    {
        $credit = $this->service->findById($id);
        $this->authorize('view', $credit);

        return ApiResponse::fractal($credit, $this->transformer, __('person::messages.credit_show'));
    }

    public function update(UpdateCreditRequest $request, int $id): JsonResponse
    {
        $this->authorize('update', Credit::findOrFail($id));

        $data = $request->validated();

        $dto = new UpdateCreditDTO(
            role: $data['role'] ?? null,
            characterName: array_key_exists('character_name', $data) ? $data['character_name'] : null,
            creditedAs: array_key_exists('credited_as', $data) ? $data['credited_as'] : null,
            department: array_key_exists('department', $data) ? $data['department'] : null,
            order: isset($data['order']) ? (int) $data['order'] : null,
        );

        $credit = $this->service->update($id, $dto);

        return ApiResponse::fractal($credit, $this->transformer, __('person::messages.credit_updated'));
    }

    public function destroy(int $id): JsonResponse
    {
        $this->authorize('delete', Credit::findOrFail($id));

        $this->service->delete($id);

        return ApiResponse::noContent(__('person::messages.credit_deleted'));
    }
}
