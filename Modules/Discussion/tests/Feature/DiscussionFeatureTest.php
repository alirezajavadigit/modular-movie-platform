<?php

namespace Modules\Discussion\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Auth\Models\User;
use Modules\Discussion\Enums\DiscussionStatus;
use Modules\Discussion\Models\Discussion;
use Modules\Movie\Models\Movie;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class DiscussionFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    protected function givePermission(User $user, string $permission): User
    {
        Permission::findOrCreate($permission, 'api');
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $user->givePermissionTo($permission);

        return $user;
    }

    protected function actingAsApiUser(array $permissions = []): User
    {
        $user = User::factory()->create();

        foreach ($permissions as $permission) {
            $this->givePermission($user, $permission);
        }

        $this->actingAs($user, 'api');

        return $user;
    }

    public function test_guest_cannot_create_discussion(): void
    {
        $movie = Movie::factory()->create();

        $response = $this->postJson('/api/v1/discussions', [
            'discussionable_type' => 'movie',
            'discussionable_id'   => $movie->id,
            'body'                => 'Hello, this is a comment.',
        ]);

        $response->assertStatus(401);
    }

    public function test_authenticated_user_without_permission_cannot_create_discussion(): void
    {
        $this->actingAsApiUser();
        $movie = Movie::factory()->create();

        $response = $this->postJson('/api/v1/discussions', [
            'discussionable_type' => 'movie',
            'discussionable_id'   => $movie->id,
            'body'                => 'Hello, this is a comment.',
        ]);

        $response->assertStatus(403);
    }

    public function test_user_with_create_permission_can_create_discussion(): void
    {
        $user = $this->actingAsApiUser(['discussions.create']);
        $movie = Movie::factory()->create();

        $response = $this->postJson('/api/v1/discussions', [
            'discussionable_type' => 'movie',
            'discussionable_id'   => $movie->id,
            'body'                => 'This is a new discussion body.',
        ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('discussions', [
            'user_id'             => $user->id,
            'discussionable_id'   => $movie->id,
            'discussionable_type' => Movie::class,
            'status'              => DiscussionStatus::PENDING->value,
        ]);
    }

    public function test_create_validates_required_fields(): void
    {
        $this->actingAsApiUser(['discussions.create']);

        $response = $this->postJson('/api/v1/discussions', []);

        $response->assertStatus(422);
    }

    public function test_create_rejects_invalid_discussionable_type(): void
    {
        $this->actingAsApiUser(['discussions.create']);
        $movie = Movie::factory()->create();

        $response = $this->postJson('/api/v1/discussions', [
            'discussionable_type' => 'unknown_type',
            'discussionable_id'   => $movie->id,
            'body'                => 'Some valid body content.',
        ]);

        $response->assertStatus(422);
    }

    public function test_create_rejects_too_short_body(): void
    {
        $this->actingAsApiUser(['discussions.create']);
        $movie = Movie::factory()->create();

        $response = $this->postJson('/api/v1/discussions', [
            'discussionable_type' => 'movie',
            'discussionable_id'   => $movie->id,
            'body'                => 'ab',
        ]);

        $response->assertStatus(422);
    }

    public function test_user_can_reply_to_existing_discussion(): void
    {
        $user = $this->actingAsApiUser(['discussions.create']);
        $movie = Movie::factory()->create();

        $parent = Discussion::factory()->approved()->for($user)->for($movie, 'discussionable')->create();

        $response = $this->postJson('/api/v1/discussions', [
            'discussionable_type' => 'movie',
            'discussionable_id'   => $movie->id,
            'body'                => 'This is a reply to the parent.',
            'parent_id'           => $parent->id,
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('discussions', [
            'parent_id' => $parent->id,
            'user_id'   => $user->id,
        ]);
    }

    public function test_user_with_view_any_can_list_discussions(): void
    {
        $this->actingAsApiUser(['discussions.viewAny']);
        $owner = User::factory()->create();
        $movie = Movie::factory()->create();

        Discussion::factory()->count(3)->approved()->for($owner)->for($movie, 'discussionable')->create();
        Discussion::factory()->count(2)->pending()->for($owner)->for($movie, 'discussionable')->create();
        Discussion::factory()->count(1)->rejected()->for($owner)->for($movie, 'discussionable')->create();

        $response = $this->getJson("/api/v1/discussions/movie/{$movie->id}");

        $response->assertStatus(200);
    }

    public function test_user_without_view_any_cannot_list_discussions(): void
    {
        $this->actingAsApiUser();
        $movie = Movie::factory()->create();

        $response = $this->getJson("/api/v1/discussions/movie/{$movie->id}");

        $response->assertStatus(403);
    }

    public function test_user_with_view_permission_can_show_discussion(): void
    {
        $this->actingAsApiUser(['discussions.view']);
        $owner = User::factory()->create();
        $movie = Movie::factory()->create();

        $discussion = Discussion::factory()->approved()->for($owner)->for($movie, 'discussionable')->create();

        $response = $this->getJson("/api/v1/discussions/{$discussion->id}");

        $response->assertStatus(200);
    }

    public function test_user_without_view_permission_cannot_show_discussion(): void
    {
        $this->actingAsApiUser();
        $owner = User::factory()->create();
        $movie = Movie::factory()->create();

        $discussion = Discussion::factory()->approved()->for($owner)->for($movie, 'discussionable')->create();

        $response = $this->getJson("/api/v1/discussions/{$discussion->id}");

        $response->assertStatus(403);
    }

    public function test_user_with_update_permission_can_update_discussion(): void
    {
        $this->actingAsApiUser(['discussions.update']);
        $owner = User::factory()->create();
        $movie = Movie::factory()->create();

        $discussion = Discussion::factory()->pending()->for($owner)->for($movie, 'discussionable')->create();

        $response = $this->putJson("/api/v1/discussions/{$discussion->id}", [
            'body' => 'Updated body content here.',
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('discussions', [
            'id'   => $discussion->id,
            'body' => 'Updated body content here.',
        ]);
    }

    public function test_user_without_update_permission_cannot_update_discussion(): void
    {
        $this->actingAsApiUser();
        $owner = User::factory()->create();
        $movie = Movie::factory()->create();

        $discussion = Discussion::factory()->pending()->for($owner)->for($movie, 'discussionable')->create();

        $response = $this->putJson("/api/v1/discussions/{$discussion->id}", [
            'body' => 'Hijack attempt.',
        ]);

        $response->assertStatus(403);
    }

    public function test_user_with_delete_permission_can_soft_delete_discussion(): void
    {
        $this->actingAsApiUser(['discussions.delete']);
        $owner = User::factory()->create();
        $movie = Movie::factory()->create();

        $discussion = Discussion::factory()->approved()->for($owner)->for($movie, 'discussionable')->create();

        $response = $this->deleteJson("/api/v1/discussions/{$discussion->id}");

        $response->assertStatus(200);
        $this->assertSoftDeleted('discussions', ['id' => $discussion->id]);
    }

    public function test_user_without_delete_permission_cannot_delete_discussion(): void
    {
        $this->actingAsApiUser();
        $owner = User::factory()->create();
        $movie = Movie::factory()->create();

        $discussion = Discussion::factory()->approved()->for($owner)->for($movie, 'discussionable')->create();

        $response = $this->deleteJson("/api/v1/discussions/{$discussion->id}");

        $response->assertStatus(403);
    }

    public function test_user_with_force_delete_permission_can_force_delete_discussion(): void
    {
        $this->actingAsApiUser(['discussions.forceDelete']);
        $owner = User::factory()->create();
        $movie = Movie::factory()->create();

        $discussion = Discussion::factory()->approved()->for($owner)->for($movie, 'discussionable')->create();

        $response = $this->deleteJson("/api/v1/discussions/{$discussion->id}/force");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('discussions', ['id' => $discussion->id]);
    }

    public function test_user_without_force_delete_permission_cannot_force_delete_discussion(): void
    {
        $this->actingAsApiUser();
        $owner = User::factory()->create();
        $movie = Movie::factory()->create();

        $discussion = Discussion::factory()->approved()->for($owner)->for($movie, 'discussionable')->create();

        $response = $this->deleteJson("/api/v1/discussions/{$discussion->id}/force");

        $response->assertStatus(403);
    }

    public function test_user_with_restore_permission_can_restore_discussion(): void
    {
        $this->actingAsApiUser(['discussions.restore']);
        $owner = User::factory()->create();
        $movie = Movie::factory()->create();

        $discussion = Discussion::factory()->approved()->for($owner)->for($movie, 'discussionable')->create();
        $discussion->delete();

        $response = $this->postJson("/api/v1/discussions/{$discussion->id}/restore");

        $response->assertStatus(200);
        $this->assertDatabaseHas('discussions', [
            'id'         => $discussion->id,
            'deleted_at' => null,
        ]);
    }

    public function test_user_with_approve_permission_can_approve_discussion(): void
    {
        $this->actingAsApiUser(['discussions.approve']);
        $owner = User::factory()->create();
        $movie = Movie::factory()->create();

        $discussion = Discussion::factory()->pending()->for($owner)->for($movie, 'discussionable')->create();

        $response = $this->postJson("/api/v1/discussions/{$discussion->id}/approve");

        $response->assertStatus(200);
        $this->assertDatabaseHas('discussions', [
            'id'     => $discussion->id,
            'status' => DiscussionStatus::APPROVED->value,
        ]);
    }

    public function test_user_without_approve_permission_cannot_approve_discussion(): void
    {
        $this->actingAsApiUser();
        $owner = User::factory()->create();
        $movie = Movie::factory()->create();

        $discussion = Discussion::factory()->pending()->for($owner)->for($movie, 'discussionable')->create();

        $response = $this->postJson("/api/v1/discussions/{$discussion->id}/approve");

        $response->assertStatus(403);
    }

    public function test_user_with_reject_permission_can_reject_discussion(): void
    {
        $this->actingAsApiUser(['discussions.reject']);
        $owner = User::factory()->create();
        $movie = Movie::factory()->create();

        $discussion = Discussion::factory()->pending()->for($owner)->for($movie, 'discussionable')->create();

        $response = $this->postJson("/api/v1/discussions/{$discussion->id}/reject");

        $response->assertStatus(200);
        $this->assertDatabaseHas('discussions', [
            'id'     => $discussion->id,
            'status' => DiscussionStatus::REJECTED->value,
        ]);
    }

    public function test_user_with_mark_as_pending_permission_can_mark_as_pending(): void
    {
        $this->actingAsApiUser(['discussions.markAsPending']);
        $owner = User::factory()->create();
        $movie = Movie::factory()->create();

        $discussion = Discussion::factory()->approved()->for($owner)->for($movie, 'discussionable')->create();

        $response = $this->postJson("/api/v1/discussions/{$discussion->id}/pending");

        $response->assertStatus(200);
        $this->assertDatabaseHas('discussions', [
            'id'     => $discussion->id,
            'status' => DiscussionStatus::PENDING->value,
        ]);
    }

    public function test_user_with_view_pending_permission_can_view_pending_list(): void
    {
        $this->actingAsApiUser(['discussions.viewPending']);
        $owner = User::factory()->create();
        $movie = Movie::factory()->create();

        Discussion::factory()->count(4)->pending()->for($owner)->for($movie, 'discussionable')->create();
        Discussion::factory()->count(2)->approved()->for($owner)->for($movie, 'discussionable')->create();

        $response = $this->getJson('/api/v1/discussions/pending/list');

        $response->assertStatus(200);
    }

    public function test_user_without_view_pending_permission_cannot_view_pending_list(): void
    {
        $this->actingAsApiUser();

        $response = $this->getJson('/api/v1/discussions/pending/list');

        $response->assertStatus(403);
    }

    public function test_user_with_view_any_can_view_user_discussions(): void
    {
        $this->actingAsApiUser(['discussions.viewAny']);
        $target = User::factory()->create();
        $movie = Movie::factory()->create();

        Discussion::factory()->count(3)->for($target)->for($movie, 'discussionable')->create();

        $response = $this->getJson("/api/v1/discussions/user/{$target->id}");

        $response->assertStatus(200);
    }

    public function test_replies_endpoint_returns_approved_children_only(): void
    {
        $user = $this->actingAsApiUser(['discussions.view']);
        $movie = Movie::factory()->create();

        $parent = Discussion::factory()->approved()->for($user)->for($movie, 'discussionable')->create();

        Discussion::factory()->count(3)->approved()->for($user)->for($movie, 'discussionable')
            ->create(['parent_id' => $parent->id]);

        Discussion::factory()->count(2)->pending()->for($user)->for($movie, 'discussionable')
            ->create(['parent_id' => $parent->id]);

        $response = $this->getJson("/api/v1/discussions/{$parent->id}/replies");

        $response->assertStatus(200);
    }

    public function test_show_returns_404_for_missing_discussion(): void
    {
        $this->actingAsApiUser(['discussions.view']);

        $response = $this->getJson('/api/v1/discussions/999999');

        $response->assertStatus(404);
    }

    public function test_update_validates_body_length(): void
    {
        $this->actingAsApiUser(['discussions.update']);
        $owner = User::factory()->create();
        $movie = Movie::factory()->create();

        $discussion = Discussion::factory()->pending()->for($owner)->for($movie, 'discussionable')->create();

        $response = $this->putJson("/api/v1/discussions/{$discussion->id}", [
            'body' => 'ab',
        ]);

        $response->assertStatus(422);
    }

    public function test_update_validates_status_enum(): void
    {
        $this->actingAsApiUser(['discussions.update']);
        $owner = User::factory()->create();
        $movie = Movie::factory()->create();

        $discussion = Discussion::factory()->pending()->for($owner)->for($movie, 'discussionable')->create();

        $response = $this->putJson("/api/v1/discussions/{$discussion->id}", [
            'status' => 'invalid_status',
        ]);

        $response->assertStatus(422);
    }

    public function test_auto_approve_setting_creates_approved_discussions(): void
    {
        config()->set('discussion-module.auto_approve', true);

        $user = $this->actingAsApiUser(['discussions.create']);
        $movie = Movie::factory()->create();

        $response = $this->postJson('/api/v1/discussions', [
            'discussionable_type' => 'movie',
            'discussionable_id'   => $movie->id,
            'body'                => 'Auto-approved comment body here.',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('discussions', [
            'user_id' => $user->id,
            'status'  => DiscussionStatus::APPROVED->value,
        ]);
    }
}
