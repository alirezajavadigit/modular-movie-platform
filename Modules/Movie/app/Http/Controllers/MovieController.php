<?php

namespace Modules\Movie\Http\Controllers;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Movie\Contracts\MovieServiceInterface;
use Modules\Movie\DTOs\CreateMovieDTO;
use Modules\Movie\DTOs\UpdateMovieDTO;
use Modules\Movie\Enums\BadgeType;
use Modules\Movie\Enums\MovieType;
use Modules\Movie\Http\Requests\StoreMovieRequest;
use Modules\Movie\Http\Requests\UpdateMovieRequest;
use Modules\Movie\Http\Resources\Transformers\MovieTransformer;

class MovieController extends Controller
{
    public function __construct(
        private readonly MovieServiceInterface $movieService,
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

    public function store(StoreMovieRequest $request): JsonResponse
    {
        $dto = new CreateMovieDTO(
            title: $request->validated('title'),
            description: $request->validated('description'),
            poster: $request->validated('poster'),
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

    public function update(UpdateMovieRequest $request, int $id): JsonResponse
    {
        $dto = new UpdateMovieDTO(
            title: $request->validated('title'),
            description: $request->validated('description'),
            poster: $request->validated('poster'),
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

    public function destroy(int $id): JsonResponse
    {
        $this->movieService->deleteMovie($id);

        return ApiResponse::noContent(
            __('movie::messages.movies.destroy'),
        );
    }

    public function restore(int $id): JsonResponse
    {
        $movie = $this->movieService->restoreMovie($id);

        return ApiResponse::fractal(
            $movie,
            new MovieTransformer(),
            __('movie::messages.movies.restore'),
        );
    }
}
