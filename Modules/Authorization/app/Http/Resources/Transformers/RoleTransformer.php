<?php

namespace Modules\Authorization\Http\Resources\Transformers;

use League\Fractal\TransformerAbstract;
use Modules\Authorization\Models\Role;

class RoleTransformer extends TransformerAbstract
{
    protected array $availableIncludes = [
        'permissions',
    ];

    public function transform(Role $role): array
    {
        return [
            'id' => $role->id,
            'name' => $role->name,
            'guard_name' => $role->guard_name,
            'created_at' => $role->created_at->toISOString(),
            'updated_at' => $role->updated_at->toISOString(),
        ];
    }

    public function includePermissions(Role $role): \League\Fractal\Resource\Collection
    {
        return $this->collection($role->permissions, new PermissionTransformer());
    }
}
