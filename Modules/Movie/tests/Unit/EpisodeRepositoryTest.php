<?php

namespace Modules\Movie\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Movie\Contracts\EpisodeRepositoryInterface;
use Modules\Movie\DTOs\CreateEpisodeDTO;
use Modules\Movie\DTOs\UpdateEpisodeDTO;
use Modules\Movie\Models\Episode;
use Modules\Movie\Models\Movie;
use Tests\TestCase;

final class EpisodeRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private EpisodeRepositoryInterface $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = app(EpisodeRepositoryInterface::class);
    }

    public function test_get_all_by_movie_returns_episodes_for_serial(): void
    {
        $serial = Movie::factory()->serial()->create();
        Episode::factory()->count(3)->create(['movie_id' => $serial->id]);
        Episode::factory()->create();

        $result = $this->repository->getAllByMovie($serial->id);

        $this->assertCount(3, $result);
    }

    public function test_get_all_by_movie_returns_empty_collection_when_none(): void
    {
        $serial = Movie::factory()->serial()->create();

        $result = $this->repository->getAllByMovie($serial->id);

        $this->assertCount(0, $result);
    }

    public function test_get_all_by_movie_returns_ordered_by_season_and_episode(): void
    {
        $serial = Movie::factory()->serial()->create();
        Episode::factory()->create(['movie_id' => $serial->id, 'season_number' => 2, 'episode_number' => 1]);
        Episode::factory()->create(['movie_id' => $serial->id, 'season_number' => 1, 'episode_number' => 3]);
        Episode::factory()->create(['movie_id' => $serial->id, 'season_number' => 1, 'episode_number' => 1]);

        $result = $this->repository->getAllByMovie($serial->id);

        $this->assertEquals(1, $result[0]->season_number);
        $this->assertEquals(1, $result[0]->episode_number);
        $this->assertEquals(1, $result[1]->season_number);
        $this->assertEquals(3, $result[1]->episode_number);
        $this->assertEquals(2, $result[2]->season_number);
    }

    public function test_find_by_id_returns_episode_when_exists(): void
    {
        $episode = Episode::factory()->create();

        $result = $this->repository->findById($episode->id);

        $this->assertNotNull($result);
        $this->assertEquals($episode->id, $result->id);
    }

    public function test_find_by_id_returns_null_when_not_found(): void
    {
        $result = $this->repository->findById(999);

        $this->assertNull($result);
    }

    public function test_create_returns_episode(): void
    {
        $serial = Movie::factory()->serial()->create();

        $dto = new CreateEpisodeDTO(
            movieId: $serial->id,
            seasonNumber: 1,
            episodeNumber: 1,
            title: 'Pilot',
            description: 'The first episode',
            poster: null,
            trailerUrl: null,
            downloadLinks: ['https://example.com/ep1'],
        );

        $result = $this->repository->create($dto);

        $this->assertInstanceOf(Episode::class, $result);
        $this->assertEquals('Pilot', $result->title);
        $this->assertEquals($serial->id, $result->movie_id);
        $this->assertDatabaseHas('episodes', ['title' => 'Pilot', 'movie_id' => $serial->id]);
    }

    public function test_update_returns_updated_episode(): void
    {
        $episode = Episode::factory()->create(['title' => 'Old Title']);

        $dto = new UpdateEpisodeDTO(
            seasonNumber: $episode->season_number,
            episodeNumber: $episode->episode_number,
            title: 'New Title',
            description: $episode->description,
            poster: $episode->poster,
            trailerUrl: $episode->trailer_url,
            downloadLinks: $episode->download_links,
        );

        $result = $this->repository->update($episode->id, $dto);

        $this->assertEquals('New Title', $result->title);
        $this->assertDatabaseHas('episodes', ['id' => $episode->id, 'title' => 'New Title']);
    }

    public function test_delete_soft_deletes_episode(): void
    {
        $episode = Episode::factory()->create();

        $result = $this->repository->delete($episode->id);

        $this->assertTrue($result);
        $this->assertSoftDeleted('episodes', ['id' => $episode->id]);
    }

    public function test_restore_restores_soft_deleted_episode(): void
    {
        $episode = Episode::factory()->create();
        $episode->delete();

        $result = $this->repository->restore($episode->id);

        $this->assertNull($result->deleted_at);
        $this->assertDatabaseHas('episodes', ['id' => $episode->id, 'deleted_at' => null]);
    }

    public function test_force_delete_permanently_removes_episode(): void
    {
        $episode = Episode::factory()->create();
        $episode->delete();

        $result = $this->repository->forceDelete($episode->id);

        $this->assertTrue($result);
        $this->assertDatabaseMissing('episodes', ['id' => $episode->id]);
    }
}
