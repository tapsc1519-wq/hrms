<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Invoice extends Model
{
    protected $fillable = [
        'organization_id', 'vendor_id', 'purchase_order_id',
        'invoice_number', 'invoice_date', 'due_date',
        'subtotal', 'tax_amount', 'discount_amount', 'total_amount', 'paid_amount',
        'status', 'payment_date', 'payment_method', 'payment_reference',
        'attachment', 'notes',
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'due_date' => 'date',
        'payment_date' => 'date',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'vendor_id');
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function getStatusBadgeAttribute(): string
    {
        return match($this->status) {
            'pending'   => 'warning',
            'partial'   => 'info',
            'paid'      => 'success',
            'overdue'   => 'danger',
            'cancelled' => 'secondary',
            default     => 'secondary',
        };
    }

    public function getBalanceAttribute(): float
    {
        return (float) $this->total_amount - (float) $this->paid_amount;
    }
}
