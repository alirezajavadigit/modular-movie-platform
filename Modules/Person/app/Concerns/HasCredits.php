<?php

declare(strict_types=1);

namespace Modules\Person\Concerns;

use Illuminate\Database\Eloquent\Relations\MorphMany;
use Modules\Person\Enums\CreditRole;
use Modules\Person\Models\Credit;
use Modules\Person\Models\Person;

trait HasCredits
{
    public function credits(): MorphMany
    {
        return $this->morphMany(Credit::class, 'creditable');
    }

    public function cast(): MorphMany
    {
        return $this->credits()->whereIn('role', [
            CreditRole::ACTOR->value,
            CreditRole::GUEST->value,
            CreditRole::NARRATOR->value,
        ]);
    }

    public function crew(): MorphMany
    {
        return $this->credits()->whereNotIn('role', [
            CreditRole::ACTOR->value,
            CreditRole::GUEST->value,
            CreditRole::NARRATOR->value,
        ]);
    }

    public function directors(): MorphMany
    {
        return $this->credits()->where('role', CreditRole::DIRECTOR->value);
    }

    public function writers(): MorphMany
    {
        return $this->credits()->where('role', CreditRole::WRITER->value);
    }

    public function producers(): MorphMany
    {
        return $this->credits()->whereIn('role', [
            CreditRole::PRODUCER->value,
            CreditRole::EXECUTIVE->value,
        ]);
    }

    public function composers(): MorphMany
    {
        return $this->credits()->where('role', CreditRole::COMPOSER->value);
    }

    public function attachCredit(Person $person, CreditRole|string $role, array $extra = []): Credit
    {
        $roleValue = $role instanceof CreditRole ? $role->value : $role;

        return $this->credits()->create(array_merge([
            'person_id' => $person->id,
            'role'      => $roleValue,
        ], $extra));
    }

    public function detachCredit(int $creditId): bool
    {
        return (bool) $this->credits()->whereKey($creditId)->delete();
    }

    public function syncCredits(array $credits): void
    {
        $this->credits()->delete();
        foreach ($credits as $credit) {
            $this->credits()->create($credit);
        }
    }
}
