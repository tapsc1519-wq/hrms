<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Location extends Model
{
    protected $fillable = [
        'organization_id', 'facility_id', 'name', 'building', 'floor', 'room', 'address', 'description', 'status',
        'geofence_mode', 'latitude', 'longitude', 'geofence_radius_metres', 'maximum_gps_accuracy_metres',
        'require_location_on_sign_in', 'require_location_on_sign_out',
    ];

    protected $casts = [
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'require_location_on_sign_in' => 'boolean',
        'require_location_on_sign_out' => 'boolean',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function facility(): BelongsTo
    {
        return $this->belongsTo(Facility::class);
    }

    public function assets(): HasMany
    {
        return $this->hasMany(Asset::class);
    }
}
