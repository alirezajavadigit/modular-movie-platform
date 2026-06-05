<?php

namespace Modules\Payment\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Modules\Payment\Contracts\PaymentRepositoryInterface;
use Modules\Payment\DTOs\CreatePaymentDTO;
use Modules\Payment\DTOs\UpdatePaymentDTO;
use Modules\Payment\Models\Payment;

final class PaymentRepository implements PaymentRepositoryInterface
{
    public function __construct(
        private readonly Payment $model,
    ) {}

    public function findById(int $id): ?Payment
    {
        return $this->model->newQuery()->find($id);
    }

    public function findByTransactionId(string $transactionId): ?Payment
    {
        return $this->model->newQuery()->where('transaction_id', $transactionId)->latest()->first();
    }

    public function getAll(): Collection
    {
        return $this->model->newQuery()->latest()->get();
    }

    public function getAllRelatedToUser(int $userId): Collection
    {
        return $this->model->newQuery()->where('user_id', $userId)->latest()->get();
    }

    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->newQuery()->latest()->paginate($perPage);
    }

    public function create(CreatePaymentDTO $dto): Payment
    {
        return $this->model->newQuery()->create([
            'payable_id'   => $dto->payable->getPayableId(),
            'payable_type' => $dto->payable::class,
            'user_id'      => $dto->userId,
            'amount'       => $dto->payable->getPayableAmount(),
            'driver'       => $dto->driver,
            'status'       => $dto->status,
        ]);
    }

    public function updatePaymentStatus(UpdatePaymentDTO $dto): Payment
    {
        $payment = $this->model->newQuery()->findOrFail($dto->paymentId);

        $payment->update([
            'status'         => $dto->status,
            'transaction_id' => $dto->transactionId,
        ]);

        return $payment->refresh();
    }

    public function delete(int $id): bool
    {
        return (bool) $this->model->newQuery()->findOrFail($id)->delete();
    }

    public function forceDelete(int $id): bool
    {
        return (bool) $this->model->newQuery()->withTrashed()->findOrFail($id)->forceDelete();
    }

    public function restore(int $id): Payment
    {
        $payment = $this->model->newQuery()->withTrashed()->findOrFail($id);
        $payment->restore();

        return $payment->refresh();
    }

    public function getTrashed(int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->newQuery()->onlyTrashed()->latest()->paginate($perPage);
    }

    public function exists(int $id): bool
    {
        return $this->model->newQuery()->where('id', $id)->exists();
    }
}
