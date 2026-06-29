<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DeviceAgent;
use App\Models\Software;
use App\Models\SoftwareComplianceAction;
use App\Models\SoftwareDiscovery;
use App\Models\SoftwareLicense;
use App\Models\SoftwareRenewalDecision;

class SamDashboardController extends Controller
{
    public function index()
    {
        $organizationId = $this->orgId();
        $now = now();

        $discoveryBase = SoftwareDiscovery::where('organization_id', $organizationId)->where('is_installed', true);
        $deviceBase = DeviceAgent::where('organization_id', $organizationId);

        $stats = [
            'devices' => (clone $deviceBase)->count(),
            'healthy_devices' => (clone $deviceBase)->where('last_seen_at', '>=', $now->copy()->subHours(24))->count(),
            'stale_devices' => (clone $deviceBase)
                ->where('last_seen_at', '>=', $now->copy()->subDays(7))
                ->where('last_seen_at', '<', $now->copy()->subHours(24))
                ->count(),
            'offline_devices' => (clone $deviceBase)
                ->where(fn ($query) => $query->whereNull('last_seen_at')->orWhere('last_seen_at', '<', $now->copy()->subDays(7)))
                ->count(),
            'installed_records' => (clone $discoveryBase)->count(),
            'unknown_records' => (clone $discoveryBase)->where('status', 'unknown')->count(),
            'mapped_records' => (clone $discoveryBase)->where('status', 'mapped')->count(),
            'catalog_items' => Software::where('organization_id', $organizationId)->count(),
            'active_licenses' => SoftwareLicense::where('organization_id', $organizationId)->where('status', 'active')->count(),
            'expiring_licenses' => SoftwareLicense::where('organization_id', $organizationId)
                ->where('status', 'active')
                ->whereNotNull('expiry_date')
                ->whereBetween('expiry_date', [$now->toDateString(), $now->copy()->addDays(30)->toDateString()])
                ->count(),
            'expired_licenses' => SoftwareLicense::where('organization_id', $organizationId)
                ->where('status', 'active')
                ->whereNotNull('expiry_date')
                ->where('expiry_date', '<', $now->toDateString())
                ->count(),
            'planned_renewals' => SoftwareRenewalDecision::where('organization_id', $organizationId)
                ->where('status', 'planned')
                ->count(),
            'open_actions' => SoftwareComplianceAction::where('organization_id', $organizationId)->where('status', 'open')->count(),
        ];

        $normalizationGroups = SoftwareDiscovery::where('organization_id', $organizationId)
            ->where('is_installed', true)
            ->where('status', 'unknown')
            ->select(['raw_name', 'raw_publisher'])
            ->selectRaw('COUNT(*) as installation_count')
            ->selectRaw('COUNT(DISTINCT device_agent_id) as device_count')
            ->selectRaw('COUNT(DISTINCT user_id) as user_count')
            ->selectRaw('MAX(last_seen_at) as latest_seen_at')
            ->groupBy('raw_name', 'raw_publisher')
            ->orderByDesc('installation_count')
            ->orderBy('raw_name')
            ->limit(8)
            ->get();

        $riskRows = Software::where('organization_id', $organizationId)
            ->with([
                'activeLicenses.activeAssignments',
                'discoveries' => fn ($query) => $query->where('status', 'mapped')->where('is_installed', true)->with('activePolicyException'),
            ])
            ->orderBy('name')
            ->get()
            ->map(fn (Software $software) => $this->riskRow($software))
            ->filter(fn (array $row) => $row['installed_count'] > 0 && $row['risk_score'] > 0)
            ->sortByDesc('risk_score')
            ->take(8)
            ->values();

        $renewals = SoftwareLicense::where('organization_id', $organizationId)
            ->where('status', 'active')
            ->whereNotNull('expiry_date')
            ->where('expiry_date', '<=', $now->copy()->addDays(60)->toDateString())
            ->with(['software', 'activeRenewalDecision.owner'])
            ->orderBy('expiry_date')
            ->limit(8)
            ->get();

        $upcomingRenewalIds = SoftwareLicense::where('organization_id', $organizationId)
            ->where('status', 'active')
            ->whereNotNull('expiry_date')
            ->where('expiry_date', '<=', $now->copy()->addDays(60)->toDateString())
            ->pluck('id');

        $plannedUpcomingRenewals = SoftwareRenewalDecision::where('organization_id', $organizationId)
            ->where('status', 'planned')
            ->whereIn('software_license_id', $upcomingRenewalIds)
            ->count();

        $stats['unplanned_renewals'] = max(0, $upcomingRenewalIds->count() - $plannedUpcomingRenewals);
        $stats['planned_renewal_spend'] = SoftwareRenewalDecision::where('organization_id', $organizationId)
            ->where('status', 'planned')
            ->sum('projected_cost');

        $openActions = SoftwareComplianceAction::where('organization_id', $organizationId)
            ->where('status', 'open')
            ->with(['software', 'owner'])
            ->orderByRaw('CASE WHEN due_date IS NULL THEN 1 ELSE 0 END')
            ->orderBy('due_date')
            ->latest('id')
            ->limit(8)
            ->get();

        $coverage = [
            'normalized_percent' => $stats['installed_records'] > 0
                ? (int) round(($stats['mapped_records'] / $stats['installed_records']) * 100)
                : 0,
            'healthy_percent' => $stats['devices'] > 0
                ? (int) round(($stats['healthy_devices'] / $stats['devices']) * 100)
                : 0,
        ];

        return view('admin.sam-dashboard.index', compact(
            'stats',
            'coverage',
            'normalizationGroups',
            'riskRows',
            'renewals',
            'openActions'
        ));
    }

