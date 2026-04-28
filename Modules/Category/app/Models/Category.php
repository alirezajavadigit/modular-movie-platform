<?php

declare(strict_types=1);

namespace Modules\Category\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Article\Models\Article;
use Modules\Category\Database\Factories\CategoryFactory;
use Spatie\Translatable\HasTranslations;

class Category extends Model
{
    use HasFactory;
    use HasTranslations;
    use SoftDeletes;

    protected $fillable = [
        'parent_id',
        'name',
        'slug',
        'description',
        'is_active',
        'order',
    ];

    public array $translatable = [
        'name',
        'slug',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'order'     => 'integer',
            'parent_id' => 'integer',
        ];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function articles(): MorphToMany
    {
        return $this->morphedByMany(
            Article::class,
            'categorizable',
            'categorizables',
            'category_id',
            'categorizable_id',
        )->withTimestamps();
    }

    protected static function newFactory(): CategoryFactory
    {
        return CategoryFactory::new();
    }
}
