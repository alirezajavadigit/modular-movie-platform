<?php

namespace Modules\Movie\Http\Controllers;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Modules\Movie\Contracts\EpisodeServiceInterface;
use Modules\Movie\Contracts\FileUploadServiceInterface;
use Modules\Movie\Models\Episode;
use Modules\Movie\DTOs\CreateEpisodeDTO;
use Modules\Movie\DTOs\UpdateEpisodeDTO;
use Modules\Movie\Http\Requests\StoreEpisodeRequest;
use Modules\Movie\Http\Requests\UpdateEpisodeRequest;
use Modules\Movie\Http\Resources\Transformers\EpisodeTransformer;
use OpenApi\Attributes as OA;

class EpisodeController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly EpisodeServiceInterface $episodeService,
        private readonly FileUploadServiceInterface $fileUploadService,
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

    #[OA\Post(
        path: '/api/v1/movies/{movie}/episodes',
        operationId: 'api.movies.episodes.store',
        summary: 'Create an episode for a serial',
        security: [['bearerAuth' => []]],
        tags: ['Movie'],
        requestBody: new OA\RequestBody(ref: '#/components/requestBodies/StoreEpisodeRequest'),
        parameters: [
            new OA\Parameter(name: 'movie', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
    )]
    #[OA\Response(response: 201, ref: '#/components/responses/EpisodeCreated')]
    #[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
    #[OA\Response(response: 403, ref: '#/components/responses/Forbidden')]
    #[OA\Response(response: 404, ref: '#/components/responses/NotFound')]
    #[OA\Response(response: 422, ref: '#/components/responses/LegacyValidationError')]
    #[OA\Response(response: 500, ref: '#/components/responses/ServerError')]
    public function store(StoreEpisodeRequest $request, int $movie): JsonResponse
    {
        $this->authorize('create', Episode::class);

        $poster = $this->resolvePoster($request);

        $dto = new CreateEpisodeDTO(
            movieId: $movie,
            seasonNumber: (int) $request->validated('season_number'),
            episodeNumber: (int) $request->validated('episode_number'),
            title: $request->validated('title'),
            description: $request->validated('description'),
            poster: $poster,
            trailerUrl: $request->validated('trailer_url'),
            downloadLinks: $request->validated('download_links'),
        );

        $episode = $this->episodeService->createEpisode($dto);

        return ApiResponse::fractalCreated(
            $episode,
            new EpisodeTransformer(),
            __('movie::messages.episodes.store'),
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

    #[OA\Put(
        path: '/api/v1/movies/{movie}/episodes/{episode}',
        operationId: 'api.movies.episodes.update',
        summary: 'Update an episode',
        security: [['bearerAuth' => []]],
        tags: ['Movie'],
        requestBody: new OA\RequestBody(ref: '#/components/requestBodies/UpdateEpisodeRequest'),
        parameters: [
            new OA\Parameter(name: 'movie', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'episode', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, ref: '#/components/responses/EpisodeItem'),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
            new OA\Response(response: 403, ref: '#/components/responses/Forbidden'),
            new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
            new OA\Response(response: 422, ref: '#/components/responses/LegacyValidationError'),
            new OA\Response(response: 500, ref: '#/components/responses/ServerError'),
        ],
    )]
    #[OA\Patch(
        path: '/api/v1/movies/{movie}/episodes/{episode}',
        operationId: 'api.movies.episodes.patch',
        summary: 'Partially update an episode',
        security: [['bearerAuth' => []]],
        tags: ['Movie'],
        requestBody: new OA\RequestBody(ref: '#/components/requestBodies/UpdateEpisodeRequest'),
        parameters: [
            new OA\Parameter(name: 'movie', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'episode', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, ref: '#/components/responses/EpisodeItem'),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
            new OA\Response(response: 403, ref: '#/components/responses/Forbidden'),
            new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
            new OA\Response(response: 422, ref: '#/components/responses/LegacyValidationError'),
            new OA\Response(response: 500, ref: '#/components/responses/ServerError'),
        ],
    )]
    public function update(UpdateEpisodeRequest $request, int $movie, int $episodeId): JsonResponse
    {
        $episode = $this->episodeService->getEpisodeById($movie, $episodeId);
        $this->authorize('update', $episode);

        $poster = $this->resolvePoster($request);

        if ($poster && $episode->poster && $poster !== $episode->poster) {
            $this->fileUploadService->delete($episode->poster);
        }

        $dto = new UpdateEpisodeDTO(
            seasonNumber: (int) $request->validated('season_number'),
            episodeNumber: (int) $request->validated('episode_number'),
            title: $request->validated('title'),
            description: $request->validated('description'),
            poster: $poster ?? $episode->poster,
            trailerUrl: $request->validated('trailer_url'),
            downloadLinks: $request->validated('download_links'),
        );

        $episode = $this->episodeService->updateEpisode($movie, $episodeId, $dto);

        return ApiResponse::fractal(
            $episode,
            new EpisodeTransformer(),
            __('movie::messages.episodes.update'),
        );
    }

    #[OA\Delete(
        path: '/api/v1/movies/{movie}/episodes/{episode}',
        operationId: 'api.movies.episodes.destroy',
        summary: 'Soft delete an episode',
        security: [['bearerAuth' => []]],
        tags: ['Movie'],
        parameters: [
            new OA\Parameter(name: 'movie', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'episode', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
    )]
    #[OA\Response(response: 204, ref: '#/components/responses/NoContent')]
    #[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
    #[OA\Response(response: 403, ref: '#/components/responses/Forbidden')]
    #[OA\Response(response: 404, ref: '#/components/responses/NotFound')]
    #[OA\Response(response: 500, ref: '#/components/responses/ServerError')]
    public function destroy(int $movie, int $episode): JsonResponse
    {
        $this->authorize('delete', Episode::findOrFail($episode));

        $this->episodeService->deleteEpisode($movie, $episode);

        return ApiResponse::noContent(
            __('movie::messages.episodes.destroy'),
        );
    }

    #[OA\Post(
        path: '/api/v1/movies/{movie}/episodes/{episode}/restore',
        operationId: 'api.movies.episodes.restore',
        summary: 'Restore a soft-deleted episode',
        security: [['bearerAuth' => []]],
        tags: ['Movie'],
        parameters: [
            new OA\Parameter(name: 'movie', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'episode', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
    )]
    #[OA\Response(response: 200, ref: '#/components/responses/EpisodeItem')]
    #[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
    #[OA\Response(response: 403, ref: '#/components/responses/Forbidden')]
    #[OA\Response(response: 404, ref: '#/components/responses/NotFound')]
    #[OA\Response(response: 500, ref: '#/components/responses/ServerError')]
    public function restore(int $movie, int $episode): JsonResponse
    {
        $this->authorize('restore', Episode::class);

        $episode = $this->episodeService->restoreEpisode($movie, $episode);

        return ApiResponse::fractal(
            $episode,
            new EpisodeTransformer(),
            __('movie::messages.episodes.restore'),
        );
    }

    private function resolvePoster(StoreEpisodeRequest|UpdateEpisodeRequest $request): ?string
    {
        if ($request->hasFile('poster_file')) {
            $directory = config('movie.upload.directories.episode_posters');

            return $this->fileUploadService->upload($request->file('poster_file'), $directory);
        }

        return $request->validated('poster');
    }
}
