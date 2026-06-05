<?php

declare(strict_types=1);

namespace Modules\Discussion\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Auth\Models\User;
use Modules\Discussion\Database\Factories\DiscussionFactory;
use Modules\Discussion\Enums\DiscussionStatus;

class Discussion extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'discussions';

    protected $fillable = [
        'user_id',
        'parent_id',
        'discussionable_id',
        'discussionable_type',
        'body',
        'status',
        'ip_address',
    ];

    protected function casts(): array
    {
        return [
            'status' => DiscussionStatus::class,
        ];
    }

    protected static function newFactory(): DiscussionFactory
    {
        return DiscussionFactory::new();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function approvedReplies(): HasMany
    {
        return $this->replies()->where('status', DiscussionStatus::APPROVED->value);
    }

    public function discussionable(): MorphTo
    {
        return $this->morphTo();
    }

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', DiscussionStatus::APPROVED->value);
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', DiscussionStatus::PENDING->value);
    }

    public function scopeRejected(Builder $query): Builder
    {
        return $query->where('status', DiscussionStatus::REJECTED->value);
    }

    public function scopeParents(Builder $query): Builder
    {
        return $query->whereNull('parent_id');
    }

    public function scopeOnlyReplies(Builder $query): Builder
    {
        return $query->whereNotNull('parent_id');
    }

    public function scopeForDiscussionable(Builder $query, string $type, int $id): Builder
    {
        return $query
            ->where('discussionable_type', Relation::getMorphAlias($type))
            ->where('discussionable_id', $id);
    }

    public function isApproved(): bool
    {
        return $this->status === DiscussionStatus::APPROVED;
    }

    public function isPending(): bool
    {
        return $this->status === DiscussionStatus::PENDING;
    }

    public function isRejected(): bool
    {
        return $this->status === DiscussionStatus::REJECTED;
    }

    public function isParent(): bool
    {
        return is_null($this->parent_id);
    }

    public function isReply(): bool
    {
        return ! is_null($this->parent_id);
    }
}
