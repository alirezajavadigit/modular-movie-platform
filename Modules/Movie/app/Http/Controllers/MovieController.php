<?php

namespace Modules\Movie\Http\Controllers;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Modules\Movie\Contracts\FileUploadServiceInterface;
use Modules\Movie\Contracts\MovieServiceInterface;
use Modules\Movie\Models\Movie;
use Modules\Movie\DTOs\CreateMovieDTO;
use Modules\Movie\DTOs\UpdateMovieDTO;
use Modules\Movie\Enums\BadgeType;
use Modules\Movie\Enums\MovieType;
use Modules\Movie\Http\Requests\StoreMovieRequest;
use Modules\Movie\Http\Requests\UpdateMovieRequest;
use Modules\Movie\Http\Resources\Transformers\MovieTransformer;
use OpenApi\Attributes as OA;

class MovieController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly MovieServiceInterface $movieService,
        private readonly FileUploadServiceInterface $fileUploadService,
    ) {}

    public function index(): JsonResponse
    {
        $movies = $this->movieService->getAllMovies();

        return ApiResponse::fractal(
            $movies,
            new MovieTransformer(),
            __('movie::messages.movies.index'),
        );
    }

    #[OA\Post(
        path: '/api/v1/movies',
        operationId: 'api.movies.store',
        summary: 'Create a movie or serial',
        security: [['bearerAuth' => []]],
        tags: ['Movie'],
        requestBody: new OA\RequestBody(ref: '#/components/requestBodies/StoreMovieRequest'),
    )]
    #[OA\Response(response: 201, ref: '#/components/responses/MovieCreated')]
    #[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
    #[OA\Response(response: 403, ref: '#/components/responses/Forbidden')]
    #[OA\Response(response: 422, ref: '#/components/responses/LegacyValidationError')]
    #[OA\Response(response: 500, ref: '#/components/responses/ServerError')]
    public function store(StoreMovieRequest $request): JsonResponse
    {
        $this->authorize('create', Movie::class);

        $poster = $this->resolvePoster($request);

        $dto = new CreateMovieDTO(
            title: $request->validated('title'),
            description: $request->validated('description'),
            poster: $poster,
            trailerUrl: $request->validated('trailer_url'),
            downloadLinks: $request->validated('download_links'),
            releaseYear: (int) $request->validated('release_year'),
            country: $request->validated('country'),
            language: $request->validated('language'),
            imdbScore: $request->validated('imdb_score'),
            badge: BadgeType::from($request->validated('badge')),
            type: MovieType::from($request->validated('type')),
        );

        $movie = $this->movieService->createMovie($dto);

        return ApiResponse::fractalCreated(
            $movie,
            new MovieTransformer(),
            __('movie::messages.movies.store'),
        );
    }

    public function show(int $id): JsonResponse
    {
        $movie = $this->movieService->getMovieById($id);

        return ApiResponse::fractal(
            $movie,
            new MovieTransformer(),
            __('movie::messages.movies.show'),
        );
    }

    #[OA\Put(
        path: '/api/v1/movies/{movie}',
        operationId: 'api.movies.update',
        summary: 'Update a movie or serial',
        security: [['bearerAuth' => []]],
        tags: ['Movie'],
        requestBody: new OA\RequestBody(ref: '#/components/requestBodies/UpdateMovieRequest'),
        parameters: [
            new OA\Parameter(name: 'movie', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, ref: '#/components/responses/MovieItem'),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
            new OA\Response(response: 403, ref: '#/components/responses/Forbidden'),
            new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
            new OA\Response(response: 422, ref: '#/components/responses/LegacyValidationError'),
            new OA\Response(response: 500, ref: '#/components/responses/ServerError'),
        ],
    )]
    #[OA\Patch(
        path: '/api/v1/movies/{movie}',
        operationId: 'api.movies.patch',
        summary: 'Partially update a movie or serial',
        security: [['bearerAuth' => []]],
        tags: ['Movie'],
        requestBody: new OA\RequestBody(ref: '#/components/requestBodies/UpdateMovieRequest'),
        parameters: [
            new OA\Parameter(name: 'movie', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, ref: '#/components/responses/MovieItem'),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
            new OA\Response(response: 403, ref: '#/components/responses/Forbidden'),
            new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
            new OA\Response(response: 422, ref: '#/components/responses/LegacyValidationError'),
            new OA\Response(response: 500, ref: '#/components/responses/ServerError'),
        ],
    )]
    public function update(UpdateMovieRequest $request, int $id): JsonResponse
    {
        $movie = $this->movieService->getMovieById($id);
        $this->authorize('update', $movie);

        $poster = $this->resolvePoster($request);
        if ($poster && $movie->poster && $poster !== $movie->poster) {
            $this->fileUploadService->delete($movie->poster);
        }

        $dto = new UpdateMovieDTO(
            title: $request->validated('title'),
            description: $request->validated('description'),
            poster: $poster ?? $movie->poster,
            trailerUrl: $request->validated('trailer_url'),
            downloadLinks: $request->validated('download_links'),
            releaseYear: (int) $request->validated('release_year'),
            country: $request->validated('country'),
            language: $request->validated('language'),
            imdbScore: $request->validated('imdb_score'),
            badge: BadgeType::from($request->validated('badge')),
        );

        $movie = $this->movieService->updateMovie($id, $dto);

        return ApiResponse::fractal(
            $movie,
            new MovieTransformer(),
            __('movie::messages.movies.update'),
        );
    }

    #[OA\Delete(
        path: '/api/v1/movies/{movie}',
        operationId: 'api.movies.destroy',
        summary: 'Soft delete a movie or serial',
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
    public function destroy(int $id): JsonResponse
    {
        $this->authorize('delete', Movie::findOrFail($id));

        $this->movieService->deleteMovie($id);

        return ApiResponse::noContent(
            __('movie::messages.movies.destroy'),
        );
    }

    #[OA\Post(
        path: '/api/v1/movies/{movie}/restore',
        operationId: 'api.movies.restore',
        summary: 'Restore a soft-deleted movie or serial',
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
    public function restore(int $id): JsonResponse
    {
        $this->authorize('restore', Movie::class);

        $movie = $this->movieService->restoreMovie($id);

        return ApiResponse::fractal(
            $movie,
            new MovieTransformer(),
            __('movie::messages.movies.restore'),
        );
    }

    private function resolvePoster(StoreMovieRequest|UpdateMovieRequest $request): ?string
    {
        if ($request->hasFile('poster_file')) {
            $directory = config('movie.upload.directories.movie_posters');

            return $this->fileUploadService->upload($request->file('poster_file'), $directory);
        }

        return $request->validated('poster');
    }
}
