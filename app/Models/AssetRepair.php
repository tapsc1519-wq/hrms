<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AssetRepair extends Model
{
    public const OPEN_STATUSES = [
        'request_raised',
        'under_review',
        'approved',
        'assigned_to_it',
        'assigned_to_vendor',
        'sent_for_repair',
        'diagnosis_pending',
        'estimate_received',
        'estimate_approved',
        'repair_in_progress',
        'repaired',
        'qc_pending',
        'qc_failed',
        'ready_to_return',
    ];

    protected $fillable = [
        'organization_id',
        'asset_id',
        'asset_assignment_id',
        'asset_issue_report_id',
        'amc_contract_id',
        'requested_by',
        'approved_by',
        'assigned_to',
        'qc_by',
        'vendor_id',
        'repair_number',
        'source',
        'repair_type',
        'priority',
        'status',
        'market_vendor_name',
        'market_vendor_contact',
        'market_vendor_phone',
        'market_vendor_address',
        'issue_summary',
        'diagnosis',
        'work_performed',
        'requested_date',
        'sent_date',
        'expected_return_date',
        'completed_date',
        'returned_date',
        'parts_cost',
        'service_cost',
        'tax_amount',
        'discount_amount',
        'total_cost',
        'invoice_number',
        'invoice_path',
        'qc_status',
        'qc_checks',
        'qc_notes',
        'qc_at',
        'admin_notes',
    ];

    protected $casts = [
        'requested_date' => 'date',
        'sent_date' => 'date',
        'expected_return_date' => 'date',
        'completed_date' => 'date',
        'returned_date' => 'date',
        'parts_cost' => 'decimal:2',
        'service_cost' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total_cost' => 'decimal:2',
        'qc_checks' => 'array',
        'qc_at' => 'datetime',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(AssetAssignment::class, 'asset_assignment_id');
    }

    public function issueReport(): BelongsTo
    {
        return $this->belongsTo(AssetIssueReport::class, 'asset_issue_report_id');
    }

    public function amcContract(): BelongsTo
    {
        return $this->belongsTo(AssetAmcContract::class, 'amc_contract_id');
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function qcBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'qc_by');
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'vendor_id');
    }

    public function parts(): HasMany
    {
        return $this->hasMany(AssetRepairPart::class);
    }

    public function getStatusLabelAttribute(): string
    {
        return ucwords(str_replace('_', ' ', $this->status));
    }

    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            'request_raised', 'under_review', 'diagnosis_pending', 'estimate_received', 'qc_pending' => 'warning',
            'approved', 'assigned_to_it', 'assigned_to_vendor', 'sent_for_repair', 'estimate_approved', 'repair_in_progress' => 'primary',
            'repaired', 'ready_to_return' => 'info',
            'returned', 'closed' => 'success',
            'rejected', 'not_repairable', 'qc_failed' => 'danger',
            default => 'secondary',
        };
    }

    public function getRepairTypeLabelAttribute(): string
    {
        return match ($this->repair_type) {
            'amc' => 'AMC Vendor',
            'market' => 'Market Repair',
            'warranty' => 'Warranty',
            'vendor' => 'Onboarded Vendor',
            default => ucwords(str_replace('_', ' ', $this->repair_type)),
        };
    }
}
