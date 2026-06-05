<?php

declare(strict_types=1);

namespace Modules\Discussion\Http\Resources\Transformers;

use League\Fractal\Resource\Collection;
use League\Fractal\Resource\Item;
use League\Fractal\Resource\NullResource;
use League\Fractal\TransformerAbstract;
use Modules\Discussion\Models\Discussion;

class DiscussionTransformer extends TransformerAbstract
{
    protected array $availableIncludes = [
        'replies',
        'user',
        'parent',
    ];

    public function transform(Discussion $discussion): array
    {
        return [
            'id'                  => $discussion->id,
            'body'                => $discussion->body,
            'status'              => $discussion->status->value,
            'status_label'        => $discussion->status->label(),
            'is_reply'            => $discussion->isReply(),
            'is_approved'         => $discussion->isApproved(),
            'ip_address'          => $discussion->ip_address,
            'discussionable_type' => $discussion->discussionable_type,
            'discussionable_id'   => $discussion->discussionable_id,
            'parent_id'           => $discussion->parent_id,
            'user_id'             => $discussion->user_id,
            'created_at'          => $discussion->created_at?->toIso8601String(),
            'updated_at'          => $discussion->updated_at?->toIso8601String(),
            'deleted_at'          => $discussion->deleted_at?->toIso8601String(),
        ];
    }

    public function includeReplies(Discussion $discussion): Collection
    {
        return $this->collection($discussion->approvedReplies, new self(), 'replies');
    }

    public function includeUser(Discussion $discussion): Item|NullResource
    {
        $user = $discussion->user;

        if (! $user) {
            return $this->null();
        }

        return $this->item($user, fn ($user) => [
            'id'    => $user->id,
            'name'  => $user->name,
            'email' => $user->email,
        ]);
    }

    public function includeParent(Discussion $discussion): Item|NullResource
    {
        if (! $discussion->parent) {
            return $this->null();
        }

        return $this->item($discussion->parent, new self(), 'parent');
    }
}
