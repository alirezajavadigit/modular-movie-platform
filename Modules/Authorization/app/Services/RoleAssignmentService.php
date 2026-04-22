<?php

namespace Modules\Authorization\Services;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Modules\Auth\Contracts\UserRepositoryInterface;
use Modules\Auth\Models\User;
use Modules\Authorization\Contracts\RoleAssignmentServiceInterface;
use Modules\Authorization\Contracts\RoleRepositoryInterface;
use Modules\Authorization\DTOs\AssignRoleDTO;
use Modules\Authorization\DTOs\RevokeRoleDTO;
use Modules\Authorization\DTOs\SyncRoleDTO;

class RoleAssignmentService implements RoleAssignmentServiceInterface
{
    public function __construct(
        private readonly RoleRepositoryInterface $roleRepository,
        private readonly UserRepositoryInterface $userRepository
    ) {}

    public function assignRoles(AssignRoleDTO $dto): User
    {
        $user = $this->resolveUser($dto->userId);
        $roles = $this->resolveRoles($dto->roleNames);

        $user->assignRole(...$roles);

        return $user->load('roles');
    }

    public function revokeRoles(RevokeRoleDTO $dto): User
    {
        $user = $this->resolveUser($dto->userId);
        $roles = $this->resolveRoles($dto->roleNames);

        $user->removeRole(...$roles);

        return $user->load('roles');
    }

    public function syncRoles(SyncRoleDTO $dto): User
    {
        $user = $this->resolveUser($dto->userId);
        $roles = $this->resolveRoles($dto->roleNames);

        $user->syncRoles(...$roles);

        return $user->load('roles');
    }

    public function getUserRoles(int $userId): Collection
    {
        $user = $this->resolveUser($userId);

        return $user->roles;
    }

    private function resolveUser(int $userId): User
    {
        $user = $this->userRepository->findById($userId);

        if (!$user) {
            throw new ModelNotFoundException("User with id {$userId} not found.");
        }

        return $user;
    }

    private function resolveRoles(array $roleNames): Collection
    {
        $roles = $this->roleRepository->findByNames($roleNames);

        if ($roles->count() !== count($roleNames)) {
            $missingRoles = array_diff($roleNames, $roles->pluck('name')->toArray());

            throw new ModelNotFoundException(
                'Roles not found: ' . implode(', ', $missingRoles)
            );
        }

        return $roles;
    }
}
