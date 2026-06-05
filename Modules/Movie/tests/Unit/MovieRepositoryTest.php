<?php

namespace Modules\Movie\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Movie\Contracts\MovieRepositoryInterface;
use Modules\Movie\DTOs\CreateMovieDTO;
use Modules\Movie\DTOs\UpdateMovieDTO;
use Modules\Movie\Enums\BadgeType;
use Modules\Movie\Enums\MovieType;
use Modules\Movie\Models\Movie;
use Tests\TestCase;

final class MovieRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private MovieRepositoryInterface $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = app(MovieRepositoryInterface::class);
    }

    public function test_get_all_returns_empty_collection_when_no_movies_exist(): void
    {
        $result = $this->repository->getAll();

        $this->assertCount(0, $result);
    }

    public function test_get_all_returns_all_movies(): void
    {
        Movie::factory()->count(3)->create();

        $result = $this->repository->getAll();

        $this->assertCount(3, $result);
    }

    public function test_find_by_id_returns_movie_when_exists(): void
    {
        $movie = Movie::factory()->create();

        $result = $this->repository->findById($movie->id);

        $this->assertNotNull($result);
        $this->assertEquals($movie->id, $result->id);
    }

    public function test_find_by_id_returns_null_when_not_found(): void
    {
        $result = $this->repository->findById(999);

        $this->assertNull($result);
    }

    public function test_create_returns_movie(): void
    {
        $dto = new CreateMovieDTO(
            title: 'Inception',
            description: 'A mind-bending thriller',
            poster: 'https://example.com/poster.jpg',
            trailerUrl: 'https://example.com/trailer.mp4',
            downloadLinks: ['https://example.com/dl1'],
            releaseYear: 2010,
            country: 'USA',
            language: 'en',
            imdbScore: 8.8,
            badge: BadgeType::Dubbed,
            type: MovieType::Movie,
        );

        $result = $this->repository->create($dto);

        $this->assertInstanceOf(Movie::class, $result);
        $this->assertEquals('Inception', $result->title);
        $this->assertEquals(MovieType::Movie, $result->type);
        $this->assertDatabaseHas('movies', ['title' => 'Inception']);
    }

    public function test_update_returns_updated_movie(): void
    {
        $movie = Movie::factory()->create(['title' => 'Old Title']);

        $dto = new UpdateMovieDTO(
            title: 'New Title',
            description: $movie->description,
            poster: $movie->poster,
            trailerUrl: $movie->trailer_url,
            downloadLinks: $movie->download_links,
            releaseYear: $movie->release_year,
            country: $movie->country,
            language: $movie->language,
            imdbScore: $movie->imdb_score,
            badge: $movie->badge,
        );

        $result = $this->repository->update($movie->id, $dto);

        $this->assertEquals('New Title', $result->title);
        $this->assertDatabaseHas('movies', ['id' => $movie->id, 'title' => 'New Title']);
    }

    public function test_delete_soft_deletes_movie(): void
    {
        $movie = Movie::factory()->create();

        $result = $this->repository->delete($movie->id);

        $this->assertTrue($result);
        $this->assertSoftDeleted('movies', ['id' => $movie->id]);
    }

    public function test_restore_restores_soft_deleted_movie(): void
    {
        $movie = Movie::factory()->create();
        $movie->delete();

        $result = $this->repository->restore($movie->id);

        $this->assertNull($result->deleted_at);
        $this->assertDatabaseHas('movies', ['id' => $movie->id, 'deleted_at' => null]);
    }

    public function test_force_delete_permanently_removes_movie(): void
    {
        $movie = Movie::factory()->create();
        $movie->delete();

        $result = $this->repository->forceDelete($movie->id);

        $this->assertTrue($result);
        $this->assertDatabaseMissing('movies', ['id' => $movie->id]);
    }
}
