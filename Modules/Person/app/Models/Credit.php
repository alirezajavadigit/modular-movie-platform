<?php

declare(strict_types=1);

namespace Modules\Person\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Person\Database\Factories\CreditFactory;
use Modules\Person\Enums\CreditRole;

class Credit extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'person_id',
        'creditable_id',
        'creditable_type',
        'role',
        'character_name',
        'credited_as',
        'department',
        'order',
    ];

    protected function casts(): array
    {
        return [
            'role'  => CreditRole::class,
            'order' => 'integer',
        ];
    }

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }

    public function creditable(): MorphTo
    {
        return $this->morphTo();
    }

    public function scopeForRole($query, CreditRole|string $role)
    {
        $value = $role instanceof CreditRole ? $role->value : $role;
        return $query->where('role', $value);
    }

    public function scopeForDepartment($query, string $department)
    {
        return $query->where('department', $department);
    }

    public function scopeOrdered($query, string $direction = 'asc')
    {
        return $query->orderBy('order', $direction);
    }

    public function scopeCast($query)
    {
        return $query->whereIn('role', [
            CreditRole::ACTOR->value,
            CreditRole::GUEST->value,
            CreditRole::NARRATOR->value,
        ]);
    }

    public function scopeCrew($query)
    {
        return $query->whereNotIn('role', [
            CreditRole::ACTOR->value,
            CreditRole::GUEST->value,
            CreditRole::NARRATOR->value,
        ]);
    }

    protected static function newFactory(): CreditFactory
    {
        return CreditFactory::new();
    }
}
