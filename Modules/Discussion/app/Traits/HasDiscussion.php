<?php

namespace Modules\Discussion\Traits;

use Illuminate\Database\Eloquent\Relations\MorphMany;
use Modules\Discussion\Enums\DiscussionStatus;
use Modules\Discussion\Models\Discussion;

trait HasDiscussion
{
    public function discussions(): MorphMany
    {
        return $this->morphMany(Discussion::class, 'discussionable');
    }

    public function approvedDiscussions(): MorphMany
    {
        return $this->discussions()->where('status', DiscussionStatus::APPROVED->value);
    }

    public function pendingDiscussions(): MorphMany
    {
        return $this->discussions()->where('status', DiscussionStatus::PENDING->value);
    }

    public function rejectedDiscussions(): MorphMany
    {
        return $this->discussions()->where('status', DiscussionStatus::REJECTED->value);
    }

    public function parentDiscussions(): MorphMany
    {
        return $this->discussions()->whereNull('parent_id');
    }
}
