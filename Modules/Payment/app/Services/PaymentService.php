<?php

namespace Modules\Payment\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use LogicException;
use RuntimeException;
use Modules\Payment\Contracts\GatewayInterface;
use Modules\Payment\Contracts\PaymentRepositoryInterface;
use Modules\Payment\Contracts\PaymentServiceInterface;
use Modules\Payment\DTOs\CreatePaymentDTO;
use Modules\Payment\DTOs\UpdatePaymentDTO;
use Modules\Payment\Enums\PaymentStatus;
use Modules\Payment\Models\Payment;

final class PaymentService implements PaymentServiceInterface
{
    public function __construct(
        private readonly PaymentRepositoryInterface $repository,
    ) {}

    public function findById(int $id): ?Payment
    {
        if ($id <= 0) {
            throw new InvalidArgumentException('Payment ID must be a positive integer.');
        }

        return $this->repository->findById($id);
    }

    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        if ($perPage < 1 || $perPage > 100) {
            throw new InvalidArgumentException('Per page must be between 1 and 100.');
        }

        return $this->repository->paginate($perPage);
    }

    public function getAllRelatedToUser(int $userId): Collection
    {
        if ($userId <= 0) {
            throw new InvalidArgumentException('User ID must be a positive integer.');
        }

        return $this->repository->getAllRelatedToUser($userId);
    }

    public function request(CreatePaymentDTO $dto): string
    {
        return DB::transaction(function () use ($dto): string {
            $payment = $this->repository->create($dto);

            if (!$payment) {
                throw new RuntimeException('Failed to create payment record.');
            }

            $gateway = $this->resolveGateway($dto->driver);

            return $gateway->purchase($dto);
        });
    }

    public function verify(UpdatePaymentDTO $dto): Payment
    {
        if ($dto->paymentId <= 0) {
            throw new InvalidArgumentException('Payment ID must be a positive integer.');
        }

        $payment = $this->repository->findById($dto->paymentId);

        if (!$payment) {
            throw new InvalidArgumentException("Payment with ID {$dto->paymentId} not found.");
        }

        if (!$payment->status->isPending()) {
            throw new LogicException('Only pending payments can be verified.');
        }

        return DB::transaction(function () use ($dto, $payment): Payment {
            $gateway  = $this->resolveGateway($payment->driver);
            $verified = $gateway->verify($dto->transactionId ?? '');

            $updateDto = new UpdatePaymentDTO(
                paymentId:     $dto->paymentId,
                status:        $verified ? PaymentStatus::SUCCESS : PaymentStatus::FAILED,
                transactionId: $dto->transactionId,
            );

            return $this->repository->updatePaymentStatus($updateDto);
        });
    }

    public function delete(int $id): bool
    {
        if ($id <= 0) {
            throw new InvalidArgumentException('Payment ID must be a positive integer.');
        }

        $payment = $this->repository->findById($id);

        if (!$payment) {
            throw new InvalidArgumentException("Payment with ID {$id} not found.");
        }

        return DB::transaction(fn(): bool => $this->repository->delete($id));
    }

    public function forceDelete(int $id): bool
    {
        if ($id <= 0) {
            throw new InvalidArgumentException('Payment ID must be a positive integer.');
        }

        return DB::transaction(fn(): bool => $this->repository->forceDelete($id));
    }

    public function restore(int $id): Payment
    {
        if ($id <= 0) {
            throw new InvalidArgumentException('Payment ID must be a positive integer.');
        }

        return DB::transaction(fn(): Payment => $this->repository->restore($id));
    }

    public function getTrashed(int $perPage = 15): LengthAwarePaginator
    {
        if ($perPage < 1 || $perPage > 100) {
            throw new InvalidArgumentException('Per page must be between 1 and 100.');
        }

        return $this->repository->getTrashed($perPage);
    }

    private function resolveGateway(string $driver): GatewayInterface
    {
        $gateway = app("payment.gateway.{$driver}");

        if (!$gateway instanceof GatewayInterface) {
            throw new InvalidArgumentException("Unknown payment driver: {$driver}");
        }

        return $gateway;
    }
}
