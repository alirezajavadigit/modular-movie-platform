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

class PersonQueryController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly PersonServiceInterface $service,
        private readonly PersonTransformer $transformer,
    ) {}

    #[OA\Get(
        path: '/api/v1/persons/active',
        operationId: 'person.public.active',
        summary: 'List active persons',
        tags: ['Person'],
        parameters: [
            new OA\Parameter(ref: '#/components/parameters/Page'),
            new OA\Parameter(ref: '#/components/parameters/PerPage'),
        ],
        responses: [
            new OA\Response(response: 200, ref: '#/components/responses/PersonPage'),
            new OA\Response(response: 500, ref: '#/components/responses/ServerError'),
        ],
    )]
    #[OA\Get(
        path: '/api/v1/admin/persons/active',
        operationId: 'person.admin.active',
        summary: 'List active persons',
        security: [['bearerAuth' => []]],
        tags: ['Person'],
        parameters: [
            new OA\Parameter(ref: '#/components/parameters/Page'),
            new OA\Parameter(ref: '#/components/parameters/PerPage'),
        ],
        responses: [
            new OA\Response(response: 200, ref: '#/components/responses/PersonPage'),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
            new OA\Response(response: 500, ref: '#/components/responses/ServerError'),
        ],
    )]
    public function active(Request $request): JsonResponse
    {
        $perPage = (int) $request->input('per_page', 15);
        $persons = $this->service->getActive($perPage);

        return ApiResponse::paginated($persons, $this->transformer, __('person::messages.active_list'));
    }

    #[OA\Get(
        path: '/api/v1/admin/persons/inactive',
        operationId: 'person.admin.inactive',
        summary: 'List inactive persons',
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
    public function inactive(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Person::class);

        $perPage = (int) $request->input('per_page', 15);
        $persons = $this->service->getInactive($perPage);

        return ApiResponse::paginated($persons, $this->transformer, __('person::messages.inactive_list'));
    }

    #[OA\Get(
        path: '/api/v1/persons/popular',
        operationId: 'person.public.popular',
        summary: 'List the most popular persons',
        tags: ['Person'],
        parameters: [
            new OA\Parameter(name: 'limit', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 20)),
        ],
    )]
    #[OA\Response(response: 200, ref: '#/components/responses/PersonCollection')]
    #[OA\Response(response: 500, ref: '#/components/responses/ServerError')]
    public function popular(Request $request): JsonResponse
    {
        $limit = (int) $request->input('limit', 20);
        $persons = $this->service->getPopular($limit);

        return ApiResponse::fractalCollection($persons, $this->transformer, __('person::messages.popular'));
    }

    #[OA\Get(
        path: '/api/v1/persons/department/{department}',
        operationId: 'person.public.byDepartment',
        summary: 'List persons known for a department',
        tags: ['Person'],
        parameters: [
            new OA\Parameter(name: 'department', in: 'path', required: true, schema: new OA\Schema(type: 'string'), example: 'Acting'),
            new OA\Parameter(ref: '#/components/parameters/Page'),
            new OA\Parameter(ref: '#/components/parameters/PerPage'),
        ],
    )]
    #[OA\Response(response: 200, ref: '#/components/responses/PersonPage')]
    #[OA\Response(response: 500, ref: '#/components/responses/ServerError')]
    public function byDepartment(Request $request, string $department): JsonResponse
    {
        $perPage = (int) $request->input('per_page', 15);
        $persons = $this->service->getByDepartment($department, $perPage);

        return ApiResponse::paginated($persons, $this->transformer, __('person::messages.by_department'));
    }

    #[OA\Get(
        path: '/api/v1/admin/persons/gender/{gender}',
        operationId: 'person.admin.byGender',
        summary: 'List persons by gender',
        security: [['bearerAuth' => []]],
        tags: ['Person'],
        parameters: [
            new OA\Parameter(name: 'gender', in: 'path', required: true, schema: new OA\Schema(type: 'string', enum: ['male', 'female', 'non_binary', 'undisclosed'])),
            new OA\Parameter(ref: '#/components/parameters/Page'),
            new OA\Parameter(ref: '#/components/parameters/PerPage'),
        ],
    )]
    #[OA\Response(response: 200, ref: '#/components/responses/PersonPage')]
    #[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
    #[OA\Response(response: 403, ref: '#/components/responses/Forbidden')]
    #[OA\Response(response: 500, ref: '#/components/responses/ServerError')]
    public function byGender(Request $request, string $gender): JsonResponse
    {
        $this->authorize('viewAny', Person::class);

        $perPage = (int) $request->input('per_page', 15);
        $persons = $this->service->getByGender($gender, $perPage);

        return ApiResponse::paginated($persons, $this->transformer, __('person::messages.by_gender'));
    }

    #[OA\Get(
        path: '/api/v1/persons/search',
        operationId: 'person.public.search',
        summary: 'Search active persons',
        tags: ['Person'],
        parameters: [
            new OA\Parameter(ref: '#/components/parameters/SearchQuery'),
            new OA\Parameter(ref: '#/components/parameters/Page'),
            new OA\Parameter(ref: '#/components/parameters/PerPage'),
        ],
    )]
    #[OA\Response(response: 200, ref: '#/components/responses/PersonPage')]
    #[OA\Response(response: 500, ref: '#/components/responses/ServerError')]
    public function search(Request $request): JsonResponse
    {
        $q = (string) $request->input('q', '');
        $perPage = (int) $request->input('per_page', 15);
        $persons = $this->service->search($q, $perPage);

        return ApiResponse::paginated($persons, $this->transformer, __('person::messages.search'));
    }

    #[OA\Get(
        path: '/api/v1/persons/slug/{slug}',
        operationId: 'person.public.bySlug',
        summary: 'Show a person by slug',
        tags: ['Person'],
        parameters: [
            new OA\Parameter(name: 'slug', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
        ],
    )]
    #[OA\Response(response: 200, ref: '#/components/responses/PersonItem')]
    #[OA\Response(response: 404, ref: '#/components/responses/NotFound')]
    #[OA\Response(response: 500, ref: '#/components/responses/ServerError')]
    public function findBySlug(string $slug): JsonResponse
    {
        $person = $this->service->findBySlug($slug);

        return ApiResponse::fractal($person, $this->transformer, __('person::messages.show'));
    }
}
