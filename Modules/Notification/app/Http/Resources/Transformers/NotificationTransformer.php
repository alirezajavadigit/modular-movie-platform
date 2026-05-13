<?php

declare(strict_types=1);

namespace Modules\Notification\Http\Resources\Transformers;

use League\Fractal\Resource\Item;
use League\Fractal\Resource\NullResource;
use League\Fractal\TransformerAbstract;
use Modules\Notification\Models\Notification;
use Modules\Notification\Support\TransformerRegistry;

class NotificationTransformer extends TransformerAbstract
{
    protected array $availableIncludes = [
        'notifiable',
    ];

    public function transform(Notification $notification): array
    {
        return [
            'id'               => $notification->id,
            'notifiable_id'    => $notification->notifiable_id,
            'notifiable_type'  => $notification->notifiable_type,
            'type'             => $notification->type,
            'channel'          => $notification->channel->value,
            'channel_label'    => $notification->channel->label(),
            'data'             => $notification->data,
            'is_read'          => $notification->isRead(),
            'read_at'          => $notification->read_at?->toIso8601String(),
            'sent_at'          => $notification->sent_at?->toIso8601String(),
            'created_at'       => $notification->created_at?->toIso8601String(),
            'updated_at'       => $notification->updated_at?->toIso8601String(),
            'deleted_at'       => $notification->deleted_at?->toIso8601String(),
        ];
    }

    public function includeNotifiable(Notification $notification): Item|NullResource
    {
        $notifiable = $notification->notifiable;

        if (!$notifiable) {
            return $this->null();
        }

        if (!TransformerRegistry::has($notifiable::class)) {
            return $this->null();
        }

        return $this->item($notifiable, TransformerRegistry::resolve($notifiable));
    }
}
