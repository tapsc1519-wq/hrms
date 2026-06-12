<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Facility extends Model
{
    protected $fillable = [
        'organization_id', 'name', 'code', 'address', 'city', 'state',
        'country', 'phone', 'email', 'description', 'status',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function locations(): HasMany
    {
        return $this->hasMany(Location::class);
    }

    public function activeLocations(): HasMany
    {
        return $this->hasMany(Location::class)->where('status', 'active')->orderBy('name');
    }

    /** Full label shown in dropdowns: "HQ Manila — NCR" */
    public function getDisplayNameAttribute(): string
    {
        $parts = array_filter([$this->city, $this->state]);
        return $this->name . ($parts ? ' — ' . implode(', ', $parts) : '');
    }
}
