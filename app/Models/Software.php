<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Software extends Model
{
    protected $table = 'software';

    protected $fillable = [
        'organization_id', 'name', 'vendor', 'version',
        'category', 'description', 'publisher_website', 'icon',
    ];

    // ── Relationships ───────────────────────────────────────────────────────

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function licenses(): HasMany
    {
        return $this->hasMany(SoftwareLicense::class);
    }

    public function activeLicenses(): HasMany
    {
        return $this->hasMany(SoftwareLicense::class)->where('status', 'active');
    }

    // ── Accessors ───────────────────────────────────────────────────────────

    public function getCategoryLabelAttribute(): string
    {
        return match($this->category) {
            'productivity'     => 'Productivity',
            'security'         => 'Security',
            'design'           => 'Design',
            'development'      => 'Development',
            'communication'    => 'Communication',
            'database'         => 'Database',
            'erp'              => 'ERP / Business',
            'operating_system' => 'Operating System',
            default            => 'Other',
        };
    }

    public function getCategoryIconAttribute(): string
    {
        return match($this->category) {
            'productivity'     => 'bi-file-earmark-text-fill',
            'security'         => 'bi-shield-lock-fill',
            'design'           => 'bi-palette-fill',
            'development'      => 'bi-code-slash',
            'communication'    => 'bi-chat-dots-fill',
            'database'         => 'bi-database-fill',
            'erp'              => 'bi-building-fill',
            'operating_system' => 'bi-pc-display',
            default            => 'bi-box-seam-fill',
        };
    }

    public function getCategoryColorAttribute(): string
    {
        return match($this->category) {
            'productivity'     => 'primary',
            'security'         => 'danger',
            'design'           => 'purple',
            'development'      => 'dark',
            'communication'    => 'info',
            'database'         => 'warning',
            'erp'              => 'success',
            'operating_system' => 'secondary',
            default            => 'secondary',
        };
    }

    public function getTotalSeatsAttribute(): int
    {
        return $this->licenses()->where('status', 'active')->sum('seats');
    }

    public function getUsedSeatsAttribute(): int
    {
        return SoftwareAssignment::whereHas('license', fn($q) =>
            $q->where('software_id', $this->id)
        )->where('status', 'active')->count();
    }

    public function getAvailableSeatsAttribute(): int
    {
        return max(0, $this->total_seats - $this->used_seats);
    }
}
