<?php

namespace Modules\Authorization\Services;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Modules\Auth\Contracts\UserRepositoryInterface;
use Modules\Auth\Models\User;
use Modules\Authorization\Contracts\PermissionAssignmentServiceInterface;
use Modules\Authorization\Contracts\PermissionRepositoryInterface;
use Modules\Authorization\DTOs\AssignPermissionDTO;
use Modules\Authorization\DTOs\RevokePermissionDTO;

class PermissionAssignmentService implements PermissionAssignmentServiceInterface
{
    public function __construct(
        private readonly PermissionRepositoryInterface $permissionRepository,
        private readonly UserRepositoryInterface $userRepository
    ) {}
    public function givePermissions(AssignPermissionDTO $dto): User
    {
        $user = $this->resolveUser($dto->userId);
        $permissions = $this->resolvePermissions($dto->permissionNames);
        return $user->givePermissionTo(...$permissions);
    }

    public function revokePermissions(RevokePermissionDTO $dto): User
    {
        $user = $this->resolveUser($dto->userId);
        $permissions = $this->resolvePermissions($dto->permissionNames);
        return $user->revokePermissionTo(...$permissions);
    }
    public function getUserPermissions(int $userId): Collection
    {
        $user = $this->resolveUser($userId);
        return $user->permissions;
    }

    private function resolveUser(int $userId): User
    {
        $user = $this->userRepository->findById($userId);
        if (!$user) {
            throw new ModelNotFoundException("User with id {$userId} not found.");
        }
        return $user;
    }

    private function resolvePermissions(array $permissionNames): Collection
    {
        $permissions = $this->permissionRepository->findByNames($permissionNames);
        if ($permissions->count() !== count($permissionNames)) {
            $missingPermissions = array_diff($permissionNames, $permissions->pluck('name')->toArray());

            throw new ModelNotFoundException(
                'Permissions not found: ' . implode(', ', $missingPermissions)
            );
        }
        return $permissions;
    }
}
