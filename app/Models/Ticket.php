<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Ticket extends Model
{
    protected $fillable = [
        'organization_id', 'requester_id', 'assigned_to', 'asset_id',
        'ticket_number', 'subject', 'description',
        'category', 'priority', 'status',
        'resolved_at', 'closed_at',
    ];

    protected $casts = [
        'resolved_at' => 'datetime',
        'closed_at'   => 'datetime',
    ];

    // ── Relationships ───────────────────────────────────────────────────────

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function replies(): HasMany
    {
        return $this->hasMany(TicketReply::class)->orderBy('created_at');
    }

    public function publicReplies(): HasMany
    {
        return $this->hasMany(TicketReply::class)->where('is_internal', false)->orderBy('created_at');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(TicketAttachment::class)->whereNull('reply_id')->orderBy('created_at');
    }

    public function allAttachments(): HasMany
    {
        return $this->hasMany(TicketAttachment::class)->orderBy('created_at');
    }

    // ── Accessors ───────────────────────────────────────────────────────────

    public function getCategoryLabelAttribute(): string
    {
        return match($this->category) {
            'fixed_assets'  => 'Fixed Assets',
            'itam_support'  => 'ITAM Tool Support',
            'other_support' => 'Other Support',
            default         => ucfirst($this->category),
        };
    }

    public function getCategoryIconAttribute(): string
    {
        return match($this->category) {
            'fixed_assets'  => 'bi-box-seam',
            'itam_support'  => 'bi-pc-display',
            'other_support' => 'bi-headset',
            default         => 'bi-question-circle',
        };
    }

    public function getCategoryColorAttribute(): string
    {
        return match($this->category) {
            'fixed_assets'  => 'primary',
            'itam_support'  => 'purple',
            'other_support' => 'secondary',
            default         => 'secondary',
        };
    }

    public function getStatusBadgeAttribute(): string
    {
        return match($this->status) {
            'open'        => 'primary',
            'in_progress' => 'warning',
            'resolved'    => 'success',
            'closed'      => 'secondary',
            default       => 'secondary',
        };
    }

    public function getPriorityBadgeAttribute(): string
    {
        return match($this->priority) {
            'urgent' => 'danger',
            'high'   => 'warning',
            'normal' => 'info',
            'low'    => 'secondary',
            default  => 'secondary',
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'in_progress' => 'In Progress',
            default       => ucfirst($this->status),
        };
    }

    public function getIsOpenAttribute(): bool
    {
        return in_array($this->status, ['open', 'in_progress']);
    }
}
