<?php

namespace Modules\Movie\Http\Controllers;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Movie\Contracts\EpisodeServiceInterface;
use Modules\Movie\Http\Resources\Transformers\EpisodeTransformer;

class PublicEpisodeController extends Controller
{
    public function __construct(
        private readonly EpisodeServiceInterface $episodeService,
    ) {}

    public function index(int $movie): JsonResponse
    {
        $episodes = $this->episodeService->getAllEpisodes($movie);

        return ApiResponse::fractal(
            $episodes,
            new EpisodeTransformer(),
            __('movie::messages.episodes.index'),
        );
    }

    public function show(int $movie, int $episode): JsonResponse
    {
        $episode = $this->episodeService->getEpisodeById($movie, $episode);

        return ApiResponse::fractal(
            $episode,
            new EpisodeTransformer(),
            __('movie::messages.episodes.show'),
        );
    }
}
