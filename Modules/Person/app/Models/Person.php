<?php

declare(strict_types=1);

namespace Modules\Person\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Favorite\Traits\HasFavorite;
use Modules\Person\Database\Factories\PersonFactory;
use Modules\Person\Enums\CreditRole;
use Modules\Person\Enums\Gender;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Translatable\HasTranslations;
use Illuminate\Database\Eloquent\Builder;

class Person extends Model implements HasMedia
{
    use HasFactory;
    use HasTranslations;
    use SoftDeletes, HasFavorite, InteractsWithMedia;

    protected $table = 'persons';

    protected $fillable = [
        'first_name',
        'last_name',
        'slug',
        'biography',
        'date_of_birth',
        'date_of_death',
        'place_of_birth',
        'gender',
        'known_for_department',
        'popularity',
        'is_active',
    ];

    public array $translatable = [
        'first_name',
        'last_name',
        'biography',
        'place_of_birth',
    ];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'date_of_death' => 'date',
            'gender'        => Gender::class,
            'popularity'    => 'float',
            'is_active'     => 'boolean',
        ];
    }

    public function credits(): HasMany
    {
        return $this->hasMany(Credit::class);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('avatar')
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp']);
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->fit(Fit::Crop, 300, 300)
            ->nonQueued();
    }

    public function actingCredits(): HasMany
    {
        return $this->credits()->where('role', CreditRole::ACTOR->value);
    }

    public function directingCredits(): HasMany
    {
        return $this->credits()->where('role', CreditRole::DIRECTOR->value);
    }

    public function writingCredits(): HasMany
    {
        return $this->credits()->where('role', CreditRole::WRITER->value);
    }

    public function producingCredits(): HasMany
    {
        return $this->credits()->whereIn('role', [
            CreditRole::PRODUCER->value,
            CreditRole::EXECUTIVE->value,
        ]);
    }

    public function getFullNameAttribute(): string
    {
        $first = is_array($this->getTranslations('first_name'))
            ? ($this->first_name ?? '')
            : (string) $this->first_name;
        $last = is_array($this->getTranslations('last_name'))
            ? ($this->last_name ?? '')
            : (string) $this->last_name;

        return trim($first . ' ' . $last);
    }


    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeInactive(Builder $query): Builder
    {
        return $query->where('is_active', false);
    }

    public function scopeByDepartment(Builder $query, string $department): Builder
    {
        return $query->where('known_for_department', $department);
    }

    public function scopePopular(Builder $query): Builder
    {
        return $query->orderByDesc('popularity');
    }

    public function scopeByGender(Builder $query, string $gender): Builder
    {
        return $query->where('gender', $gender);
    }

    protected static function newFactory(): PersonFactory
    {
        return PersonFactory::new();
    }
}
