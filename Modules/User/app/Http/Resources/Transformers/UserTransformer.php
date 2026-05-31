<?php

declare(strict_types=1);

namespace Modules\User\Http\Resources\Transformers;

use League\Fractal\TransformerAbstract;
use Modules\Auth\Models\User;

class UserTransformer extends TransformerAbstract
{
    public function transform(User $user): array
    {
        return [
            'id'                => $user->id,
            'name'              => $user->name,
            'email'             => $user->email,
            'phone'             => $user->phone,
            'roles'             => $user->getRoleNames()->all(),
            'email_verified_at' => $user->email_verified_at?->toIso8601String(),
            'phone_verified_at' => $user->phone_verified_at?->toIso8601String(),
            'created_at'        => $user->created_at?->toIso8601String(),
            'updated_at'        => $user->updated_at?->toIso8601String(),
            'deleted_at'        => $user->deleted_at?->toIso8601String(),
        ];
    }
}
