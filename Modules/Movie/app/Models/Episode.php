<?php

declare(strict_types=1);

namespace Modules\Movie\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Favorite\Traits\HasFavorite;
use Modules\Movie\Database\Factories\EpisodeFactory;
use Modules\Person\Concerns\HasCredits;
use Spatie\Translatable\HasTranslations;

class Episode extends Model
{
    use HasFactory, SoftDeletes, HasCredits, HasFavorite, HasTranslations;

    public array $translatable = ['title', 'description'];

    protected $fillable = [
        'movie_id',
        'season_number',
        'episode_number',
        'title',
        'description',
        'poster',
        'trailer_url',
        'download_links',
    ];

    protected function casts(): array
    {
        return [
            'download_links'  => 'array',
            'season_number'   => 'integer',
            'episode_number'  => 'integer',
        ];
    }

    public function serial(): BelongsTo
    {
        return $this->belongsTo(Movie::class, 'movie_id');
    }

    protected static function newFactory(): EpisodeFactory
    {
        return EpisodeFactory::new();
    }
}
