<?php

namespace Modules\Notification\Traits;

use Illuminate\Database\Eloquent\Relations\MorphMany;
use Modules\Notification\Models\Notification;

trait HasNotifications
{
    public function notifications(): MorphMany
    {
        return $this->morphMany(Notification::class, 'notifiable');
    }

    public function unreadNotifications(): MorphMany
    {
        return $this->morphMany(Notification::class, 'notifiable')->whereNull('read_at');
    }

    public function hasUnreadNotifications(): bool
    {
        return $this->unreadNotifications()->exists();
    }

    public function unreadNotificationsCount(): int
    {
        return $this->unreadNotifications()->count();
    }
}
