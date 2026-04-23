<?php

declare(strict_types=1);

namespace Modules\Favorite\Models;

use Modules\Auth\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Modules\Favorite\Database\Factories\FavoriteFactory;

class Favorite extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'favoriteable_id',
        'favoriteable_type',
    ];

    protected static function newFactory(): FavoriteFactory
    {
        return FavoriteFactory::new();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function favoriteable(): MorphTo
    {
        return $this->morphTo();
    }

    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    public function scopeForFavoriteable(Builder $query, string $type, int|string $id): Builder
    {
        return $query->where('favoriteable_type', $type)
            ->where('favoriteable_id', $id);
    }
}
