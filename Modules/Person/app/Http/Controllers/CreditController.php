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
use OpenApi\Attributes as OA;

class CreditController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly CreditServiceInterface $service,
        private readonly CreditTransformer $transformer,
    ) {}

    #[OA\Get(
        path: '/api/v1/admin/credits',
        operationId: 'credit.admin.index',
        summary: 'List credits filtered by person or role',
        security: [['bearerAuth' => []]],
        tags: ['Person'],
        parameters: [
            new OA\Parameter(name: 'person_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer'), description: 'When present and positive, filters by person; otherwise role filtering applies'),
            new OA\Parameter(name: 'role', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['actor', 'director', 'writer', 'producer', 'executive_producer', 'composer', 'cinematographer', 'editor', 'crew', 'guest', 'narrator', 'other'], default: 'actor')),
            new OA\Parameter(ref: '#/components/parameters/Page'),
            new OA\Parameter(ref: '#/components/parameters/PerPage'),
        ],
    )]
    #[OA\Response(response: 200, ref: '#/components/responses/CreditPage')]
    #[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
    #[OA\Response(response: 403, ref: '#/components/responses/Forbidden')]
    #[OA\Response(response: 500, ref: '#/components/responses/ServerError')]
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

    #[OA\Post(
        path: '/api/v1/admin/credits',
        operationId: 'credit.admin.store',
        summary: 'Create a credit',
        security: [['bearerAuth' => []]],
        tags: ['Person'],
        requestBody: new OA\RequestBody(ref: '#/components/requestBodies/StoreCreditRequest'),
    )]
    #[OA\Response(response: 201, ref: '#/components/responses/CreditCreated')]
    #[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
    #[OA\Response(response: 403, ref: '#/components/responses/Forbidden')]
    #[OA\Response(response: 422, ref: '#/components/responses/ValidationError')]
    #[OA\Response(response: 500, ref: '#/components/responses/ServerError')]
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

    #[OA\Get(
        path: '/api/v1/admin/credits/{credit}',
        operationId: 'credit.admin.show',
        summary: 'Show a credit',
        security: [['bearerAuth' => []]],
        tags: ['Person'],
        parameters: [
            new OA\Parameter(name: 'credit', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
    )]
    #[OA\Response(response: 200, ref: '#/components/responses/CreditItem')]
    #[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
    #[OA\Response(response: 403, ref: '#/components/responses/Forbidden')]
    #[OA\Response(response: 404, ref: '#/components/responses/NotFound')]
    #[OA\Response(response: 500, ref: '#/components/responses/ServerError')]
    public function show(int $id): JsonResponse
    {
        $credit = $this->service->findById($id);
        $this->authorize('view', $credit);

        return ApiResponse::fractal($credit, $this->transformer, __('person::messages.credit_show'));
    }

    #[OA\Put(
        path: '/api/v1/admin/credits/{credit}',
        operationId: 'credit.admin.update',
        summary: 'Update a credit',
        security: [['bearerAuth' => []]],
        tags: ['Person'],
        requestBody: new OA\RequestBody(ref: '#/components/requestBodies/UpdateCreditRequest'),
        parameters: [
            new OA\Parameter(name: 'credit', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
    )]
    #[OA\Response(response: 200, ref: '#/components/responses/CreditItem')]
    #[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
    #[OA\Response(response: 403, ref: '#/components/responses/Forbidden')]
    #[OA\Response(response: 404, ref: '#/components/responses/NotFound')]
    #[OA\Response(response: 422, ref: '#/components/responses/ValidationError')]
    #[OA\Response(response: 500, ref: '#/components/responses/ServerError')]
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

    #[OA\Delete(
        path: '/api/v1/admin/credits/{credit}',
        operationId: 'credit.admin.destroy',
        summary: 'Delete a credit',
        security: [['bearerAuth' => []]],
        tags: ['Person'],
        parameters: [
            new OA\Parameter(name: 'credit', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
    )]
    #[OA\Response(response: 204, ref: '#/components/responses/NoContent')]
    #[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
    #[OA\Response(response: 403, ref: '#/components/responses/Forbidden')]
    #[OA\Response(response: 404, ref: '#/components/responses/NotFound')]
    #[OA\Response(response: 500, ref: '#/components/responses/ServerError')]
    public function destroy(int $id): JsonResponse
    {
        $this->authorize('delete', Credit::findOrFail($id));

        $this->service->delete($id);

        return ApiResponse::noContent(__('person::messages.credit_deleted'));
    }
}
