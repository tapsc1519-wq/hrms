<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GoodsReceipt extends Model
{
    protected $fillable = [
        'organization_id',
        'purchase_order_id',
        'received_by',
        'receipt_number',
        'received_date',
        'invoice_number',
        'invoice_date',
        'delivery_note_number',
        'notes',
    ];

    protected $casts = [
        'received_date' => 'date',
        'invoice_date' => 'date',
    ];

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function receivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(GoodsReceiptItem::class);
    }

    public function assets(): HasMany
    {
        return $this->hasMany(Asset::class);
    }
}
