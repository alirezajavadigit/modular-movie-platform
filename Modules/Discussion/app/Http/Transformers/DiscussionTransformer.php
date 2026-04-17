<?php

namespace Modules\Discussion\Http\Transformers;

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
            'created_at'          => $discussion->created_at?->toDateTimeString(),
            'updated_at'          => $discussion->updated_at?->toDateTimeString(),
            'deleted_at'          => $discussion->deleted_at?->toDateTimeString(),
        ];
    }

    public function includeReplies(Discussion $discussion): Collection
    {
        return $this->collection(
            $discussion->approvedReplies,
            new self(),
            'replies'
        );
    }

    public function includeUser(Discussion $discussion): Item|NullResource
    {
        if (! $discussion->user) {
            return $this->null();
        }

        return $this->item(
            $discussion->user,
            new UserTransformer(),
            'user'
        );
    }

    public function includeParent(Discussion $discussion): Item|NullResource
    {
        if (! $discussion->parent) {
            return $this->null();
        }

        return $this->item(
            $discussion->parent,
            new self(),
            'parent'
        );
    }
}
