<?php

declare(strict_types=1);

namespace Modules\Person\Http\Controllers;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Person\Contracts\PersonServiceInterface;
use Modules\Person\Http\Resources\Transformers\PersonTransformer;
use Modules\Person\Models\Person;
use OpenApi\Attributes as OA;

class PersonTrashedController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly PersonServiceInterface $service,
        private readonly PersonTransformer $transformer,
    ) {}

    #[OA\Get(
        path: '/api/v1/admin/persons/trashed',
        operationId: 'person.admin.trashed',
        summary: 'List soft-deleted persons',
        security: [['bearerAuth' => []]],
        tags: ['Person'],
        parameters: [
            new OA\Parameter(ref: '#/components/parameters/Page'),
            new OA\Parameter(ref: '#/components/parameters/PerPage'),
        ],
    )]
    #[OA\Response(response: 200, ref: '#/components/responses/PersonPage')]
    #[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
    #[OA\Response(response: 403, ref: '#/components/responses/Forbidden')]
    #[OA\Response(response: 500, ref: '#/components/responses/ServerError')]
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewTrashed', Person::class);

        $perPage = (int) $request->input('per_page', 15);
        $persons = $this->service->getTrashed($perPage);

        return ApiResponse::paginated($persons, $this->transformer, __('person::messages.trashed'));
    }

    #[OA\Patch(
        path: '/api/v1/admin/persons/{person}/restore',
        operationId: 'person.admin.restore',
        summary: 'Restore a soft-deleted person',
        security: [['bearerAuth' => []]],
        tags: ['Person'],
        parameters: [
            new OA\Parameter(name: 'person', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
    )]
    #[OA\Response(response: 200, ref: '#/components/responses/PersonItem')]
    #[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
    #[OA\Response(response: 403, ref: '#/components/responses/Forbidden')]
    #[OA\Response(response: 404, ref: '#/components/responses/NotFound')]
    #[OA\Response(response: 500, ref: '#/components/responses/ServerError')]
    public function restore(int $id): JsonResponse
    {
        $this->authorize('restore', Person::withTrashed()->findOrFail($id));

        $person = $this->service->restore($id);

        return ApiResponse::fractal($person, $this->transformer, __('person::messages.restored'));
    }

    #[OA\Delete(
        path: '/api/v1/admin/persons/{person}/force-delete',
        operationId: 'person.admin.forceDelete',
        summary: 'Permanently delete a person',
        security: [['bearerAuth' => []]],
        tags: ['Person'],
        parameters: [
            new OA\Parameter(name: 'person', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
    )]
    #[OA\Response(response: 204, ref: '#/components/responses/NoContent')]
    #[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
    #[OA\Response(response: 403, ref: '#/components/responses/Forbidden')]
    #[OA\Response(response: 404, ref: '#/components/responses/NotFound')]
    #[OA\Response(response: 500, ref: '#/components/responses/ServerError')]
    public function forceDelete(int $id): JsonResponse
    {
        $this->authorize('forceDelete', Person::withTrashed()->findOrFail($id));

        $this->service->forceDelete($id);

        return ApiResponse::noContent(__('person::messages.force_deleted'));
    }
}
