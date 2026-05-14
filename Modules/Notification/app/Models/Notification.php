<?php

declare(strict_types=1);

namespace Modules\Notification\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Notification\Database\Factories\NotificationFactory;
use Modules\Notification\Enums\NotificationChannel;

class Notification extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'notifiable_id',
        'notifiable_type',
        'type',
        'channel',
        'data',
        'read_at',
        'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'data'    => 'array',
            'channel' => NotificationChannel::class,
            'read_at' => 'datetime',
            'sent_at' => 'datetime',
        ];
    }

    protected static function newFactory(): NotificationFactory
    {
        return NotificationFactory::new();
    }

    public function resolveRouteBinding($value, $field = null): ?static
    {
        return $this->newQuery()->withTrashed()->find($value);
    }

    public function notifiable(): MorphTo
    {
        return $this->morphTo();
    }

    public function isRead(): bool
    {
        return $this->read_at !== null;
    }

    public function isUnread(): bool
    {
        return $this->read_at === null;
    }
}
