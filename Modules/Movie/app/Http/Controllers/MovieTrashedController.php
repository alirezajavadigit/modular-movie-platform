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

class MovieTrashedController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly MovieServiceInterface $movieService,
    ) {}

    #[OA\Get(
        path: '/api/v1/admin/movies/trashed',
        operationId: 'movie.admin.trashed',
        summary: 'Admin: list soft-deleted movies',
        security: [['bearerAuth' => []]],
        tags: ['Movie'],
        parameters: [
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
        $this->authorize('viewTrashed', Movie::class);

        $perPage = (int) $request->input('per_page', 15);
        $movies = $this->movieService->getTrashed($perPage);

        return ApiResponse::paginated(
            $movies,
            new MovieTransformer(),
            __('movie::messages.movies.trashed'),
        );
    }

    #[OA\Patch(
        path: '/api/v1/admin/movies/{movie}/restore',
        operationId: 'movie.admin.restore',
        summary: 'Admin: restore a soft-deleted movie',
        security: [['bearerAuth' => []]],
        tags: ['Movie'],
        parameters: [
            new OA\Parameter(name: 'movie', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, ref: '#/components/responses/MovieItem'),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
            new OA\Response(response: 403, ref: '#/components/responses/Forbidden'),
            new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
            new OA\Response(response: 500, ref: '#/components/responses/ServerError'),
        ],
    )]
    public function restore(int $id): JsonResponse
    {
        $this->authorize('restore', Movie::withTrashed()->findOrFail($id));

        $movie = $this->movieService->restoreMovie($id);

        return ApiResponse::fractal(
            $movie,
            new MovieTransformer(),
            __('movie::messages.movies.restore'),
        );
    }

    #[OA\Delete(
        path: '/api/v1/admin/movies/{movie}/force-delete',
        operationId: 'movie.admin.forceDelete',
        summary: 'Admin: permanently delete a movie',
        security: [['bearerAuth' => []]],
        tags: ['Movie'],
        parameters: [
            new OA\Parameter(name: 'movie', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
    )]
    #[OA\Response(response: 204, ref: '#/components/responses/NoContent')]
    #[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
    #[OA\Response(response: 403, ref: '#/components/responses/Forbidden')]
    #[OA\Response(response: 404, ref: '#/components/responses/NotFound')]
    #[OA\Response(response: 500, ref: '#/components/responses/ServerError')]
    public function forceDelete(int $id): JsonResponse
    {
        $this->authorize('forceDelete', Movie::withTrashed()->findOrFail($id));

        $this->movieService->forceDeleteMovie($id);

        return ApiResponse::noContent(__('movie::messages.movies.force_deleted'));
    }
}
