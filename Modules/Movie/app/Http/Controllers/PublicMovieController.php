<?php

namespace Modules\Movie\Http\Controllers;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Movie\Contracts\MovieServiceInterface;
use Modules\Movie\Http\Resources\Transformers\MovieTransformer;

class PublicMovieController extends Controller
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
