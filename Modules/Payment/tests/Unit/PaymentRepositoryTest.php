<?php

namespace Modules\Payment\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Auth\Models\User;
use Modules\Payment\Models\Payment;
use Modules\Payment\Repositories\PaymentRepository;
use Tests\TestCase;

class PaymentRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private PaymentRepository $repository;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = new PaymentRepository(new Payment());
        $this->user       = User::factory()->create();
    }

    public function test_find_by_id_returns_payment_when_found(): void
    {
        $payment = Payment::factory()->create(['user_id' => $this->user->id]);

        $result = $this->repository->findById($payment->id);

        $this->assertInstanceOf(Payment::class, $result);
        $this->assertEquals($payment->id, $result->id);
    }

    public function test_find_by_id_returns_null_when_not_found(): void
    {
        $result = $this->repository->findById(999);

        $this->assertNull($result);
    }

    public function test_get_all_related_to_user_returns_only_user_payments(): void
    {
        $otherUser = User::factory()->create();

        Payment::factory()->count(2)->create(['user_id' => $this->user->id]);
        Payment::factory()->create(['user_id' => $otherUser->id]);

        $results = $this->repository->getAllRelatedToUser($this->user->id);

        $this->assertCount(2, $results);
        $results->each(fn($p) => $this->assertEquals($this->user->id, $p->user_id));
    }

    public function test_exists_returns_true_when_payment_found(): void
    {
        $payment = Payment::factory()->create(['user_id' => $this->user->id]);

        $this->assertTrue($this->repository->exists($payment->id));
    }

    public function test_exists_returns_false_when_payment_not_found(): void
    {
        $this->assertFalse($this->repository->exists(999));
    }

    public function test_delete_soft_deletes_payment(): void
    {
        $payment = Payment::factory()->create(['user_id' => $this->user->id]);

        $this->repository->delete($payment->id);

        $this->assertSoftDeleted('payments', ['id' => $payment->id]);
    }

    public function test_restore_recovers_soft_deleted_payment(): void
    {
        $payment = Payment::factory()->create(['user_id' => $this->user->id]);
        $payment->delete();

        $result = $this->repository->restore($payment->id);

        $this->assertNull($result->deleted_at);
    }
}
