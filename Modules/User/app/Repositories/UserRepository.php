<?php

namespace Modules\User\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Auth\Models\User;
use Modules\User\Contracts\UserRepositoryInterface;
use Modules\User\DTOs\CreateUserDTO;
use Modules\User\DTOs\UpdateUserDTO;

final class UserRepository implements UserRepositoryInterface
{
    public function __construct(
        private readonly User $model,
    ) {}

    public function findById(int $id): ?User
    {
        return $this->model->newQuery()->with('roles')->find($id);
    }

    public function findByEmail(string $email): ?User
    {
        return $this->model->newQuery()->where('email', $email)->first();
    }

    public function findByPhone(string $phone): ?User
    {
        return $this->model->newQuery()->where('phone', $phone)->first();
    }

    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->newQuery()->with('roles')->latest()->paginate($perPage);
    }

    public function search(string $term, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->newQuery()
            ->with('roles')
            ->where(function ($query) use ($term) {
                $query->where('name', 'like', "%{$term}%")
                    ->orWhere('email', 'like', "%{$term}%")
                    ->orWhere('phone', 'like', "%{$term}%");
            })
            ->latest()
            ->paginate($perPage);
    }

    public function create(CreateUserDTO $dto): User
    {
        $user = $this->model->newQuery()->create([
            'name'     => $dto->name,
            'email'    => $dto->email,
            'phone'    => $dto->phone,
            'password' => $dto->password,
        ]);

        if (! empty($dto->roles)) {
            $user->syncRoles($dto->roles);
        }

        return $user->load('roles');
    }

    public function update(int $id, UpdateUserDTO $dto): User
    {
        $user = $this->model->newQuery()->findOrFail($id);

        $attributes = array_filter([
            'name'     => $dto->name,
            'email'    => $dto->email,
            'phone'    => $dto->phone,
            'password' => $dto->password,
        ], fn ($value) => ! is_null($value));

        if (! empty($attributes)) {
            $user->update($attributes);
        }

        if (! is_null($dto->roles)) {
            $user->syncRoles($dto->roles);
        }

        return $user->refresh()->load('roles');
    }

    public function delete(int $id): bool
    {
        return (bool) $this->model->newQuery()->findOrFail($id)->delete();
    }

    public function forceDelete(int $id): bool
    {
        return (bool) $this->model->newQuery()->withTrashed()->findOrFail($id)->forceDelete();
    }

    public function restore(int $id): User
    {
        $user = $this->model->newQuery()->withTrashed()->findOrFail($id);
        $user->restore();

        return $user->refresh()->load('roles');
    }

    public function getTrashed(int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->newQuery()->with('roles')->onlyTrashed()->latest()->paginate($perPage);
    }

    public function syncRoles(int $id, array $roles): User
    {
        $user = $this->model->newQuery()->findOrFail($id);
        $user->syncRoles($roles);

        return $user->refresh()->load('roles');
    }

    public function exists(int $id): bool
    {
        return $this->model->newQuery()->where('id', $id)->exists();
    }
}
