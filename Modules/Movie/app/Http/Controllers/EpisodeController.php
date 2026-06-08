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

    public function destroy(int $movie, int $episode): JsonResponse
    {
        $this->authorize('delete', Episode::findOrFail($episode));

        $this->episodeService->deleteEpisode($movie, $episode);

        return ApiResponse::noContent(
            __('movie::messages.episodes.destroy'),
        );
    }

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
