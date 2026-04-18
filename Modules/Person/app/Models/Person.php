<?php

declare(strict_types=1);

namespace Modules\Person\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Person\Database\Factories\PersonFactory;
use Modules\Person\Enums\CreditRole;
use Modules\Person\Enums\Gender;
use Spatie\Translatable\HasTranslations;

class Person extends Model
{
    use HasFactory;
    use HasTranslations;
    use SoftDeletes;

    protected $table = 'persons';

    protected $fillable = [
        'first_name',
        'last_name',
        'slug',
        'biography',
        'image_path',
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

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByDepartment($query, string $department)
    {
        return $query->where('known_for_department', $department);
    }

    public function scopePopular($query)
    {
        return $query->orderByDesc('popularity');
    }

    protected static function newFactory(): PersonFactory
    {
        return PersonFactory::new();
    }
}
