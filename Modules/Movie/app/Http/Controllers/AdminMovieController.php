<?php

declare(strict_types=1);

namespace Modules\Movie\Http\Controllers;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Movie\Contracts\MovieServiceInterface;
use Modules\Movie\Http\Resources\Transformers\MovieTransformer;
use Modules\Movie\Models\Movie;
use OpenApi\Attributes as OA;

class AdminMovieController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly MovieServiceInterface $movieService,
    ) {}

    #[OA\Get(
        path: '/api/v1/admin/movies',
        operationId: 'movie.admin.index',
        summary: 'Admin: list movies with advanced filtering',
        security: [['bearerAuth' => []]],
        tags: ['Movie'],
        parameters: [
            new OA\Parameter(name: 'q', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'type', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['movie', 'serial'])),
            new OA\Parameter(name: 'badge', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['dubbed', 'subtitled', 'animation'])),
            new OA\Parameter(name: 'year_from', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'year_to', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'country', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'language', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'imdb_min', in: 'query', required: false, schema: new OA\Schema(type: 'number', minimum: 0, maximum: 10)),
            new OA\Parameter(name: 'imdb_max', in: 'query', required: false, schema: new OA\Schema(type: 'number', minimum: 0, maximum: 10)),
            new OA\Parameter(name: 'trashed', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['without', 'with', 'only'], default: 'without')),
            new OA\Parameter(ref: '#/components/parameters/Page'),
            new OA\Parameter(ref: '#/components/parameters/PerPage'),
        ],
    )]
    #[OA\Response(response: 200, ref: '#/components/responses/MoviePage')]
    #[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
    #[OA\Response(response: 403, ref: '#/components/responses/Forbidden')]
    #[OA\Response(response: 500, ref: '#/components/responses/ServerError')]
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Movie::class);

        $filters = $request->only(['q', 'type', 'badge', 'year_from', 'year_to', 'country', 'language', 'imdb_min', 'imdb_max', 'trashed']);
        $perPage = (int) $request->input('per_page', 15);

        $movies = $this->movieService->adminFilter($filters, $perPage);

        return ApiResponse::paginated(
            $movies,
            new MovieTransformer(),
            __('movie::messages.movies.index'),
        );
    }

    #[OA\Get(
        path: '/api/v1/admin/movies/{movie}',
        operationId: 'movie.admin.show',
        summary: 'Admin: show a movie (including trashed)',
        security: [['bearerAuth' => []]],
        tags: ['Movie'],
        parameters: [
            new OA\Parameter(name: 'movie', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
    )]
    #[OA\Response(response: 200, ref: '#/components/responses/MovieItem')]
    #[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
    #[OA\Response(response: 403, ref: '#/components/responses/Forbidden')]
    #[OA\Response(response: 404, ref: '#/components/responses/NotFound')]
    #[OA\Response(response: 500, ref: '#/components/responses/ServerError')]
    public function show(int $id): JsonResponse
    {
        $this->authorize('view', Movie::class);

        $movie = $this->movieService->getMovieByIdWithTrashed($id);

        return ApiResponse::fractal(
            $movie,
            new MovieTransformer(),
            __('movie::messages.movies.show'),
        );
    }
}
