<?php

namespace Modules\Person\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Modules\Person\Contracts\CreditRepositoryInterface;
use Modules\Person\DTOs\CreateCreditDTO;
use Modules\Person\DTOs\UpdateCreditDTO;
use Modules\Person\Models\Credit;

final class CreditRepository implements CreditRepositoryInterface
{
    public function __construct(
        private readonly Credit $model,
    ) {}

    public function findById(int $id): ?Credit
    {
        return $this->model->newQuery()->find($id);
    }

    public function getByPerson(int $personId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->newQuery()
            ->where('person_id', $personId)
            ->orderBy('order')
            ->latest()
            ->paginate($perPage);
    }

    public function getByCreditable(string $creditableType, int $creditableId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->newQuery()
            ->where('creditable_type', $creditableType)
            ->where('creditable_id', $creditableId)
            ->orderBy('order')
            ->paginate($perPage);
    }

    public function getCastFor(string $creditableType, int $creditableId): Collection
    {
        return $this->model->newQuery()
            ->with('person')
            ->where('creditable_type', $creditableType)
            ->where('creditable_id', $creditableId)
            ->cast()
            ->ordered()
            ->get();
    }

    public function getCrewFor(string $creditableType, int $creditableId): Collection
    {
        return $this->model->newQuery()
            ->with('person')
            ->where('creditable_type', $creditableType)
            ->where('creditable_id', $creditableId)
            ->crew()
            ->ordered()
            ->get();
    }

    public function getByRole(string $role, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->newQuery()
            ->where('role', $role)
            ->orderBy('order')
            ->latest()
            ->paginate($perPage);
    }

    public function create(CreateCreditDTO $dto): Credit
    {
        return $this->model->newQuery()->create([
            'person_id'       => $dto->personId,
            'creditable_type' => $dto->creditableType,
            'creditable_id'   => $dto->creditableId,
            'role'            => $dto->role,
            'character_name'  => $dto->characterName,
            'credited_as'     => $dto->creditedAs,
            'department'      => $dto->department,
            'order'           => $dto->order,
        ]);
    }

    public function update(int $id, UpdateCreditDTO $dto): Credit
    {
        $credit = $this->model->newQuery()->findOrFail($id);

        $credit->update(array_filter([
            'role'           => $dto->role,
            'character_name' => $dto->characterName,
            'credited_as'    => $dto->creditedAs,
            'department'     => $dto->department,
            'order'          => $dto->order,
        ], fn($v) => !is_null($v)));

        return $credit->refresh();
    }

    public function delete(int $id): bool
    {
        return (bool) $this->model->newQuery()->findOrFail($id)->delete();
    }

    public function forceDelete(int $id): bool
    {
        return (bool) $this->model->newQuery()->withTrashed()->findOrFail($id)->forceDelete();
    }

    public function restore(int $id): Credit
    {
        $credit = $this->model->newQuery()->withTrashed()->findOrFail($id);
        $credit->restore();

        return $credit->refresh();
    }

    public function exists(int $id): bool
    {
        return $this->model->newQuery()->whereKey($id)->exists();
    }
}
