<?php

namespace Modules\Notification\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Auth\Models\User;
use Modules\Notification\DTOs\CreateNotificationDTO;
use Modules\Notification\Enums\NotificationChannel;
use Modules\Notification\Models\Notification;
use Modules\Notification\Repositories\NotificationRepository;
use Tests\TestCase;

class NotificationRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private NotificationRepository $repository;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = new NotificationRepository(new Notification());
        $this->user       = User::factory()->create();
    }

    public function test_find_by_id_returns_notification_when_found(): void
    {
        $notification = Notification::factory()->create([
            'notifiable_type' => User::class,
            'notifiable_id'   => $this->user->id,
        ]);

        $result = $this->repository->findById($notification->id);

        $this->assertInstanceOf(Notification::class, $result);
        $this->assertEquals($notification->id, $result->id);
    }

    public function test_find_by_id_returns_null_when_not_found(): void
    {
        $result = $this->repository->findById(999);

        $this->assertNull($result);
    }

    public function test_mark_as_read_sets_read_at_timestamp(): void
    {
        $notification = Notification::factory()->unread()->create([
            'notifiable_type' => User::class,
            'notifiable_id'   => $this->user->id,
        ]);

        $result = $this->repository->markAsRead($notification->id);

        $this->assertNotNull($result->read_at);
    }

    public function test_mark_all_as_read_updates_all_unread(): void
    {
        Notification::factory()->count(3)->unread()->create([
            'notifiable_type' => User::class,
            'notifiable_id'   => $this->user->id,
        ]);

        $this->repository->markAllAsRead(User::class, $this->user->id);

        $remaining = Notification::where('notifiable_type', User::class)
            ->where('notifiable_id', $this->user->id)
            ->whereNull('read_at')
            ->count();

        $this->assertEquals(0, $remaining);
    }

    public function test_create_stores_notification_with_correct_data(): void
    {
        $dto = new CreateNotificationDTO(
            notifiableType: User::class,
            notifiableId:   $this->user->id,
            type:           'user.welcome',
            channel:        NotificationChannel::DATABASE,
            data:           ['message' => 'Hello'],
        );

        $result = $this->repository->create($dto);

        $this->assertInstanceOf(Notification::class, $result);
        $this->assertEquals('user.welcome', $result->type);
        $this->assertEquals(NotificationChannel::DATABASE, $result->channel);
    }
}
