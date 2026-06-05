<?php

declare(strict_types=1);

namespace Modules\Article\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Article\Database\Factories\ArticleFactory;
use Modules\Auth\Models\User;
use Modules\Category\Models\Category;
use Modules\Favorite\Traits\HasFavorite;
use Modules\Like\Traits\HasLike;
use Modules\Person\Concerns\HasCredits;
use Modules\Tag\Models\Tag;
use Spatie\Translatable\HasTranslations;

class Article extends Model
{
    use HasCredits;
    use HasFactory;
    use HasTranslations;
    use SoftDeletes, HasFavorite, HasLike;

    protected $fillable = [
        'user_id',
        'title',
        'slug',
        'summary',
        'body',
        'status',
        'read_time',
        'is_featured',
        'allow_comments',
        'published_at',
    ];

    public array $translatable = [
        'title',
        'slug',
        'summary',
        'body',
    ];

    protected function casts(): array
    {
        return [
            'is_featured'    => 'boolean',
            'allow_comments' => 'boolean',
            'read_time'      => 'integer',
            'published_at'   => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function categories(): MorphToMany
    {
        return $this->morphToMany(
            Category::class,
            'categorizable',
            'categorizables',
            'categorizable_id',
            'category_id',
        )->withTimestamps();
    }

    public function tags(): MorphToMany
    {
        return $this->morphToMany(
            Tag::class,
            'taggable',
            'taggables',
            'taggable_id',
            'tag_id',
        )->withTimestamps();
    }

    protected static function newFactory(): ArticleFactory
    {
        return ArticleFactory::new();
    }
}
