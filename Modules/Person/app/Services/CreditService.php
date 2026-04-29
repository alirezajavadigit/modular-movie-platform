<?php

namespace Modules\Person\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Person\Contracts\CreditRepositoryInterface;
use Modules\Person\Contracts\CreditServiceInterface;
use Modules\Person\Contracts\PersonRepositoryInterface;
use Modules\Person\DTOs\CreateCreditDTO;
use Modules\Person\DTOs\UpdateCreditDTO;
use Modules\Person\Enums\CreditRole;
use Modules\Person\Models\Credit;
use RuntimeException;

final class CreditService implements CreditServiceInterface
{
    public function __construct(
        private readonly CreditRepositoryInterface $repository,
        private readonly PersonRepositoryInterface $personRepository,
    ) {}

    public function findById(int $id): ?Credit
    {
        if ($id <= 0) {
            throw new InvalidArgumentException('Credit ID must be a positive integer.');
        }
        return $this->repository->findById($id);
    }

    public function getByPerson(int $personId, int $perPage = 15): LengthAwarePaginator
    {
        if ($personId <= 0) {
            throw new InvalidArgumentException('Person ID must be a positive integer.');
        }
        $this->guardPerPage($perPage);
        return $this->repository->getByPerson($personId, $perPage);
    }

    public function getByCreditable(string $creditableType, int $creditableId, int $perPage = 15): LengthAwarePaginator
    {
        $this->guardCreditable($creditableType, $creditableId);
        $this->guardPerPage($perPage);
        return $this->repository->getByCreditable($creditableType, $creditableId, $perPage);
    }

    public function getCastFor(string $creditableType, int $creditableId): Collection
    {
        $this->guardCreditable($creditableType, $creditableId);
        return $this->repository->getCastFor($creditableType, $creditableId);
    }

    public function getCrewFor(string $creditableType, int $creditableId): Collection
    {
        $this->guardCreditable($creditableType, $creditableId);
        return $this->repository->getCrewFor($creditableType, $creditableId);
    }

    public function getByRole(string $role, int $perPage = 15): LengthAwarePaginator
    {
        $this->guardRole($role);
        $this->guardPerPage($perPage);
        return $this->repository->getByRole($role, $perPage);
    }

    public function store(CreateCreditDTO $dto): Credit
    {
        $this->guardRole($dto->role);
        $this->guardCreditable($dto->creditableType, $dto->creditableId);

        if (!$this->personRepository->exists($dto->personId)) {
            throw new InvalidArgumentException("Person with ID {$dto->personId} not found.");
        }

        return DB::transaction(function () use ($dto): Credit {
            $credit = $this->repository->create($dto);
            if (!$credit) {
                throw new RuntimeException('Failed to create credit.');
            }
            return $credit->refresh();
        });
    }

    public function update(int $id, UpdateCreditDTO $dto): Credit
    {
        if ($id <= 0) {
            throw new InvalidArgumentException('Credit ID must be a positive integer.');
        }
        if (!is_null($dto->role)) {
            $this->guardRole($dto->role);
        }

        $credit = $this->repository->findById($id);
        if (!$credit) {
            throw new InvalidArgumentException("Credit with ID {$id} not found.");
        }

        return DB::transaction(function () use ($id, $dto): Credit {
            return $this->repository->update($id, $dto)->refresh();
        });
    }

    public function delete(int $id): bool
    {
        if ($id <= 0) {
            throw new InvalidArgumentException('Credit ID must be a positive integer.');
        }
        if (!$this->repository->exists($id)) {
            throw new InvalidArgumentException("Credit with ID {$id} not found.");
        }

        $result = $this->repository->delete($id);
        if (!$result) {
            throw new RuntimeException("Failed to delete credit with ID {$id}.");
        }
        return $result;
    }

    public function forceDelete(int $id): bool
    {
        if ($id <= 0) {
            throw new InvalidArgumentException('Credit ID must be a positive integer.');
        }
        return DB::transaction(fn() => $this->repository->forceDelete($id));
    }

    public function restore(int $id): Credit
    {
        if ($id <= 0) {
            throw new InvalidArgumentException('Credit ID must be a positive integer.');
        }
        return $this->repository->restore($id);
    }

    private function guardPerPage(int $perPage): void
    {
        if ($perPage < 1 || $perPage > 100) {
            throw new InvalidArgumentException('Per page must be between 1 and 100.');
        }
    }

    private function guardRole(string $role): void
    {
        if (!in_array($role, CreditRole::values(), true)) {
            throw new InvalidArgumentException("Invalid role '{$role}'. Allowed: " . implode(', ', CreditRole::values()));
        }
    }

    private function guardCreditable(string $type, int $id): void
    {
        if ($id <= 0) {
            throw new InvalidArgumentException('Creditable ID must be a positive integer.');
        }
        if (trim($type) === '') {
            throw new InvalidArgumentException('Creditable type cannot be empty.');
        }
        $map = Relation::morphMap();
        if (!empty($map) && !array_key_exists($type, $map) && !in_array($type, $map, true)) {
            throw new InvalidArgumentException("Creditable type '{$type}' is not registered in the morph map.");
        }
    }
}
