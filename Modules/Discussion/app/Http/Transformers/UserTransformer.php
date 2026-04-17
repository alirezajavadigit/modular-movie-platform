<?php

namespace Modules\Discussion\Http\Transformers;

use League\Fractal\TransformerAbstract;

class UserTransformer extends TransformerAbstract
{
    public function transform($user): array
    {
        return [
            'id'    => $user->id,
            'name'  => $user->name ?? null,
            'email' => $user->email ?? null,
        ];
    }
}
