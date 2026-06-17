<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrganizationSsoSetting extends Model
{
    protected $fillable = [
        'organization_id',
        'provider',
        'is_enabled',
        'client_id',
        'client_secret',
        'tenant',
        'allowed_domains',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'client_secret' => 'encrypted',
        'allowed_domains' => 'array',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function isReady(): bool
    {
        return $this->is_enabled
            && filled($this->client_id)
            && filled($this->client_secret);
    }

    public function allowsEmail(string $email): bool
    {
        $domains = collect($this->allowed_domains ?? [])
            ->map(fn($domain) => strtolower(trim((string) $domain)))
            ->filter()
            ->values();

        if ($domains->isEmpty()) {
            return true;
        }

        $emailDomain = strtolower(str($email)->afterLast('@')->toString());

        return $domains->contains($emailDomain);
    }
}
