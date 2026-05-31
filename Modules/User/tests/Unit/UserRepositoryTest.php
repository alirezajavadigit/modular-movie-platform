<?php

namespace Modules\User\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Hash;
use Modules\Auth\Models\User;
use Modules\Authorization\Models\Role;
use Modules\User\DTOs\CreateUserDTO;
use Modules\User\DTOs\UpdateUserDTO;
use Modules\User\Repositories\UserRepository;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class UserRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private UserRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = new UserRepository(new User());
    }

    public function test_find_by_id_returns_user(): void
    {
        $user = User::factory()->create();

        $this->assertEquals($user->id, $this->repository->findById($user->id)->id);
    }

    public function test_find_by_id_returns_null_when_missing(): void
    {
        $this->assertNull($this->repository->findById(999));
    }

    public function test_create_persists_and_hashes_password(): void
    {
        $dto = new CreateUserDTO(
            name: 'Jane Doe',
            email: 'jane@example.com',
            phone: null,
            password: 'secret-password',
        );

        $user = $this->repository->create($dto);

        $this->assertDatabaseHas('users', ['id' => $user->id, 'email' => 'jane@example.com']);
        $this->assertTrue(Hash::check('secret-password', $user->password));
    }

    public function test_update_changes_only_provided_fields(): void
    {
        $user = User::factory()->create(['name' => 'Original', 'email' => 'orig@example.com']);

        $updated = $this->repository->update($user->id, new UpdateUserDTO(name: 'Changed'));

        $this->assertEquals('Changed', $updated->name);
        $this->assertEquals('orig@example.com', $updated->email);
    }

    public function test_delete_soft_deletes_user(): void
    {
        $user = User::factory()->create();

        $this->assertTrue($this->repository->delete($user->id));
        $this->assertSoftDeleted('users', ['id' => $user->id]);
    }

    public function test_restore_brings_back_user(): void
    {
        $user = User::factory()->create();
        $user->delete();

        $restored = $this->repository->restore($user->id);

        $this->assertNull($restored->deleted_at);
    }

    public function test_force_delete_removes_user(): void
    {
        $user = User::factory()->create();
        $user->delete();

        $this->assertTrue($this->repository->forceDelete($user->id));
        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }

    public function test_search_matches_name_email_or_phone(): void
    {
        User::factory()->create(['name' => 'Findable Person', 'email' => 'a@example.com']);
        User::factory()->create(['name' => 'Someone Else', 'email' => 'b@example.com']);

        $result = $this->repository->search('Findable', 10);

        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
        $this->assertEquals(1, $result->total());
    }

    public function test_get_trashed_returns_only_deleted(): void
    {
        User::factory()->create();
        $deleted = User::factory()->create();
        $deleted->delete();

        $result = $this->repository->getTrashed(10);

        $this->assertEquals(1, $result->total());
    }

    public function test_sync_roles_assigns_roles(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Role::findOrCreate('editor', 'api');
        $user = User::factory()->create();

        $this->repository->syncRoles($user->id, ['editor']);

        $this->assertTrue($user->fresh()->hasRole('editor'));
    }
}
