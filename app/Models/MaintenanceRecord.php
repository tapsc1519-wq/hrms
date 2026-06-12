<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MaintenanceRecord extends Model
{
    protected $fillable = [
        'asset_id', 'vendor_id', 'type', 'scheduled_date', 'completed_date',
        'next_maintenance_date', 'technician_name', 'technician_company',
        'description', 'diagnosis', 'work_performed', 'parts_replaced',
        'labor_cost', 'parts_cost', 'total_cost', 'status',
        'invoice_number', 'notes',
    ];

    protected $casts = [
        'scheduled_date' => 'date',
        'completed_date' => 'date',
        'next_maintenance_date' => 'date',
    ];

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'vendor_id');
    }

    public function getStatusBadgeAttribute(): string
    {
        return match($this->status) {
            'scheduled'   => 'info',
            'in_progress' => 'warning',
            'completed'   => 'success',
            'cancelled'   => 'danger',
            default       => 'secondary',
        };
    }
}
