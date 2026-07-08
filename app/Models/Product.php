<?php

namespace App\Models;

use App\Support\ModuleRegistry;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'short_name',
        'domain',
        'app_path',
        'icon',
        'color',
        'description',
        'status',
        'sort_order',
    ];

    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            'active' => 'success',
            'coming_soon' => 'warning',
            'inactive' => 'secondary',
            default => 'secondary',
        };
    }

    public function registeredModules()
    {
        return ModuleRegistry::forProduct($this->slug);
    }
}
