<?php

namespace Modules\Movie\Tests\Unit;

use DomainException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Movie\Contracts\EpisodeServiceInterface;
use Modules\Movie\DTOs\CreateEpisodeDTO;
use Modules\Movie\DTOs\UpdateEpisodeDTO;
use Modules\Movie\Models\Episode;
use Modules\Movie\Models\Movie;
use Tests\TestCase;

final class EpisodeServiceTest extends TestCase
{
    use RefreshDatabase;

    private EpisodeServiceInterface $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(EpisodeServiceInterface::class);
    }

    public function test_get_all_episodes_returns_collection(): void
    {
        $serial = Movie::factory()->serial()->create();
        Episode::factory()->count(3)->create(['movie_id' => $serial->id]);

        $result = $this->service->getAllEpisodes($serial->id);

        $this->assertCount(3, $result);
    }

    public function test_get_all_episodes_throws_when_movie_not_serial(): void
    {
        $movie = Movie::factory()->movie()->create();

        $this->expectException(DomainException::class);

        $this->service->getAllEpisodes($movie->id);
    }

    public function test_get_all_episodes_throws_when_movie_not_found(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $this->service->getAllEpisodes(999);
    }

    public function test_get_episode_by_id_returns_episode(): void
    {
        $serial = Movie::factory()->serial()->create();
        $episode = Episode::factory()->create(['movie_id' => $serial->id]);

        $result = $this->service->getEpisodeById($serial->id, $episode->id);

        $this->assertEquals($episode->id, $result->id);
    }

    public function test_get_episode_by_id_throws_when_episode_not_found(): void
    {
        $serial = Movie::factory()->serial()->create();

        $this->expectException(ModelNotFoundException::class);

        $this->service->getEpisodeById($serial->id, 999);
    }

    public function test_get_episode_by_id_throws_when_episode_belongs_to_different_serial(): void
    {
        $serial1 = Movie::factory()->serial()->create();
        $serial2 = Movie::factory()->serial()->create();
        $episode = Episode::factory()->create(['movie_id' => $serial2->id]);

        $this->expectException(ModelNotFoundException::class);

        $this->service->getEpisodeById($serial1->id, $episode->id);
    }

    public function test_create_episode_returns_episode(): void
    {
        $serial = Movie::factory()->serial()->create();

        $dto = new CreateEpisodeDTO(
            movieId: $serial->id,
            seasonNumber: 1,
            episodeNumber: 1,
            title: 'Pilot',
            description: 'First episode',
            poster: null,
            trailerUrl: null,
            downloadLinks: null,
        );

        $result = $this->service->createEpisode($dto);

        $this->assertEquals('Pilot', $result->title);
        $this->assertEquals($serial->id, $result->movie_id);
    }

    public function test_create_episode_throws_when_movie_not_serial(): void
    {
        $movie = Movie::factory()->movie()->create();

        $dto = new CreateEpisodeDTO(
            movieId: $movie->id,
            seasonNumber: 1,
            episodeNumber: 1,
            title: 'Pilot',
            description: null,
            poster: null,
            trailerUrl: null,
            downloadLinks: null,
        );

        $this->expectException(DomainException::class);

        $this->service->createEpisode($dto);
    }

    public function test_update_episode_returns_updated_episode(): void
    {
        $serial = Movie::factory()->serial()->create();
        $episode = Episode::factory()->create(['movie_id' => $serial->id, 'title' => 'Old']);

        $dto = new UpdateEpisodeDTO(
            seasonNumber: $episode->season_number,
            episodeNumber: $episode->episode_number,
            title: 'New',
            description: $episode->description,
            poster: $episode->poster,
            trailerUrl: $episode->trailer_url,
            downloadLinks: $episode->download_links,
        );

        $result = $this->service->updateEpisode($serial->id, $episode->id, $dto);

        $this->assertEquals('New', $result->title);
    }

    public function test_delete_episode_returns_true(): void
    {
        $serial = Movie::factory()->serial()->create();
        $episode = Episode::factory()->create(['movie_id' => $serial->id]);

        $result = $this->service->deleteEpisode($serial->id, $episode->id);

        $this->assertTrue($result);
        $this->assertSoftDeleted('episodes', ['id' => $episode->id]);
    }

    public function test_restore_episode_returns_restored_episode(): void
    {
        $serial = Movie::factory()->serial()->create();
        $episode = Episode::factory()->create(['movie_id' => $serial->id]);
        $episode->delete();

        $result = $this->service->restoreEpisode($serial->id, $episode->id);

        $this->assertNull($result->deleted_at);
    }
}
