<?php

namespace Modules\Payment\Tests\Unit;

use InvalidArgumentException;
use LogicException;
use Mockery;
use Modules\Payment\Contracts\PaymentRepositoryInterface;
use Modules\Payment\DTOs\UpdatePaymentDTO;
use Modules\Payment\Enums\PaymentStatus;
use Modules\Payment\Models\Payment;
use Modules\Payment\Services\PaymentService;
use Tests\TestCase;

class PaymentServiceTest extends TestCase
{
    private PaymentRepositoryInterface $repository;
    private PaymentService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = Mockery::mock(PaymentRepositoryInterface::class);
        $this->service    = new PaymentService($this->repository);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        Mockery::close();
    }

    public function test_find_by_id_throws_when_id_is_zero(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->service->findById(0);
    }

    public function test_find_by_id_returns_null_when_not_found(): void
    {
        $this->repository->shouldReceive('findById')->with(1)->once()->andReturn(null);

        $result = $this->service->findById(1);

        $this->assertNull($result);
    }

    public function test_paginate_throws_when_per_page_is_zero(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->service->paginate(0);
    }

    public function test_delete_throws_when_payment_not_found(): void
    {
        $this->repository->shouldReceive('findById')->with(1)->once()->andReturn(null);

        $this->expectException(InvalidArgumentException::class);

        $this->service->delete(1);
    }

    public function test_verify_throws_logic_exception_when_payment_is_not_pending(): void
    {
        $payment = Mockery::mock(Payment::class);
        $payment->shouldReceive('getAttribute')->with('status')->andReturn(PaymentStatus::SUCCESS);

        $this->repository->shouldReceive('findById')->with(1)->once()->andReturn($payment);

        $dto = new UpdatePaymentDTO(
            paymentId: 1,
            status: PaymentStatus::PENDING,
            transactionId: 'txn_123',
        );

        $this->expectException(LogicException::class);
        $this->service->verify($dto);
    }
}
