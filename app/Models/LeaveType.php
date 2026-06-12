<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeaveType extends Model
{
    protected $fillable = [
        'organization_id', 'name', 'code', 'annual_quota', 'is_paid', 'requires_approval', 'status',
    ];

    protected $casts = [
        'annual_quota' => 'decimal:2',
        'is_paid' => 'boolean',
        'requires_approval' => 'boolean',
    ];
}
