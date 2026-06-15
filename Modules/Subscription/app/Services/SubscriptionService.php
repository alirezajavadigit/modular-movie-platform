<?php

namespace Modules\Subscription\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use LogicException;
use RuntimeException;
use Modules\Payment\Contracts\PaymentServiceInterface;
use Modules\Payment\DTOs\CreatePaymentDTO;
use Modules\Subscription\Contracts\SubscriptionPlanRepositoryInterface;
use Modules\Subscription\Contracts\SubscriptionRepositoryInterface;
use Modules\Subscription\Contracts\SubscriptionServiceInterface;
use Modules\Subscription\DTOs\CreateSubscriptionDTO;
use Modules\Subscription\DTOs\UpdateSubscriptionDTO;
use Modules\Subscription\Enums\SubscriptionStatus;
use Modules\Subscription\Models\Subscription;

final class SubscriptionService implements SubscriptionServiceInterface
{
    public function __construct(
        private readonly SubscriptionRepositoryInterface     $repository,
        private readonly SubscriptionPlanRepositoryInterface $planRepository,
        private readonly PaymentServiceInterface             $paymentService,
    ) {}

    public function findById(int $id): ?Subscription
    {
        if ($id <= 0) {
            throw new InvalidArgumentException('Subscription ID must be a positive integer.');
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

    public function getAllForUser(int $userId): Collection
    {
        if ($userId <= 0) {
            throw new InvalidArgumentException('User ID must be a positive integer.');
        }

        return $this->repository->getAllForUser($userId);
    }

    public function paginateForUser(int $userId, int $perPage = 15): LengthAwarePaginator
    {
        if ($userId <= 0) {
            throw new InvalidArgumentException('User ID must be a positive integer.');
        }

        if ($perPage < 1 || $perPage > 100) {
            throw new InvalidArgumentException('Per page must be between 1 and 100.');
        }

        return $this->repository->paginateForUser($userId, $perPage);
    }

    public function subscribe(CreateSubscriptionDTO $dto): string
    {
        $plan = $this->planRepository->findById($dto->planId);

        if (!$plan) {
            throw new InvalidArgumentException("Subscription plan with ID {$dto->planId} not found.");
        }

        if (!$plan->status->isActive()) {
            throw new LogicException('The selected subscription plan is not available.');
        }

        return DB::transaction(function () use ($dto): string {
            $subscription = $this->repository->create($dto);

            if (!$subscription) {
                throw new RuntimeException('Failed to create subscription.');
            }

            $subscription->load('plan');

            $paymentDto = new CreatePaymentDTO(
                payable: $subscription,
                userId:  $dto->userId,
                driver:  $dto->driver,
            );

            return $this->paymentService->request($paymentDto);
        });
    }

    public function activate(Subscription $subscription): Subscription
    {
        if (!$subscription->status->isPending()) {
            throw new LogicException('Only pending subscriptions can be activated.');
        }

        return DB::transaction(function () use ($subscription): Subscription {
            $subscription->load('plan');

            return $this->repository->update($subscription, new UpdateSubscriptionDTO(
                status:   SubscriptionStatus::ACTIVE,
                startsAt: now(),
                endsAt:   now()->addDays($subscription->plan->duration_days),
            ));
        });
    }

    public function cancel(Subscription $subscription): Subscription
    {
        if ($subscription->status->isCanceled()) {
            throw new LogicException('Subscription is already canceled.');
        }

        if ($subscription->status->isExpired()) {
            throw new LogicException('Expired subscriptions cannot be canceled.');
        }

        return DB::transaction(fn(): Subscription => $this->repository->update($subscription, new UpdateSubscriptionDTO(
            status: SubscriptionStatus::CANCELED,
        )));
    }

    public function delete(Subscription $subscription): bool
    {
        return DB::transaction(fn(): bool => $this->repository->delete($subscription));
    }

    public function forceDelete(Subscription $subscription): bool
    {
        return DB::transaction(fn(): bool => $this->repository->forceDelete($subscription));
    }

    public function restore(Subscription $subscription): Subscription
    {
        return DB::transaction(fn(): Subscription => $this->repository->restore($subscription));
    }

    public function getTrashed(int $perPage = 15): LengthAwarePaginator
    {
        if ($perPage < 1 || $perPage > 100) {
            throw new InvalidArgumentException('Per page must be between 1 and 100.');
        }

        return $this->repository->getTrashed($perPage);
    }

    public function adminFilter(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        if ($perPage < 1 || $perPage > 100) {
            throw new InvalidArgumentException('Per page must be between 1 and 100.');
        }

        return $this->repository->adminFilter($filters, $perPage);
    }
}
