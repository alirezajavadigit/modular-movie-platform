<?php

namespace Modules\Movie\Http\Controllers;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Movie\Contracts\EpisodeServiceInterface;
use Modules\Movie\Http\Resources\Transformers\EpisodeTransformer;
use OpenApi\Attributes as OA;

class PublicEpisodeController extends Controller
{
    public function __construct(
        private readonly EpisodeServiceInterface $episodeService,
    ) {}

    #[OA\Get(
        path: '/api/v1/movies/{movie}/episodes',
        operationId: 'api.public.movies.episodes.index',
        summary: 'List episodes of a serial',
        tags: ['Movie'],
        parameters: [
            new OA\Parameter(name: 'movie', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
    )]
    #[OA\Response(response: 200, ref: '#/components/responses/EpisodeCollection')]
    #[OA\Response(response: 404, ref: '#/components/responses/NotFound')]
    #[OA\Response(response: 500, ref: '#/components/responses/ServerError')]
    public function index(int $movie): JsonResponse
    {
        $episodes = $this->episodeService->getAllEpisodes($movie);

        return ApiResponse::fractal(
            $episodes,
            new EpisodeTransformer(),
            __('movie::messages.episodes.index'),
        );
    }

    #[OA\Get(
        path: '/api/v1/movies/{movie}/episodes/{episode}',
        operationId: 'api.public.movies.episodes.show',
        summary: 'Show an episode',
        tags: ['Movie'],
        parameters: [
            new OA\Parameter(name: 'movie', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'episode', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
    )]
    #[OA\Response(response: 200, ref: '#/components/responses/EpisodeItem')]
    #[OA\Response(response: 404, ref: '#/components/responses/NotFound')]
    #[OA\Response(response: 500, ref: '#/components/responses/ServerError')]
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
