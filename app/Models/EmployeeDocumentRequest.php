<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeDocumentRequest extends Model
{
    protected $fillable = [
        'organization_id',
        'employee_profile_id',
        'requested_by',
        'reviewed_by',
        'fulfilled_document_id',
        'document_type',
        'title',
        'due_date',
        'notes',
        'review_notes',
        'status',
        'submitted_at',
        'reviewed_at',
    ];

    protected $casts = [
        'due_date' => 'date',
        'submitted_at' => 'datetime',
        'reviewed_at' => 'datetime',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(EmployeeProfile::class, 'employee_profile_id');
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function fulfilledDocument(): BelongsTo
    {
        return $this->belongsTo(EmployeeDocument::class, 'fulfilled_document_id');
    }

    public function getDocumentTypeLabelAttribute(): string
    {
        return ucwords(str_replace('_', ' ', $this->document_type));
    }

    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'warning',
            'submitted' => 'info',
            'approved' => 'success',
            'rejected' => 'danger',
            default => 'secondary',
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return ucfirst($this->status);
    }
}
