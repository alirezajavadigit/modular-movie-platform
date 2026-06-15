<?php

namespace Modules\Movie\Http\Controllers;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Movie\Contracts\MovieServiceInterface;
use Modules\Movie\Http\Resources\Transformers\MovieTransformer;
use OpenApi\Attributes as OA;

class PublicMovieController extends Controller
{
    public function __construct(
        private readonly MovieServiceInterface $movieService,
    ) {}

    #[OA\Get(
        path: '/api/v1/movies',
        operationId: 'api.public.movies.index',
        summary: 'List all movies and serials',
        tags: ['Movie'],
        parameters: [
            new OA\Parameter(name: 'q', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'type', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['movie', 'serial'])),
            new OA\Parameter(ref: '#/components/parameters/Page'),
            new OA\Parameter(ref: '#/components/parameters/PerPage'),
        ],
    )]
    #[OA\Response(response: 200, ref: '#/components/responses/MoviePage')]
    #[OA\Response(response: 500, ref: '#/components/responses/ServerError')]
    public function index(Request $request): JsonResponse
    {
        $q = (string) $request->input('q', '');
        $type = (string) $request->input('type', '');
        $perPage = (int) $request->input('per_page', 15);

        $movies = $this->movieService->publicPaginated($q, $type, $perPage);

        return ApiResponse::paginated(
            $movies,
            new MovieTransformer(),
            __('movie::messages.movies.index'),
        );
    }

    #[OA\Get(
        path: '/api/v1/movies/search',
        operationId: 'api.public.movies.search',
        summary: 'Search movies and serials',
        tags: ['Movie'],
        parameters: [
            new OA\Parameter(ref: '#/components/parameters/SearchQuery'),
            new OA\Parameter(ref: '#/components/parameters/Page'),
            new OA\Parameter(ref: '#/components/parameters/PerPage'),
        ],
    )]
    #[OA\Response(response: 200, ref: '#/components/responses/MoviePage')]
    #[OA\Response(response: 500, ref: '#/components/responses/ServerError')]
    public function search(Request $request): JsonResponse
    {
        $q = (string) $request->input('q', '');
        $perPage = (int) $request->input('per_page', 15);

        $movies = $this->movieService->publicPaginated($q, '', $perPage);

        return ApiResponse::paginated(
            $movies,
            new MovieTransformer(),
            __('movie::messages.movies.search'),
        );
    }

    #[OA\Get(
        path: '/api/v1/movies/{movie}',
        operationId: 'api.public.movies.show',
        summary: 'Show a movie',
        tags: ['Movie'],
        parameters: [
            new OA\Parameter(name: 'movie', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
    )]
    #[OA\Response(response: 200, ref: '#/components/responses/MovieItem')]
    #[OA\Response(response: 404, ref: '#/components/responses/NotFound')]
    #[OA\Response(response: 500, ref: '#/components/responses/ServerError')]
    public function show(int $id): JsonResponse
    {
        $movie = $this->movieService->getMovieById($id);

        return ApiResponse::fractal(
            $movie,
            new MovieTransformer(),
            __('movie::messages.movies.show'),
        );
    }
}
