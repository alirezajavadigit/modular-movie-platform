<?php

declare(strict_types=1);

namespace Modules\Person\Http\Resources\Transformers;

use League\Fractal\Resource\Item;
use League\Fractal\Resource\NullResource;
use League\Fractal\TransformerAbstract;
use Modules\Person\Models\Credit;

class CreditTransformer extends TransformerAbstract
{
    protected array $availableIncludes = [
        'person',
        'creditable',
    ];

    public function transform(Credit $credit): array
    {
        return [
            'id'              => $credit->id,
            'person_id'       => $credit->person_id,
            'creditable_type' => $credit->creditable_type,
            'creditable_id'   => $credit->creditable_id,
            'role'            => $credit->role?->value,
            'character_name'  => $credit->character_name,
            'credited_as'     => $credit->credited_as,
            'department'      => $credit->department,
            'order'           => (int) $credit->order,
            'created_at'      => $credit->created_at?->toIso8601String(),
            'updated_at'      => $credit->updated_at?->toIso8601String(),
            'deleted_at'      => $credit->deleted_at?->toIso8601String(),
        ];
    }

    public function includePerson(Credit $credit): Item|NullResource
    {
        if (!$credit->person) {
            return $this->null();
        }
        return $this->item($credit->person, new PersonTransformer());
    }

    public function includeCreditable(Credit $credit): Item|NullResource
    {
        $creditable = $credit->creditable;
        if (!$creditable) {
            return $this->null();
        }
        return $this->item($creditable, function ($model) use ($credit) {
            return [
                'id'   => $model->id,
                'type' => $credit->creditable_type,
            ];
        });
    }
}
