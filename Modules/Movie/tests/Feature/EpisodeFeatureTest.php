<?php

namespace Modules\Movie\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\Auth\Models\User;
use Modules\Authorization\Models\Role;
use Modules\Movie\Models\Episode;
use Modules\Movie\Models\Movie;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

final class EpisodeFeatureTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Movie $serial;

    protected function setUp(): void
    {
        parent::setUp();
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        Role::create(['name' => 'super-admin', 'guard_name' => 'api']);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('super-admin');

        $this->serial = Movie::factory()->serial()->create();
    }

    public function test_public_get_all_episodes_returns_200(): void
    {
        Episode::factory()->count(3)->create(['movie_id' => $this->serial->id]);

        $response = $this->getJson("/api/v1/movies/{$this->serial->id}/episodes");

        $response->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_public_get_all_episodes_returns_empty_when_none_exist(): void
    {
        $response = $this->getJson("/api/v1/movies/{$this->serial->id}/episodes");

        $response->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_public_get_episode_by_id_returns_200(): void
    {
        $episode = Episode::factory()->create(['movie_id' => $this->serial->id]);

        $response = $this->getJson("/api/v1/movies/{$this->serial->id}/episodes/{$episode->id}");

        $response->assertOk()
            ->assertJsonFragment(['title' => $episode->title]);
    }

    public function test_public_get_episode_returns_404_when_not_found(): void
    {
        $response = $this->getJson("/api/v1/movies/{$this->serial->id}/episodes/999");

        $response->assertNotFound();
    }

    public function test_public_get_episode_returns_404_when_belongs_to_different_serial(): void
    {
        $otherSerial = Movie::factory()->serial()->create();
        $episode = Episode::factory()->create(['movie_id' => $otherSerial->id]);

        $response = $this->getJson("/api/v1/movies/{$this->serial->id}/episodes/{$episode->id}");

        $response->assertNotFound();
    }

    public function test_public_routes_do_not_require_authentication(): void
    {
        $episode = Episode::factory()->create(['movie_id' => $this->serial->id]);

        $this->getJson("/api/v1/movies/{$this->serial->id}/episodes")->assertOk();
        $this->getJson("/api/v1/movies/{$this->serial->id}/episodes/{$episode->id}")->assertOk();
    }

    public function test_create_episode_returns_201(): void
    {
        $payload = [
            'season_number'  => 1,
            'episode_number' => 1,
            'title'          => 'Pilot',
            'description'    => 'The first episode',
            'download_links' => ['https://example.com/ep1'],
        ];

        $response = $this->actingAs($this->admin, 'api')
            ->postJson("/api/v1/movies/{$this->serial->id}/episodes", $payload);

        $response->assertCreated()
            ->assertJsonFragment(['title' => 'Pilot']);

        $this->assertDatabaseHas('episodes', [
            'movie_id' => $this->serial->id,
            'title'    => 'Pilot',
        ]);
    }

    public function test_create_episode_with_poster_file_upload(): void
    {
        Storage::fake(config('movie.upload.disk', 'public'));

        $payload = [
            'season_number'  => 1,
            'episode_number' => 1,
            'title'          => 'Upload Test',
            'poster_file'    => UploadedFile::fake()->image('ep-poster.jpg', 400, 300),
        ];

        $response = $this->actingAs($this->admin, 'api')
            ->postJson("/api/v1/movies/{$this->serial->id}/episodes", $payload);

        $response->assertCreated();

        $episode = Episode::where('title', 'Upload Test')->first();
        $this->assertNotNull($episode->poster);
    }

    public function test_create_episode_fails_on_non_serial(): void
    {
        $movie = Movie::factory()->movie()->create();

        $payload = [
            'season_number'  => 1,
            'episode_number' => 1,
            'title'          => 'Pilot',
        ];

        $response = $this->actingAs($this->admin, 'api')
            ->postJson("/api/v1/movies/{$movie->id}/episodes", $payload);

        $response->assertStatus(422);
    }

    public function test_create_episode_requires_title(): void
    {
        $payload = [
            'season_number'  => 1,
            'episode_number' => 1,
        ];

        $response = $this->actingAs($this->admin, 'api')
            ->postJson("/api/v1/movies/{$this->serial->id}/episodes", $payload);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['title']);
    }

    public function test_create_episode_requires_season_number(): void
    {
        $payload = [
            'episode_number' => 1,
            'title'          => 'Pilot',
        ];

        $response = $this->actingAs($this->admin, 'api')
            ->postJson("/api/v1/movies/{$this->serial->id}/episodes", $payload);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['season_number']);
    }

    public function test_create_episode_requires_episode_number(): void
    {
        $payload = [
            'season_number' => 1,
            'title'         => 'Pilot',
        ];

        $response = $this->actingAs($this->admin, 'api')
            ->postJson("/api/v1/movies/{$this->serial->id}/episodes", $payload);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['episode_number']);
    }

    public function test_create_episode_requires_authentication(): void
    {
        $response = $this->postJson("/api/v1/movies/{$this->serial->id}/episodes", [
            'season_number'  => 1,
            'episode_number' => 1,
            'title'          => 'Pilot',
        ]);

        $response->assertUnauthorized();
    }

    public function test_update_episode_returns_200(): void
    {
        $episode = Episode::factory()->create(['movie_id' => $this->serial->id]);

        $payload = [
            'season_number'  => $episode->season_number,
            'episode_number' => $episode->episode_number,
            'title'          => 'Updated Title',
        ];

        $response = $this->actingAs($this->admin, 'api')
            ->putJson("/api/v1/movies/{$this->serial->id}/episodes/{$episode->id}", $payload);

        $response->assertOk()
            ->assertJsonFragment(['title' => 'Updated Title']);

        $this->assertDatabaseHas('episodes', ['id' => $episode->id, 'title' => 'Updated Title']);
    }

    public function test_update_episode_returns_404_when_not_found(): void
    {
        $payload = [
            'season_number'  => 1,
            'episode_number' => 1,
            'title'          => 'Updated',
        ];

        $response = $this->actingAs($this->admin, 'api')
            ->putJson("/api/v1/movies/{$this->serial->id}/episodes/999", $payload);

        $response->assertNotFound();
    }

    public function test_update_episode_requires_authentication(): void
    {
        $episode = Episode::factory()->create(['movie_id' => $this->serial->id]);

        $response = $this->putJson("/api/v1/movies/{$this->serial->id}/episodes/{$episode->id}", [
            'season_number'  => 1,
            'episode_number' => 1,
            'title'          => 'Updated',
        ]);

        $response->assertUnauthorized();
    }

    public function test_delete_episode_returns_204(): void
    {
        $episode = Episode::factory()->create(['movie_id' => $this->serial->id]);

        $response = $this->actingAs($this->admin, 'api')
            ->deleteJson("/api/v1/movies/{$this->serial->id}/episodes/{$episode->id}");

        $response->assertNoContent();

        $this->assertSoftDeleted('episodes', ['id' => $episode->id]);
    }

    public function test_delete_episode_returns_404_when_not_found(): void
    {
        $response = $this->actingAs($this->admin, 'api')
            ->deleteJson("/api/v1/movies/{$this->serial->id}/episodes/999");

        $response->assertNotFound();
    }

    public function test_delete_episode_requires_authentication(): void
    {
        $episode = Episode::factory()->create(['movie_id' => $this->serial->id]);

        $response = $this->deleteJson("/api/v1/movies/{$this->serial->id}/episodes/{$episode->id}");

        $response->assertUnauthorized();
    }

    public function test_restore_episode_returns_200(): void
    {
        $episode = Episode::factory()->create(['movie_id' => $this->serial->id]);
        $episode->delete();

        $response = $this->actingAs($this->admin, 'api')
            ->postJson("/api/v1/movies/{$this->serial->id}/episodes/{$episode->id}/restore");

        $response->assertOk();

        $this->assertDatabaseHas('episodes', ['id' => $episode->id, 'deleted_at' => null]);
    }

    public function test_restore_episode_requires_authentication(): void
    {
        $episode = Episode::factory()->create(['movie_id' => $this->serial->id]);
        $episode->delete();

        $response = $this->postJson("/api/v1/movies/{$this->serial->id}/episodes/{$episode->id}/restore");

        $response->assertUnauthorized();
    }
}
