<?php


namespace Modules\Authorization\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Authorization\Database\Factories\RoleFactory;
use Spatie\Permission\Models\Role as SpatieRole;

class Role extends SpatieRole
{
    use HasFactory;

    protected $fillable = [
        'name',
        'guard_name',
    ];

    protected static function booted(): void
    {
        static::creating(function (Role $role) {
            if (empty($role->guard_name)) {
                $role->guard_name = config('auth.defaults.guard');
            }
        });
    }

    protected static function newFactory(): RoleFactory
    {
        return RoleFactory::new();
    }
}
