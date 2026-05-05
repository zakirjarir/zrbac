<?php

namespace Zakirjarir\RbacAutomator\Traits;

use Zakirjarir\RbacAutomator\Models\Role;
use Zakirjarir\RbacAutomator\Models\Permission;
use Zakirjarir\RbacAutomator\Models\Module;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

trait HasRbac
{
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'user_role');
    }

    public function hasRole(string $role): bool
    {
        return $this->roles->contains('slug', $role);
    }

    public function hasPermission(string $permission): bool
    {
        return $this->roles->flatMap->permissions->contains('slug', $permission);
    }

    public function hasModule(string $module): bool
    {
        return $this->roles->flatMap->modules->contains('slug', $module);
    }

    public function assignRole(string $roleSlug): void
    {
        $role = Role::where('slug', $roleSlug)->first();
        if ($role) {
            $this->roles()->syncWithoutDetaching($role->id);
        }
    }
}
