<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Software extends Model
{
    protected $table = 'software';

    protected $fillable = [
        'organization_id', 'name', 'vendor', 'version', 'edition',
        'category', 'software_type', 'license_required', 'criticality',
        'license_metric', 'trusted_publisher',
        'policy_status', 'policy_notes', 'policy_reviewed_by', 'policy_reviewed_at',
        'description', 'publisher_website', 'icon',
    ];

    protected $casts = [
        'license_required' => 'boolean',
        'trusted_publisher' => 'boolean',
        'policy_reviewed_at' => 'datetime',
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

    public function discoveries(): HasMany
    {
        return $this->hasMany(SoftwareDiscovery::class);
    }

    public function recognitionRules(): HasMany
    {
        return $this->hasMany(SoftwareRecognitionRule::class);
    }

    public function requests(): HasMany
    {
        return $this->hasMany(SoftwareRequest::class);
    }

    public function policyExceptions(): HasMany
    {
        return $this->hasMany(SoftwarePolicyException::class);
    }

    public function policyReviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'policy_reviewed_by');
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

    public function getSoftwareTypeLabelAttribute(): string
    {
        return match($this->software_type) {
            'saas' => 'SaaS',
            'open_source' => 'Open Source',
            'freeware' => 'Freeware',
            'os' => 'Operating System',
            default => 'Commercial',
        };
    }

    public function getCriticalityBadgeAttribute(): string
    {
        return match($this->criticality) {
            'critical' => 'danger',
            'high' => 'warning',
            'low' => 'secondary',
            default => 'primary',
        };
    }

    public function getPolicyStatusLabelAttribute(): string
    {
        return match ($this->policy_status) {
            'approved' => 'Approved',
            'restricted' => 'Restricted',
            'prohibited' => 'Prohibited',
            default => 'Unreviewed',
        };
    }

    public function getPolicyStatusBadgeAttribute(): string
    {
        return match ($this->policy_status) {
            'approved' => 'success',
            'restricted' => 'warning',
            'prohibited' => 'danger',
            default => 'secondary',
        };
    }

    public function getLicenseMetricLabelAttribute(): string
    {
        return match($this->license_metric) {
            'per_device' => 'Per Device',
            'concurrent' => 'Concurrent',
            'site' => 'Site',
            'enterprise' => 'Enterprise',
            'usage_based' => 'Usage Based',
            default => 'Per User',
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
