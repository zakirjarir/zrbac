<?php

namespace Zakirjarir\RbacAutomator\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Module extends Model
{
    protected $fillable = ['name', 'slug', 'icon', 'status'];

    public function permissions(): HasMany
    {
        return $this->hasMany(Permission::class);
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_module');
    }
}
