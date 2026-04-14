<?php

namespace Modules\Authorization\Models;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Authorization\Database\Factories\PermissionFactory;
use Spatie\Permission\Models\Permission as ModelsPermission;


class Permission extends ModelsPermission
{
    use HasFactory;

    public function scopeByModule($query, string $modulePrefix)
    {
        return $query->where('name', 'like', $modulePrefix . '.%');
    }

    protected static function newFactory(): PermissionFactory
    {
        return PermissionFactory::new();
    }
}
