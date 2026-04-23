<?php

declare(strict_types=1);

namespace Modules\Movie\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Favorite\Traits\HasFavorite;
use Modules\Movie\Database\Factories\MovieFactory;
use Modules\Movie\Enums\BadgeType;
use Modules\Movie\Enums\MovieType;
use Modules\Person\Concerns\HasCredits;

class Movie extends Model
{
    use HasFactory, SoftDeletes, HasCredits, HasFavorite;

    protected $fillable = [
        'title',
        'description',
        'poster',
        'trailer_url',
        'download_links',
        'release_year',
        'country',
        'language',
        'imdb_score',
        'badge',
        'type',
    ];

    protected function casts(): array
    {
        return [
            'download_links' => 'array',
            'badge'          => BadgeType::class,
            'type'           => MovieType::class,
            'imdb_score'     => 'decimal:1',
            'release_year'   => 'integer',
        ];
    }

    public function episodes(): HasMany
    {
        return $this->hasMany(Episode::class);
    }

    public function isSerial(): bool
    {
        return $this->type === MovieType::Serial;
    }

    protected static function newFactory(): MovieFactory
    {
        return MovieFactory::new();
    }
}
