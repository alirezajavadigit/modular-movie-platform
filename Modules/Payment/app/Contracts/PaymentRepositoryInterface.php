<?php

namespace Modules\Payment\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Modules\Payment\DTOs\CreatePaymentDTO;
use Modules\Payment\DTOs\UpdatePaymentDTO;
use Modules\Payment\Models\Payment;

interface PaymentRepositoryInterface
{
    public function findById(int $id): ?Payment;
    public function findByTransactionId(string $transactionId): ?Payment;
    public function getAll(): Collection;
    public function getAllRelatedToUser(int $userId): Collection;
    public function paginate(int $perPage = 15): LengthAwarePaginator;
    public function create(CreatePaymentDTO $dto): Payment;
    public function updatePaymentStatus(UpdatePaymentDTO $dto): Payment;
    public function delete(int $id): bool;
    public function forceDelete(int $id): bool;
    public function restore(int $id): Payment;
    public function getTrashed(int $perPage = 15): LengthAwarePaginator;
    public function exists(int $id): bool;
}
