<?php

namespace App\Services;

use App\Models\Software;
use App\Models\SoftwareRecognitionRule;

class SoftwareRecognitionService
{
    private array $rulesByOrganization = [];
    private array $softwareByOrganization = [];

    public function recognize(int $organizationId, string $rawName, ?string $publisher): array
    {
        $rawNameLower = strtolower($rawName);
        $publisherLower = strtolower((string) $publisher);

        $rules = $this->rulesByOrganization[$organizationId] ??= SoftwareRecognitionRule::where('organization_id', $organizationId)
            ->get()->sortByDesc(fn (SoftwareRecognitionRule $rule) => strlen($rule->raw_name_pattern))->values();
        foreach ($rules as $rule) {
            $nameMatches = str_contains($rawNameLower, strtolower($rule->raw_name_pattern));
            $publisherMatches = ! $rule->raw_publisher_pattern || str_contains($publisherLower, strtolower($rule->raw_publisher_pattern));
            if ($nameMatches && $publisherMatches) {
                return ['software_id' => $rule->software_id, 'confidence' => $rule->confidence_score];
            }
        }

        $catalog = $this->softwareByOrganization[$organizationId] ??= Software::where('organization_id', $organizationId)
            ->get()->sortByDesc(fn (Software $software) => strlen($software->name))->values();
        $software = $catalog->first(function (Software $software) use ($rawNameLower, $publisherLower) {
            $nameMatches = str_contains($rawNameLower, strtolower($software->name));
            $publisherMatches = ! $software->vendor || ! $publisherLower || str_contains($publisherLower, strtolower($software->vendor));
            return $nameMatches && $publisherMatches;
        });

        return ['software_id' => $software?->id, 'confidence' => $software ? 85 : null];
    }

    public function forgetOrganization(int $organizationId): void
    {
        unset($this->rulesByOrganization[$organizationId], $this->softwareByOrganization[$organizationId]);
    }
}