    private function riskRow(Software $software): array
    {
        $discoveries = $software->discoveries;
        $validLicenses = $software->activeLicenses->reject(fn ($license) => $license->is_expired);
        $installedCount = $discoveries->count();
        $activeExceptionCount = $discoveries->filter(fn ($discovery) => $discovery->activePolicyException)->count();
        $policyViolationCount = in_array($software->policy_status, ['restricted', 'prohibited'], true)
            ? max(0, $installedCount - $activeExceptionCount)
            : 0;
        $userIds = $discoveries->pluck('user_id')->filter()->unique();
        $assetIds = $discoveries->pluck('asset_id')->filter()->unique();
        $requiredSeats = $this->requiredSeats($software, $discoveries->count(), $userIds->count(), $assetIds->count());
        $purchasedSeats = (int) $validLicenses->sum('seats');
        $missingSeats = max(0, $requiredSeats - $purchasedSeats);
        $financialExposure = $missingSeats * $this->averageSeatCost($validLicenses);
        $allocationMismatchCount = $userIds->diff($validLicenses->flatMap(fn ($license) => $license->activeAssignments)->pluck('user_id')->filter()->unique())->count();
        $status = $this->status($software, $installedCount, $requiredSeats, $purchasedSeats, $allocationMismatchCount, $policyViolationCount);
        $riskScore = $this->riskScore($software, $requiredSeats, $missingSeats, $financialExposure, $status);

        return [
            'software' => $software,
            'installed_count' => $installedCount,
            'required_seats' => $requiredSeats,
            'purchased_seats' => $purchasedSeats,
            'missing_seats' => $missingSeats,
            'financial_exposure' => $financialExposure,
            'status' => $status,
            'risk_score' => $riskScore,
            'risk_badge' => $riskScore > 70 ? 'danger' : ($riskScore > 30 ? 'warning' : 'secondary'),
        ];
    }

    private function requiredSeats(Software $software, int $discoveryCount, int $userCount, int $assetCount): int
    {
        if (! $software->license_required) {
            return 0;
        }

        return match ($software->license_metric) {
            'per_device' => $assetCount ?: $discoveryCount,
            'site', 'enterprise' => $discoveryCount > 0 ? 1 : 0,
            default => $userCount ?: $discoveryCount,
        };
    }

    private function status(Software $software, int $installedCount, int $requiredSeats, int $purchasedSeats, int $allocationMismatchCount, int $policyViolationCount): string
    {
        if ($policyViolationCount > 0 && in_array($software->policy_status, ['prohibited', 'restricted'], true)) {
            return $software->policy_status;
        }

        if (! $software->license_required) {
            return 'free';
        }

        if ($installedCount > 0 && $purchasedSeats === 0) {
            return 'unauthorized';
        }

        if ($requiredSeats > $purchasedSeats) {
            return 'under licensed';
        }

        if ($allocationMismatchCount > 0) {
            return 'allocation mismatch';
        }

        return 'review';
    }

    private function averageSeatCost($licenses): float
    {
        $seats = (int) $licenses->sum('seats');

        if ($seats <= 0) {
            return 0;
        }

        return (float) $licenses->sum(fn ($license) => $license->total_cost) / $seats;
    }

    private function riskScore(Software $software, int $requiredSeats, int $missingSeats, float $financialExposure, string $status): int
    {
        if (in_array($status, ['prohibited', 'restricted'], true)) {
            return $status === 'prohibited' ? 100 : 85;
        }

        if (in_array($status, ['free', 'review'], true)) {
            return 0;
        }

        $shortagePercentage = $requiredSeats > 0 ? ($missingSeats / $requiredSeats) * 100 : 100;
        $shortageScore = match (true) {
            $shortagePercentage <= 0 => 20,
            $shortagePercentage <= 10 => 30,
            $shortagePercentage <= 30 => 55,
            $shortagePercentage <= 60 => 80,
            default => 100,
        };
        $criticalityScore = match ($software->criticality) {
            'critical' => 100,
            'high' => 75,
            'low' => 25,
            default => 50,
        };
        $costScore = match (true) {
            $financialExposure <= 0 => 20,
            $financialExposure <= 100000 => 35,
            $financialExposure <= 1000000 => 60,
            $financialExposure <= 5000000 => 80,
            default => 100,
        };

        return (int) round(($shortageScore * 0.4) + ($criticalityScore * 0.3) + ($costScore * 0.3));
    }
}
