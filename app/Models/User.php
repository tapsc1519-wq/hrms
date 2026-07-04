<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;

    protected $fillable = [
        'organization_id', 'department_id', 'name', 'email', 'password',
        'role', 'custom_role_id', 'phone', 'employee_id', 'avatar', 'job_title', 'status', 'last_login_at',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'last_login_at' => 'datetime',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'user_id', 'user_id');
    }

    public function customRole(): BelongsTo
    {
        return $this->belongsTo(OrganizationRole::class, 'custom_role_id');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(AssetAssignment::class);
    }

    public function requests(): HasMany
    {
        return $this->hasMany(AssetRequest::class, 'requester_id');
    }

    public function softwareRequests(): HasMany
    {
        return $this->hasMany(SoftwareRequest::class, 'requester_id');
    }

    public function requestedAssetRepairs(): HasMany
    {
        return $this->hasMany(AssetRepair::class, 'requested_by');
    }

    public function employeeProfile(): HasOne
    {
        return $this->hasOne(EmployeeProfile::class);
    }

    public function isSuperAdmin(): bool { return $this->role === 'super_admin'; }
    public function isAdmin(): bool { return $this->role === 'admin'; }
    public function isSupplier(): bool { return $this->role === 'supplier'; }
    public function isStaff(): bool { return $this->role === 'staff'; }

    public function hasPermission(string $permission): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        if (!$this->customRole) {
            return $this->isAdmin();
        }

        return $this->customRole->status === 'active'
            && $this->customRole->portal_role === $this->role
            && $this->customRole->hasPermission($permission);
    }
}
