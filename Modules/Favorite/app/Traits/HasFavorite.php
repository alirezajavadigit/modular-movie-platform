<?php

namespace Modules\Favorite\Traits;

use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\Auth;
use Modules\Favorite\Models\Favorite;

trait HasFavorite
{
    public function favorites(): MorphMany
    {
        return $this->morphMany(Favorite::class, 'favoriteable');
    }

    public function isFavoritedBy(int $userId): bool
    {
        return $this->favorites()->where('user_id', $userId)->exists();
    }

    public function isFavorited(): bool
    {
        if (!Auth::check()) {
            return false;
        }

        return $this->isFavoritedBy(Auth::id());
    }

    public function favoritesCount(): int
    {
        return $this->favorites()->count();
    }

    public function toggleFavoriteBy(int $userId): array
    {
        $existing = $this->favorites()->where('user_id', $userId)->first();

        if ($existing) {
            $existing->delete();

            return [
                'favorited' => false,
                'count'     => $this->favoritesCount(),
            ];
        }

        $this->favorites()->create(['user_id' => $userId]);

        return [
            'favorited' => true,
            'count'     => $this->favoritesCount(),
        ];
    }
}
