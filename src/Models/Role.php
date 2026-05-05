<?php

namespace Zakirjarir\RbacAutomator\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Role extends Model
{
    protected $fillable = ['name', 'slug', 'description', 'is_deletable'];

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'role_permission');
    }

    public function modules(): BelongsToMany
    {
        return $this->belongsToMany(Module::class, 'role_module');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(config('auth.providers.users.model'), 'user_role');
    }
}
