<?php

declare(strict_types=1);

namespace Modules\Article\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Article\Database\Factories\ArticleFactory;
use Modules\Auth\Models\User;
use Spatie\Translatable\HasTranslations;

class Article extends Model
{
    use HasFactory;
    use HasTranslations;
    use SoftDeletes;

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
            'is_featured' => 'boolean',
            'allow_comments' => 'boolean',
            'read_time' => 'integer',
            'published_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }


    protected static function newFactory(): ArticleFactory
    {
        return ArticleFactory::new();
    }
}
