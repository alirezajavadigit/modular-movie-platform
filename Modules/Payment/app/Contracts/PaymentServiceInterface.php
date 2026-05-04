<?php

namespace Modules\Payment\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Modules\Payment\DTOs\CreatePaymentDTO;
use Modules\Payment\DTOs\UpdatePaymentDTO;
use Modules\Payment\Models\Payment;

interface PaymentServiceInterface
{
    public function findById(int $id): ?Payment;
    public function paginate(int $perPage = 15): LengthAwarePaginator;
    public function getAllRelatedToUser(int $userId): Collection;
    public function request(CreatePaymentDTO $dto): string;
    public function verify(UpdatePaymentDTO $dto): Payment;
    public function delete(int $id): bool;
    public function forceDelete(int $id): bool;
    public function restore(int $id): Payment;
    public function getTrashed(int $perPage = 15): LengthAwarePaginator;
}
