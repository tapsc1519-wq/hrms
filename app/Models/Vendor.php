<?php

namespace App\Models;

class Vendor extends Supplier
{
    protected static function booted(): void
    {
        static::creating(function (Vendor $vendor) {
            $vendor->partner_type = $vendor->partner_type ?: 'vendor';
        });
    }
}
