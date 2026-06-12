<?php

declare(strict_types=1);

namespace Modules\Person\Http\Controllers;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Person\Contracts\PersonServiceInterface;
use Modules\Person\Models\Person;
use Modules\Person\DTOs\CreatePersonDTO;
use Modules\Person\DTOs\UpdatePersonDTO;
use Modules\Person\Http\Requests\StorePersonRequest;
use Modules\Person\Http\Requests\UpdatePersonRequest;
use Modules\Person\Http\Requests\UploadPersonImageRequest;
use Modules\Person\Http\Resources\Transformers\PersonTransformer;
use OpenApi\Attributes as OA;

class PersonController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly PersonServiceInterface $service,
        private readonly PersonTransformer $transformer,
    ) {}

    #[OA\Get(
        path: '/api/v1/admin/persons',
        operationId: 'person.admin.index',
        summary: 'List all persons with advanced filtering',
        security: [['bearerAuth' => []]],
        tags: ['Person'],
        parameters: [
            new OA\Parameter(ref: '#/components/parameters/SearchQuery'),
            new OA\Parameter(name: 'gender', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['male', 'female', 'other'])),
            new OA\Parameter(name: 'department', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'is_active', in: 'query', required: false, schema: new OA\Schema(type: 'integer', enum: [0, 1])),
            new OA\Parameter(name: 'trashed', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['without', 'with', 'only'], default: 'without')),
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
        $this->authorize('viewAny', Person::class);

        $filters = $request->only(['q', 'gender', 'department', 'is_active', 'trashed']);
        $perPage = (int) $request->input('per_page', 15);

        $persons = $this->service->adminFilter($filters, $perPage);

        return ApiResponse::paginated(
            $persons,
            $this->transformer,
            __('person::messages.index'),
        );
    }

    #[OA\Post(
        path: '/api/v1/admin/persons',
        operationId: 'person.admin.store',
        summary: 'Create a person',
        security: [['bearerAuth' => []]],
        tags: ['Person'],
        requestBody: new OA\RequestBody(ref: '#/components/requestBodies/StorePersonRequest'),
    )]
    #[OA\Response(response: 201, ref: '#/components/responses/PersonCreated')]
    #[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
    #[OA\Response(response: 403, ref: '#/components/responses/Forbidden')]
    #[OA\Response(response: 422, ref: '#/components/responses/ValidationError')]
    #[OA\Response(response: 500, ref: '#/components/responses/ServerError')]
    public function store(StorePersonRequest $request): JsonResponse
    {
        $this->authorize('create', Person::class);

        $data = $request->validated();

        $dto = new CreatePersonDTO(
            firstName: $data['first_name'],
            lastName: $data['last_name'],
            slug: $data['slug'],
            biography: $data['biography'] ?? null,
            dateOfBirth: $data['date_of_birth'] ?? null,
            dateOfDeath: $data['date_of_death'] ?? null,
            placeOfBirth: $data['place_of_birth'] ?? null,
            gender: $data['gender'] ?? null,
            knownForDepartment: $data['known_for_department'] ?? null,
            popularity: (float) ($data['popularity'] ?? 0),
            isActive: (bool) ($data['is_active'] ?? true),
        );

        $person = $this->service->store($dto, $request->file('image'));

        return ApiResponse::fractalCreated(
            $person,
            $this->transformer,
            __('person::messages.created'),
        );
    }

    #[OA\Get(
        path: '/api/v1/admin/persons/{person}',
        operationId: 'person.admin.show',
        summary: 'Show a person',
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
    public function show(int $id): JsonResponse
    {
        $person = $this->service->findById($id);
        $this->authorize('view', $person);

        return ApiResponse::fractal(
            $person,
            $this->transformer,
            __('person::messages.show'),
        );
    }

    #[OA\Put(
        path: '/api/v1/admin/persons/{person}',
        operationId: 'person.admin.update',
        summary: 'Update a person',
        security: [['bearerAuth' => []]],
        tags: ['Person'],
        requestBody: new OA\RequestBody(ref: '#/components/requestBodies/UpdatePersonRequest'),
        parameters: [
            new OA\Parameter(name: 'person', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
    )]
    #[OA\Response(response: 200, ref: '#/components/responses/PersonItem')]
    #[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
    #[OA\Response(response: 403, ref: '#/components/responses/Forbidden')]
    #[OA\Response(response: 404, ref: '#/components/responses/NotFound')]
    #[OA\Response(response: 422, ref: '#/components/responses/ValidationError')]
    #[OA\Response(response: 500, ref: '#/components/responses/ServerError')]
    public function update(UpdatePersonRequest $request, int $id): JsonResponse
    {
        $this->authorize('update', Person::findOrFail($id));

        $data = $request->validated();

        $dto = new UpdatePersonDTO(
            firstName: $data['first_name'] ?? null,
            lastName: $data['last_name'] ?? null,
            slug: $data['slug'] ?? null,
            biography: array_key_exists('biography', $data) ? $data['biography'] : null,
            dateOfBirth: $data['date_of_birth'] ?? null,
            dateOfDeath: $data['date_of_death'] ?? null,
            placeOfBirth: array_key_exists('place_of_birth', $data) ? $data['place_of_birth'] : null,
            gender: $data['gender'] ?? null,
            knownForDepartment: $data['known_for_department'] ?? null,
            popularity: isset($data['popularity']) ? (float) $data['popularity'] : null,
            isActive: isset($data['is_active']) ? (bool) $data['is_active'] : null,
        );

        $person = $this->service->update($id, $dto, $request->file('image'));

        return ApiResponse::fractal(
            $person,
            $this->transformer,
            __('person::messages.updated'),
        );
    }

    #[OA\Delete(
        path: '/api/v1/admin/persons/{person}',
        operationId: 'person.admin.destroy',
        summary: 'Soft delete a person',
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
    public function destroy(int $id): JsonResponse
    {
        $this->authorize('delete', Person::findOrFail($id));

        $this->service->delete($id);

        return ApiResponse::noContent(__('person::messages.deleted'));
    }

    #[OA\Post(
        path: '/api/v1/admin/persons/{person}/image',
        operationId: 'person.admin.uploadImage',
        summary: 'Upload or replace the avatar of a person',
        security: [['bearerAuth' => []]],
        tags: ['Person'],
        requestBody: new OA\RequestBody(ref: '#/components/requestBodies/UploadPersonImageRequest'),
        parameters: [
            new OA\Parameter(name: 'person', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
    )]
    #[OA\Response(response: 200, ref: '#/components/responses/PersonItem')]
    #[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
    #[OA\Response(response: 403, ref: '#/components/responses/Forbidden')]
    #[OA\Response(response: 404, ref: '#/components/responses/NotFound')]
    #[OA\Response(response: 422, ref: '#/components/responses/ValidationError')]
    #[OA\Response(response: 500, ref: '#/components/responses/ServerError')]
    public function uploadImage(UploadPersonImageRequest $request, int $id): JsonResponse
    {
        $person = Person::findOrFail($id);
        $this->authorize('update', $person);

        $person = $this->service->setImage($id, $request->file('image'));

        return ApiResponse::fractal(
            $person,
            $this->transformer,
            __('person::messages.image_updated'),
        );
    }

    #[OA\Delete(
        path: '/api/v1/admin/persons/{person}/image',
        operationId: 'person.admin.deleteImage',
        summary: 'Remove the avatar of a person',
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
    public function deleteImage(int $id): JsonResponse
    {
        $person = Person::findOrFail($id);
        $this->authorize('update', $person);

        $person = $this->service->removeImage($id);

        return ApiResponse::fractal(
            $person,
            $this->transformer,
            __('person::messages.image_removed'),
        );
    }
}
