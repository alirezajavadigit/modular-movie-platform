<?php

namespace Modules\Movie\Tests\Unit;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Movie\Contracts\MovieServiceInterface;
use Modules\Movie\DTOs\CreateMovieDTO;
use Modules\Movie\DTOs\UpdateMovieDTO;
use Modules\Movie\Enums\BadgeType;
use Modules\Movie\Enums\MovieType;
use Modules\Movie\Models\Movie;
use Tests\TestCase;

final class MovieServiceTest extends TestCase
{
    use RefreshDatabase;

    private MovieServiceInterface $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(MovieServiceInterface::class);
    }

    public function test_get_all_movies_returns_collection(): void
    {
        Movie::factory()->count(2)->create();

        $result = $this->service->getAllMovies();

        $this->assertCount(2, $result);
    }

    public function test_get_movie_by_id_returns_movie_when_found(): void
    {
        $movie = Movie::factory()->create();

        $result = $this->service->getMovieById($movie->id);

        $this->assertEquals($movie->id, $result->id);
    }

    public function test_get_movie_by_id_throws_exception_when_not_found(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $this->service->getMovieById(999);
    }

    public function test_create_movie_returns_movie(): void
    {
        $dto = new CreateMovieDTO(
            title: ['en' => 'The Matrix'],
            description: ['en' => 'A sci-fi classic'],
            poster: null,
            trailerUrl: null,
            downloadLinks: null,
            releaseYear: 1999,
            country: 'USA',
            language: 'en',
            imdbScore: 8.7,
            badge: BadgeType::Subtitled,
            type: MovieType::Movie,
        );

        $result = $this->service->createMovie($dto);

        $this->assertEquals('The Matrix', $result->title);
        $this->assertEquals(MovieType::Movie, $result->type);
    }

    public function test_update_movie_returns_updated_movie(): void
    {
        $movie = Movie::factory()->create();

        $dto = new UpdateMovieDTO(
            title: ['en' => 'Updated Title'],
            description: $movie->getTranslations('description'),
            poster: $movie->poster,
            trailerUrl: $movie->trailer_url,
            downloadLinks: $movie->download_links,
            releaseYear: $movie->release_year,
            country: $movie->country,
            language: $movie->language,
            imdbScore: $movie->imdb_score,
            badge: $movie->badge,
        );

        $result = $this->service->updateMovie($movie->id, $dto);

        $this->assertEquals('Updated Title', $result->title);
    }

    public function test_update_movie_throws_exception_when_not_found(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $dto = new UpdateMovieDTO(
            title: ['en' => 'Title'],
            description: null,
            poster: null,
            trailerUrl: null,
            downloadLinks: null,
            releaseYear: 2020,
            country: null,
            language: null,
            imdbScore: null,
            badge: BadgeType::Dubbed,
        );

        $this->service->updateMovie(999, $dto);
    }

    public function test_delete_movie_returns_true(): void
    {
        $movie = Movie::factory()->create();

        $result = $this->service->deleteMovie($movie->id);

        $this->assertTrue($result);
        $this->assertSoftDeleted('movies', ['id' => $movie->id]);
    }

    public function test_delete_movie_throws_exception_when_not_found(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $this->service->deleteMovie(999);
    }

    public function test_restore_movie_returns_restored_movie(): void
    {
        $movie = Movie::factory()->create();
        $movie->delete();

        $result = $this->service->restoreMovie($movie->id);

        $this->assertNull($result->deleted_at);
    }
}
