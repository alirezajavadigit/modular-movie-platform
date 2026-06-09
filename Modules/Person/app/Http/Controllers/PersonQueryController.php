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

class PersonQueryController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly PersonServiceInterface $service,
        private readonly PersonTransformer $transformer,
    ) {}

    public function active(Request $request): JsonResponse
    {
        $perPage = (int) $request->input('per_page', 15);
        $persons = $this->service->getActive($perPage);

        return ApiResponse::paginated($persons, $this->transformer, __('person::messages.active_list'));
    }

    public function inactive(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Person::class);

        $perPage = (int) $request->input('per_page', 15);
        $persons = $this->service->getInactive($perPage);

        return ApiResponse::paginated($persons, $this->transformer, __('person::messages.inactive_list'));
    }

    public function popular(Request $request): JsonResponse
    {
        $limit = (int) $request->input('limit', 20);
        $persons = $this->service->getPopular($limit);

        return ApiResponse::fractalCollection($persons, $this->transformer, __('person::messages.popular'));
    }

    public function byDepartment(Request $request, string $department): JsonResponse
    {
        $perPage = (int) $request->input('per_page', 15);
        $persons = $this->service->getByDepartment($department, $perPage);

        return ApiResponse::paginated($persons, $this->transformer, __('person::messages.by_department'));
    }

    public function byGender(Request $request, string $gender): JsonResponse
    {
        $this->authorize('viewAny', Person::class);

        $perPage = (int) $request->input('per_page', 15);
        $persons = $this->service->getByGender($gender, $perPage);

        return ApiResponse::paginated($persons, $this->transformer, __('person::messages.by_gender'));
    }

    public function search(Request $request): JsonResponse
    {
        $q = (string) $request->input('q', '');
        $perPage = (int) $request->input('per_page', 15);
        $persons = $this->service->search($q, $perPage);

        return ApiResponse::paginated($persons, $this->transformer, __('person::messages.search'));
    }

    public function findBySlug(string $slug): JsonResponse
    {
        $person = $this->service->findBySlug($slug);

        return ApiResponse::fractal($person, $this->transformer, __('person::messages.show'));
    }
}
