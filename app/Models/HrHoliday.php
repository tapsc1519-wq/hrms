<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HrHoliday extends Model
{
    protected $fillable = [
        'organization_id', 'name', 'holiday_date', 'type', 'description',
    ];

    protected $casts = [
        'holiday_date' => 'date',
    ];
}
