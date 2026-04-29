<?php

namespace Modules\Person\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Modules\Person\DTOs\CreateCreditDTO;
use Modules\Person\DTOs\UpdateCreditDTO;
use Modules\Person\Models\Credit;

interface CreditServiceInterface
{
    public function findById(int $id): ?Credit;

    public function getByPerson(int $personId, int $perPage = 15): LengthAwarePaginator;

    public function getByCreditable(string $creditableType, int $creditableId, int $perPage = 15): LengthAwarePaginator;

    public function getCastFor(string $creditableType, int $creditableId): Collection;

    public function getCrewFor(string $creditableType, int $creditableId): Collection;

    public function getByRole(string $role, int $perPage = 15): LengthAwarePaginator;

    public function store(CreateCreditDTO $dto): Credit;

    public function update(int $id, UpdateCreditDTO $dto): Credit;

    public function delete(int $id): bool;

    public function forceDelete(int $id): bool;

    public function restore(int $id): Credit;
}
