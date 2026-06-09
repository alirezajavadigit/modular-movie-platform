<?php

namespace Modules\Movie\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\Auth\Models\User;
use Modules\Authorization\Models\Role;
use Modules\Movie\Enums\BadgeType;
use Modules\Movie\Enums\MovieType;
use Modules\Movie\Models\Movie;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

final class MovieFeatureTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        Role::create(['name' => 'super_admin', 'guard_name' => 'api']);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('super_admin');
    }

    public function test_public_get_all_movies_returns_200(): void
    {
        Movie::factory()->count(3)->create();

        $response = $this->getJson('/api/v1/movies');

        $response->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_public_get_all_movies_returns_empty_when_none_exist(): void
    {
        $response = $this->getJson('/api/v1/movies');

        $response->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_public_get_movie_by_id_returns_200(): void
    {
        $movie = Movie::factory()->create();

        $response = $this->getJson("/api/v1/movies/{$movie->id}");

        $response->assertOk()
            ->assertJsonFragment(['title' => $movie->title]);
    }

    public function test_public_get_movie_by_id_returns_404_when_not_found(): void
    {
        $response = $this->getJson('/api/v1/movies/999');

        $response->assertNotFound();
    }

    public function test_public_routes_do_not_require_authentication(): void
    {
        $movie = Movie::factory()->create();

        $this->getJson('/api/v1/movies')->assertOk();
        $this->getJson("/api/v1/movies/{$movie->id}")->assertOk();
    }

    public function test_create_movie_returns_201(): void
    {
        $payload = [
            'title'        => 'Inception',
            'description'  => 'A mind-bending thriller',
            'poster'       => 'https://example.com/poster.jpg',
            'trailer_url'  => 'https://example.com/trailer.mp4',
            'download_links' => ['https://example.com/dl1'],
            'release_year' => 2010,
            'country'      => 'USA',
            'language'     => 'en',
            'imdb_score'   => 8.8,
            'badge'        => BadgeType::Dubbed->value,
            'type'         => MovieType::Movie->value,
        ];

        $response = $this->actingAs($this->admin, 'api')
            ->postJson('/api/v1/movies', $payload);

        $response->assertCreated()
            ->assertJsonFragment(['title' => 'Inception']);

        $this->assertDatabaseHas('movies', ['title' => 'Inception']);
    }

    public function test_create_serial_returns_201(): void
    {
        $payload = [
            'title'        => 'Breaking Bad',
            'description'  => 'A chemistry teacher turned drug lord',
            'release_year' => 2008,
            'badge'        => BadgeType::Subtitled->value,
            'type'         => MovieType::Serial->value,
        ];

        $response = $this->actingAs($this->admin, 'api')
            ->postJson('/api/v1/movies', $payload);

        $response->assertCreated()
            ->assertJsonFragment(['type' => 'serial']);
    }

    public function test_create_movie_with_poster_file_upload(): void
    {
        Storage::fake(config('movie.upload.disk', 'public'));

        $payload = [
            'title'        => 'Upload Test',
            'release_year' => 2024,
            'badge'        => BadgeType::Dubbed->value,
            'type'         => MovieType::Movie->value,
            'poster_file'  => UploadedFile::fake()->image('poster.jpg', 800, 600),
        ];

        $response = $this->actingAs($this->admin, 'api')
            ->postJson('/api/v1/movies', $payload);

        $response->assertCreated();

        $movie = Movie::where('title', 'Upload Test')->first();
        $this->assertNotNull($movie->poster);

        Storage::disk(config('movie.upload.disk', 'public'))
            ->assertExists(config('movie.upload.directories.movie_posters'));
    }

    public function test_create_movie_poster_file_takes_precedence_over_poster_string(): void
    {
        Storage::fake(config('movie.upload.disk', 'public'));

        $payload = [
            'title'        => 'Precedence Test',
            'release_year' => 2024,
            'badge'        => BadgeType::Dubbed->value,
            'type'         => MovieType::Movie->value,
            'poster'       => 'https://example.com/ignored.jpg',
            'poster_file'  => UploadedFile::fake()->image('poster.jpg'),
        ];

        $response = $this->actingAs($this->admin, 'api')
            ->postJson('/api/v1/movies', $payload);

        $response->assertCreated();

        $movie = Movie::where('title', 'Precedence Test')->first();
        $this->assertNotEquals('https://example.com/ignored.jpg', $movie->poster);
    }

    public function test_create_movie_requires_title(): void
    {
        $payload = [
            'release_year' => 2020,
            'badge'        => BadgeType::Dubbed->value,
            'type'         => MovieType::Movie->value,
        ];

        $response = $this->actingAs($this->admin, 'api')
            ->postJson('/api/v1/movies', $payload);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['title']);
    }

    public function test_create_movie_requires_release_year(): void
    {
        $payload = [
            'title' => 'Test Movie',
            'badge' => BadgeType::Dubbed->value,
            'type'  => MovieType::Movie->value,
        ];

        $response = $this->actingAs($this->admin, 'api')
            ->postJson('/api/v1/movies', $payload);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['release_year']);
    }

    public function test_create_movie_requires_valid_badge(): void
    {
        $payload = [
            'title'        => 'Test Movie',
            'release_year' => 2020,
            'badge'        => 'invalid',
            'type'         => MovieType::Movie->value,
        ];

        $response = $this->actingAs($this->admin, 'api')
            ->postJson('/api/v1/movies', $payload);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['badge']);
    }

    public function test_create_movie_requires_valid_type(): void
    {
        $payload = [
            'title'        => 'Test Movie',
            'release_year' => 2020,
            'badge'        => BadgeType::Dubbed->value,
            'type'         => 'invalid',
        ];

        $response = $this->actingAs($this->admin, 'api')
            ->postJson('/api/v1/movies', $payload);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['type']);
    }

    public function test_create_movie_validates_imdb_score_range(): void
    {
        $payload = [
            'title'        => 'Test Movie',
            'release_year' => 2020,
            'badge'        => BadgeType::Dubbed->value,
            'type'         => MovieType::Movie->value,
            'imdb_score'   => 11.0,
        ];

        $response = $this->actingAs($this->admin, 'api')
            ->postJson('/api/v1/movies', $payload);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['imdb_score']);
    }

    public function test_create_movie_requires_authentication(): void
    {
        $response = $this->postJson('/api/v1/movies', [
            'title'        => 'Test',
            'release_year' => 2020,
            'badge'        => BadgeType::Dubbed->value,
            'type'         => MovieType::Movie->value,
        ]);

        $response->assertUnauthorized();
    }

    public function test_update_movie_returns_200(): void
    {
        $movie = Movie::factory()->create();

        $payload = [
            'title'        => 'Updated Title',
            'release_year' => $movie->release_year,
            'badge'        => $movie->badge->value,
        ];

        $response = $this->actingAs($this->admin, 'api')
            ->putJson("/api/v1/movies/{$movie->id}", $payload);

        $response->assertOk()
            ->assertJsonFragment(['title' => 'Updated Title']);

        $this->assertDatabaseHas('movies', ['id' => $movie->id, 'title' => 'Updated Title']);
    }

    public function test_update_movie_returns_404_when_not_found(): void
    {
        $payload = [
            'title'        => 'Updated',
            'release_year' => 2020,
            'badge'        => BadgeType::Dubbed->value,
        ];

        $response = $this->actingAs($this->admin, 'api')
            ->putJson('/api/v1/movies/999', $payload);

        $response->assertNotFound();
    }

    public function test_update_movie_requires_title(): void
    {
        $movie = Movie::factory()->create();

        $response = $this->actingAs($this->admin, 'api')
            ->putJson("/api/v1/movies/{$movie->id}", [
                'release_year' => 2020,
                'badge'        => BadgeType::Dubbed->value,
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['title']);
    }

    public function test_update_movie_requires_authentication(): void
    {
        $movie = Movie::factory()->create();

        $response = $this->putJson("/api/v1/movies/{$movie->id}", [
            'title'        => 'Updated',
            'release_year' => 2020,
            'badge'        => BadgeType::Dubbed->value,
        ]);

        $response->assertUnauthorized();
    }

    public function test_delete_movie_returns_204(): void
    {
        $movie = Movie::factory()->create();

        $response = $this->actingAs($this->admin, 'api')
            ->deleteJson("/api/v1/movies/{$movie->id}");

        $response->assertNoContent();

        $this->assertSoftDeleted('movies', ['id' => $movie->id]);
    }

    public function test_delete_movie_returns_404_when_not_found(): void
    {
        $response = $this->actingAs($this->admin, 'api')
            ->deleteJson('/api/v1/movies/999');

        $response->assertNotFound();
    }

    public function test_delete_movie_requires_authentication(): void
    {
        $movie = Movie::factory()->create();

        $response = $this->deleteJson("/api/v1/movies/{$movie->id}");

        $response->assertUnauthorized();
    }

    public function test_restore_movie_returns_200(): void
    {
        $movie = Movie::factory()->create();
        $movie->delete();

        $response = $this->actingAs($this->admin, 'api')
            ->postJson("/api/v1/movies/{$movie->id}/restore");

        $response->assertOk();

        $this->assertDatabaseHas('movies', ['id' => $movie->id, 'deleted_at' => null]);
    }

    public function test_restore_movie_requires_authentication(): void
    {
        $movie = Movie::factory()->create();
        $movie->delete();

        $response = $this->postJson("/api/v1/movies/{$movie->id}/restore");

        $response->assertUnauthorized();
    }
}
