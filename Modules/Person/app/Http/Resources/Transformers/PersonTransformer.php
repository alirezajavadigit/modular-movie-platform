<?php

declare(strict_types=1);

namespace Modules\Person\Http\Resources\Transformers;

use League\Fractal\Resource\Collection as FractalCollection;
use League\Fractal\Resource\NullResource;
use League\Fractal\TransformerAbstract;
use Modules\Person\Models\Person;

class PersonTransformer extends TransformerAbstract
{
    protected array $availableIncludes = [
        'credits',
        'acting_credits',
        'directing_credits',
    ];

    public function transform(Person $person): array
    {
        return [
            'id'                   => $person->id,
            'first_name'           => $person->getTranslations('first_name'),
            'last_name'            => $person->getTranslations('last_name'),
            'full_name'            => $person->full_name,
            'slug'                 => $person->slug,
            'biography'            => $person->getTranslations('biography'),
            'avatar'               => $person->getFirstMediaUrl('avatar') ?: null,
            'avatar_thumb'         => $person->getFirstMediaUrl('avatar', 'thumb') ?: null,
            'date_of_birth'        => $person->date_of_birth?->toDateString(),
            'date_of_death'        => $person->date_of_death?->toDateString(),
            'place_of_birth'       => $person->getTranslations('place_of_birth'),
            'gender'               => $person->gender?->value,
            'known_for_department' => $person->known_for_department,
            'popularity'           => (float) $person->popularity,
            'is_active'            => (bool) $person->is_active,
            'created_at'           => $person->created_at?->toIso8601String(),
            'updated_at'           => $person->updated_at?->toIso8601String(),
            'deleted_at'           => $person->deleted_at?->toIso8601String(),
        ];
    }

    public function includeCredits(Person $person): FractalCollection|NullResource
    {
        if (!$person->relationLoaded('credits') && !$person->credits()->exists()) {
            return $this->null();
        }
        return $this->collection($person->credits, new CreditTransformer());
    }

    public function includeActingCredits(Person $person): FractalCollection
    {
        return $this->collection($person->actingCredits, new CreditTransformer());
    }

    public function includeDirectingCredits(Person $person): FractalCollection
    {
        return $this->collection($person->directingCredits, new CreditTransformer());
    }
}
