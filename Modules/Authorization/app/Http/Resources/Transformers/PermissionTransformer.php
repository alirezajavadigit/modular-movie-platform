<?php

namespace Modules\Authorization\Http\Resources\Transformers;

use League\Fractal\TransformerAbstract;
use Modules\Authorization\Models\Permission;

class PermissionTransformer extends TransformerAbstract
{
    public function transform(Permission $permission): array
    {
        return [
            'id' => $permission->id,
            'name' => $permission->name,
            'guard_name' => $permission->guard_name,
            'created_at' => $permission->created_at->toISOString(),
            'updated_at' => $permission->updated_at->toISOString(),
        ];
    }
}
