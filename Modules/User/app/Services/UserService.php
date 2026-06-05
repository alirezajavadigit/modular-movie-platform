<?php

namespace Modules\User\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use LogicException;
use Modules\Auth\Models\User;
use Modules\User\Contracts\UserRepositoryInterface;
use Modules\User\Contracts\UserServiceInterface;
use Modules\User\DTOs\CreateUserDTO;
use Modules\User\DTOs\UpdateUserDTO;
use RuntimeException;

final class UserService implements UserServiceInterface
{
    public function __construct(
        private readonly UserRepositoryInterface $repository,
    ) {}

    public function findById(int $id): ?User
    {
        if ($id <= 0) {
            throw new InvalidArgumentException('User ID must be a positive integer.');
        }

        return $this->repository->findById($id);
    }

    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->paginate($this->guardPerPage($perPage));
    }

    public function search(string $term, int $perPage = 15): LengthAwarePaginator
    {
        if (mb_strlen(trim($term)) < 2) {
            throw new InvalidArgumentException('Search term must be at least 2 characters.');
        }

        return $this->repository->search(trim($term), $this->guardPerPage($perPage));
    }

    public function store(CreateUserDTO $dto): User
    {
        if (trim($dto->name) === '') {
            throw new InvalidArgumentException('User name is required.');
        }

        if (trim($dto->password) === '') {
            throw new InvalidArgumentException('User password is required.');
        }

        if (is_null($dto->email) && is_null($dto->phone)) {
            throw new InvalidArgumentException('A user must have an email or a phone.');
        }

        if (! is_null($dto->email) && $this->repository->findByEmail($dto->email)) {
            throw new LogicException('A user with this email already exists.');
        }

        if (! is_null($dto->phone) && $this->repository->findByPhone($dto->phone)) {
            throw new LogicException('A user with this phone already exists.');
        }

        return DB::transaction(function () use ($dto): User {
            $user = $this->repository->create($dto);

            if (! $user) {
                throw new RuntimeException('Failed to create user.');
            }

            return $user;
        });
    }

    public function update(int $id, UpdateUserDTO $dto): User
    {
        if ($id <= 0) {
            throw new InvalidArgumentException('User ID must be a positive integer.');
        }

        if (! $this->repository->exists($id)) {
            throw new InvalidArgumentException("User with ID {$id} not found.");
        }

        return DB::transaction(fn (): User => $this->repository->update($id, $dto));
    }

    public function delete(int $id): bool
    {
        if ($id <= 0) {
            throw new InvalidArgumentException('User ID must be a positive integer.');
        }

        if (! $this->repository->exists($id)) {
            throw new InvalidArgumentException("User with ID {$id} not found.");
        }

        return DB::transaction(fn (): bool => $this->repository->delete($id));
    }

    public function forceDelete(int $id): bool
    {
        if ($id <= 0) {
            throw new InvalidArgumentException('User ID must be a positive integer.');
        }

        return DB::transaction(fn (): bool => $this->repository->forceDelete($id));
    }

    public function restore(int $id): User
    {
        if ($id <= 0) {
            throw new InvalidArgumentException('User ID must be a positive integer.');
        }

        return DB::transaction(fn (): User => $this->repository->restore($id));
    }

    public function getTrashed(int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->getTrashed($this->guardPerPage($perPage));
    }

    private function guardPerPage(int $perPage): int
    {
        if ($perPage < 1 || $perPage > 100) {
            throw new InvalidArgumentException('Per page must be between 1 and 100.');
        }

        return $perPage;
    }
}
