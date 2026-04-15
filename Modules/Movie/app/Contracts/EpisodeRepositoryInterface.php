<?php

namespace Modules\Movie\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Modules\Movie\DTOs\CreateEpisodeDTO;
use Modules\Movie\DTOs\UpdateEpisodeDTO;
use Modules\Movie\Models\Episode;

interface EpisodeRepositoryInterface
{
    public function getAllByMovie(int $movieId): Collection;

    public function findById(int $id): ?Episode;

    public function create(CreateEpisodeDTO $dto): Episode;

    public function update(int $id, UpdateEpisodeDTO $dto): Episode;

    public function delete(int $id): bool;

    public function restore(int $id): Episode;

    public function forceDelete(int $id): bool;
}
