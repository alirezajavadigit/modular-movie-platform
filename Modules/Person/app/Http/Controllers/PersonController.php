<?php

declare(strict_types=1);

namespace Modules\Person\Http\Controllers;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Person\Contracts\PersonServiceInterface;
use Modules\Person\DTOs\CreatePersonDTO;
use Modules\Person\DTOs\UpdatePersonDTO;
use Modules\Person\Http\Requests\StorePersonRequest;
use Modules\Person\Http\Requests\UpdatePersonRequest;
use Modules\Person\Http\Resources\Transformers\PersonTransformer;

class PersonController extends Controller
{
    public function __construct(
        private readonly PersonServiceInterface $service,
        private readonly PersonTransformer $transformer,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->input('per_page', 15);
        $persons = $this->service->paginate($perPage);

        return ApiResponse::paginated(
            $persons,
            $this->transformer,
            __('person::messages.index'),
        );
    }

    public function store(StorePersonRequest $request): JsonResponse
    {
        $data = $request->validated();

        $dto = new CreatePersonDTO(
            firstName: $data['first_name'],
            lastName: $data['last_name'],
            slug: $data['slug'],
            biography: $data['biography'] ?? null,
            imagePath: $data['image_path'] ?? null,
            dateOfBirth: $data['date_of_birth'] ?? null,
            dateOfDeath: $data['date_of_death'] ?? null,
            placeOfBirth: $data['place_of_birth'] ?? null,
            gender: $data['gender'] ?? null,
            knownForDepartment: $data['known_for_department'] ?? null,
            popularity: (float) ($data['popularity'] ?? 0),
            isActive: (bool) ($data['is_active'] ?? true),
        );

        $person = $this->service->store($dto);

        return ApiResponse::fractalCreated(
            $person,
            $this->transformer,
            __('person::messages.created'),
        );
    }

    public function show(int $id): JsonResponse
    {
        $person = $this->service->findById($id);

        return ApiResponse::fractal(
            $person,
            $this->transformer,
            __('person::messages.show'),
        );
    }

    public function update(UpdatePersonRequest $request, int $id): JsonResponse
    {
        $data = $request->validated();

        $dto = new UpdatePersonDTO(
            firstName: $data['first_name'] ?? null,
            lastName: $data['last_name'] ?? null,
            slug: $data['slug'] ?? null,
            biography: array_key_exists('biography', $data) ? $data['biography'] : null,
            imagePath: $data['image_path'] ?? null,
            dateOfBirth: $data['date_of_birth'] ?? null,
            dateOfDeath: $data['date_of_death'] ?? null,
            placeOfBirth: array_key_exists('place_of_birth', $data) ? $data['place_of_birth'] : null,
            gender: $data['gender'] ?? null,
            knownForDepartment: $data['known_for_department'] ?? null,
            popularity: isset($data['popularity']) ? (float) $data['popularity'] : null,
            isActive: isset($data['is_active']) ? (bool) $data['is_active'] : null,
        );

        $person = $this->service->update($id, $dto);

        return ApiResponse::fractal(
            $person,
            $this->transformer,
            __('person::messages.updated'),
        );
    }

    public function destroy(int $id): JsonResponse
    {
        $this->service->delete($id);

        return ApiResponse::noContent(__('person::messages.deleted'));
    }
}
