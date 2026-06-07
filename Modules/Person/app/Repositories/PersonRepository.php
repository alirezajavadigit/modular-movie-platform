<?php

namespace Modules\Person\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Modules\Person\Contracts\PersonRepositoryInterface;
use Modules\Person\DTOs\CreatePersonDTO;
use Modules\Person\DTOs\UpdatePersonDTO;
use Modules\Person\Models\Person;

final class PersonRepository implements PersonRepositoryInterface
{
    public function __construct(
        private readonly Person $model,
    ) {}

    public function findById(int $id): ?Person
    {
        return $this->model->newQuery()->find($id);
    }

    public function findBySlug(string $slug): ?Person
    {
        return $this->model->newQuery()->where('slug', $slug)->first();
    }

    public function findByField(string $field, mixed $value): Collection
    {
        return $this->model->newQuery()->where($field, $value)->get();
    }

    public function getAll(): Collection
    {
        return $this->model->newQuery()->latest()->get();
    }

    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->newQuery()->latest()->paginate($perPage);
    }

    public function getActive(int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->newQuery()
            ->active()
            ->orderByDesc('popularity')
            ->paginate($perPage);
    }
    public function getInactive(int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->newQuery()
            ->inactive()
            ->orderByDesc('popularity')
            ->paginate($perPage);
    }

    public function getPopular(int $limit = 20): Collection
    {
        return $this->model->newQuery()
            ->active()
            ->orderByDesc('popularity')
            ->limit($limit)
            ->get();
    }

    public function getByDepartment(string $department, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->newQuery()
            ->byDepartment($department)
            ->active()
            ->orderByDesc('popularity')
            ->paginate($perPage);
    }
    
    public function getByGender(string $gender, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->newQuery()
            ->byGender($gender)
            ->active()
            ->orderByDesc('popularity')
            ->paginate($perPage);
    }

    public function searchAll(string $query, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->newQuery()
            ->where(function ($q) use ($query) {
                $q->where('first_name', 'LIKE', "%{$query}%")
                    ->orWhere('last_name', 'LIKE', "%{$query}%")
                    ->orWhere('slug', 'LIKE', "%{$query}%");
            })
            ->orderByDesc('popularity')
            ->paginate($perPage);
    }

    public function search(string $query, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->newQuery()
            ->active()
            ->where(function ($q) use ($query) {
                $q->where('first_name', 'LIKE', "%{$query}%")
                    ->orWhere('last_name', 'LIKE', "%{$query}%")
                    ->orWhere('slug', 'LIKE', "%{$query}%");
            })
            ->orderByDesc('popularity')
            ->paginate($perPage);
    }

    public function create(CreatePersonDTO $dto): Person
    {
        return $this->model->newQuery()->create([
            'first_name'           => $dto->firstName,
            'last_name'            => $dto->lastName,
            'slug'                 => $dto->slug,
            'biography'            => $dto->biography,
            'date_of_birth'        => $dto->dateOfBirth,
            'date_of_death'        => $dto->dateOfDeath,
            'place_of_birth'       => $dto->placeOfBirth,
            'gender'               => $dto->gender,
            'known_for_department' => $dto->knownForDepartment,
            'popularity'           => $dto->popularity,
            'is_active'            => $dto->isActive,
        ]);
    }

    public function update(int $id, UpdatePersonDTO $dto): Person
    {
        $person = $this->model->newQuery()->findOrFail($id);

        $person->update(array_filter([
            'first_name'           => $dto->firstName,
            'last_name'            => $dto->lastName,
            'slug'                 => $dto->slug,
            'biography'            => $dto->biography,
            'date_of_birth'        => $dto->dateOfBirth,
            'date_of_death'        => $dto->dateOfDeath,
            'place_of_birth'       => $dto->placeOfBirth,
            'gender'               => $dto->gender,
            'known_for_department' => $dto->knownForDepartment,
            'popularity'           => $dto->popularity,
            'is_active'            => $dto->isActive,
        ], fn($v) => !is_null($v)));

        return $person->refresh();
    }

    public function delete(int $id): bool
    {
        return (bool) $this->model->newQuery()->findOrFail($id)->delete();
    }

    public function forceDelete(int $id): bool
    {
        return (bool) $this->model->newQuery()->withTrashed()->findOrFail($id)->forceDelete();
    }

    public function restore(int $id): Person
    {
        $person = $this->model->newQuery()->withTrashed()->findOrFail($id);
        $person->restore();

        return $person->refresh();
    }

    public function getTrashed(int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->newQuery()->onlyTrashed()->latest()->paginate($perPage);
    }

    public function exists(int $id): bool
    {
        return $this->model->newQuery()->whereKey($id)->exists();
    }

    public function activate(int $id): Person
    {
        $person = $this->model->newQuery()->findOrFail($id);
        $person->update(['is_active' => true]);

        return $person->refresh();
    }

    public function deactivate(int $id): Person
    {
        $person = $this->model->newQuery()->findOrFail($id);
        $person->update(['is_active' => false]);

        return $person->refresh();
    }
}
