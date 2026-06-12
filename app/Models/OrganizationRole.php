<?php

namespace App\Models;

use App\Support\PermissionRegistry;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrganizationRole extends Model
{
    protected $fillable = [
        'organization_id', 'name', 'portal_role', 'permissions',
        'description', 'status',
    ];

    protected $casts = [
        'permissions' => 'array',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'custom_role_id');
    }

    public function hasPermission(string $permission): bool
    {
        return PermissionRegistry::grants($this->permissions ?? [], $permission);
    }

    public static function permissionGroups(): array
    {
        return PermissionRegistry::groups();
    }

    public static function validPermissions(): array
    {
        return PermissionRegistry::all();
    }
}
