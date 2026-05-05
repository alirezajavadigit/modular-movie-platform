<?php

namespace Modules\Like\Traits;

use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\Auth;
use Modules\Like\Models\Like;

trait HasLike
{
    public function likes(): MorphMany
    {
        return $this->morphMany(Like::class, 'likeable');
    }

    public function isLikedBy(int $userId): bool
    {
        return $this->likes()->where('user_id', $userId)->exists();
    }

    public function isLiked(): bool
    {
        if (!Auth::check()) {
            return false;
        }

        return $this->isLikedBy(Auth::id());
    }

    public function likesCount(): int
    {
        return $this->likes()->count();
    }

    public function toggleLikeBy(int $userId): array
    {
        $existing = $this->likes()->where('user_id', $userId)->first();

        if ($existing) {
            $existing->delete();

            return [
                'liked' => false,
                'count' => $this->likesCount(),
            ];
        }

        $this->likes()->create(['user_id' => $userId]);

        return [
            'liked' => true,
            'count' => $this->likesCount(),
        ];
    }
}
