<?php

namespace Modules\Person\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use LogicException;
use Modules\Person\Contracts\PersonRepositoryInterface;
use Modules\Person\Contracts\PersonServiceInterface;
use Modules\Person\DTOs\CreatePersonDTO;
use Modules\Person\DTOs\UpdatePersonDTO;
use Modules\Person\Models\Person;
use RuntimeException;

final class PersonService implements PersonServiceInterface
{
    public function __construct(
        private readonly PersonRepositoryInterface $repository,
    ) {}

    public function findById(int $id): ?Person
    {
        if ($id <= 0) {
            throw new InvalidArgumentException('Person ID must be a positive integer.');
        }
        return $this->repository->findById($id);
    }

    public function findBySlug(string $slug): ?Person
    {
        if (trim($slug) === '') {
            throw new InvalidArgumentException('Slug cannot be empty.');
        }
        return $this->repository->findBySlug($slug);
    }

    public function getAll(): Collection
    {
        return $this->repository->getAll();
    }

    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        $this->guardPerPage($perPage);
        return $this->repository->paginate($perPage);
    }

    public function getActive(int $perPage = 15): LengthAwarePaginator
    {
        $this->guardPerPage($perPage);
        return $this->repository->getActive($perPage);
    }

    public function getPopular(int $limit = 20): Collection
    {
        if ($limit < 1 || $limit > 100) {
            throw new InvalidArgumentException('Limit must be between 1 and 100.');
        }
        return $this->repository->getPopular($limit);
    }

    public function getByDepartment(string $department, int $perPage = 15): LengthAwarePaginator
    {
        if (trim($department) === '') {
            throw new InvalidArgumentException('Department cannot be empty.');
        }
        $this->guardPerPage($perPage);
        return $this->repository->getByDepartment($department, $perPage);
    }

    public function search(string $query, int $perPage = 15): LengthAwarePaginator
    {
        if (trim($query) === '') {
            throw new InvalidArgumentException('Search query cannot be empty.');
        }
        if (mb_strlen($query) < 2) {
            throw new InvalidArgumentException('Search query must be at least 2 characters.');
        }
        $this->guardPerPage($perPage);
        return $this->repository->search($query, $perPage);
    }

    public function store(CreatePersonDTO $dto): Person
    {
        if (empty($dto->firstName)) {
            throw new InvalidArgumentException('First name is required.');
        }
        if (empty($dto->lastName)) {
            throw new InvalidArgumentException('Last name is required.');
        }
        if (trim($dto->slug) === '') {
            throw new InvalidArgumentException('Slug is required.');
        }

        if ($this->repository->findBySlug($dto->slug)) {
            throw new LogicException('A person with this slug already exists.');
        }

        return DB::transaction(function () use ($dto): Person {
            $person = $this->repository->create($dto);
            if (!$person) {
                throw new RuntimeException('Failed to create person.');
            }
            return $person->refresh();
        });
    }

    public function update(int $id, UpdatePersonDTO $dto): Person
    {
        if ($id <= 0) {
            throw new InvalidArgumentException('Person ID must be a positive integer.');
        }

        $person = $this->repository->findById($id);
        if (!$person) {
            throw new InvalidArgumentException("Person with ID {$id} not found.");
        }

        if (!is_null($dto->slug)) {
            $other = $this->repository->findBySlug($dto->slug);
            if ($other && $other->id !== $id) {
                throw new LogicException('Another person with this slug already exists.');
            }
        }

        if (!is_null($dto->dateOfBirth) && !is_null($dto->dateOfDeath)) {
            if (strtotime($dto->dateOfDeath) < strtotime($dto->dateOfBirth)) {
                throw new LogicException('Date of death cannot be before date of birth.');
            }
        }

        return DB::transaction(function () use ($id, $dto): Person {
            $person = $this->repository->update($id, $dto);
            if (!$person) {
                throw new RuntimeException("Failed to update person with ID {$id}.");
            }
            return $person->refresh();
        });
    }

    public function delete(int $id): bool
    {
        if ($id <= 0) {
            throw new InvalidArgumentException('Person ID must be a positive integer.');
        }
        $person = $this->repository->findById($id);
        if (!$person) {
            throw new InvalidArgumentException("Person with ID {$id} not found.");
        }

        $result = $this->repository->delete($id);
        if (!$result) {
            throw new RuntimeException("Failed to delete person with ID {$id}.");
        }
        return $result;
    }

    public function forceDelete(int $id): bool
    {
        if ($id <= 0) {
            throw new InvalidArgumentException('Person ID must be a positive integer.');
        }
        return DB::transaction(function () use ($id): bool {
            $result = $this->repository->forceDelete($id);
            if (!$result) {
                throw new RuntimeException("Failed to permanently delete person with ID {$id}.");
            }
            return $result;
        });
    }

    public function restore(int $id): Person
    {
        if ($id <= 0) {
            throw new InvalidArgumentException('Person ID must be a positive integer.');
        }
        $person = $this->repository->restore($id);
        if (!$person) {
            throw new RuntimeException("Failed to restore person with ID {$id}.");
        }
        return $person;
    }

    public function getTrashed(int $perPage = 15): LengthAwarePaginator
    {
        $this->guardPerPage($perPage);
        return $this->repository->getTrashed($perPage);
    }

    public function activate(int $id): Person
    {
        $person = $this->repository->findById($id);
        if (!$person) {
            throw new InvalidArgumentException("Person with ID {$id} not found.");
        }
        if ($person->is_active) {
            throw new LogicException('Person is already active.');
        }
        return $this->repository->activate($id);
    }

    public function deactivate(int $id): Person
    {
        $person = $this->repository->findById($id);
        if (!$person) {
            throw new InvalidArgumentException("Person with ID {$id} not found.");
        }
        if (!$person->is_active) {
            throw new LogicException('Person is already inactive.');
        }
        return $this->repository->deactivate($id);
    }

    private function guardPerPage(int $perPage): void
    {
        if ($perPage < 1 || $perPage > 100) {
            throw new InvalidArgumentException('Per page must be between 1 and 100.');
        }
    }
}
