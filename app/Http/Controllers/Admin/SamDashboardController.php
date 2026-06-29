<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DeviceAgent;
use App\Models\PurchaseOrderItem;
use App\Models\Software;
use App\Models\SoftwareComplianceAction;
use App\Models\SoftwareDiscovery;
use App\Models\SoftwareLicense;
use App\Models\SoftwarePolicyException;
use App\Models\SoftwareRequest;
use App\Models\SoftwareRenewalDecision;
use App\Models\SoftwareUsageReview;

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
            'unlinked_devices' => (clone $deviceBase)->whereNull('asset_id')->count(),
            'unassigned_devices' => (clone $deviceBase)->whereNull('user_id')->count(),
            'devices_with_errors' => (clone $deviceBase)->whereNotNull('last_error')->count(),
            'installed_records' => (clone $discoveryBase)->count(),
            'unknown_records' => (clone $discoveryBase)->where('status', 'unknown')->count(),
            'mapped_records' => (clone $discoveryBase)->where('status', 'mapped')->count(),
            'catalog_items' => Software::where('organization_id', $organizationId)->count(),
            'unreviewed_policies' => Software::where('organization_id', $organizationId)
                ->where('policy_status', 'unreviewed')
                ->count(),
            'stale_policies' => Software::where('organization_id', $organizationId)
                ->whereNotNull('policy_reviewed_at')
                ->where('policy_reviewed_at', '<', $now->copy()->subYear())
                ->count(),
            'active_policy_exceptions' => SoftwarePolicyException::where('organization_id', $organizationId)
                ->active()
                ->count(),
            'policy_exceptions_expiring' => SoftwarePolicyException::where('organization_id', $organizationId)
                ->where('status', 'approved')
                ->whereBetween('expires_at', [$now->toDateString(), $now->copy()->addDays(14)->toDateString()])
                ->count(),
            'expired_policy_exceptions' => SoftwarePolicyException::where('organization_id', $organizationId)
                ->where('status', 'approved')
                ->where('expires_at', '<', $now->toDateString())
                ->count(),
            'prohibited_installations' => SoftwareDiscovery::where('organization_id', $organizationId)
                ->where('status', 'mapped')
                ->where('is_installed', true)
                ->whereHas('software', fn ($query) => $query->where('policy_status', 'prohibited'))
                ->count(),
            'pending_requests' => SoftwareRequest::where('organization_id', $organizationId)
                ->where('status', 'pending')
                ->count(),
            'approved_requests' => SoftwareRequest::where('organization_id', $organizationId)
                ->where('status', 'approved')
                ->count(),
            'urgent_requests' => SoftwareRequest::where('organization_id', $organizationId)
                ->whereIn('status', ['pending', 'approved'])
                ->whereIn('urgency', ['high', 'critical'])
                ->count(),
            'overdue_software_requests' => SoftwareRequest::where('organization_id', $organizationId)
                ->whereIn('status', ['pending', 'approved'])
                ->whereNotNull('needed_by')
                ->where('needed_by', '<', $now->toDateString())
                ->count(),
            'aging_software_requests' => SoftwareRequest::where('organization_id', $organizationId)
                ->whereIn('status', ['pending', 'approved'])
                ->where('created_at', '<', $now->copy()->subDays(7))
                ->count(),
            'open_software_po_items' => PurchaseOrderItem::where('item_type', 'software')
                ->whereColumn('received_quantity', '<', 'quantity')
                ->whereHas('purchaseOrder', fn ($query) => $query
                    ->where('organization_id', $organizationId)
                    ->whereNotIn('status', ['draft', 'cancelled']))
                ->count(),
            'pending_software_po_seats' => PurchaseOrderItem::where('item_type', 'software')
                ->whereHas('purchaseOrder', fn ($query) => $query
                    ->where('organization_id', $organizationId)
                    ->whereNotIn('status', ['draft', 'cancelled']))
                ->get()
                ->sum(fn ($item) => $item->pending_quantity),
            'active_licenses' => SoftwareLicense::where('organization_id', $organizationId)->where('status', 'active')->count(),
            'licenses_missing_evidence' => SoftwareLicense::where('organization_id', $organizationId)
                ->where('status', 'active')
                ->where(fn ($query) => $query
                    ->whereNull('evidence_document')
                    ->orWhereNull('invoice_number')
                    ->orWhereNull('po_number')
                    ->orWhereNull('vendor_id'))
                ->count(),
            'licenses_missing_cost' => SoftwareLicense::where('organization_id', $organizationId)
                ->where('status', 'active')
                ->whereNull('purchase_price')
                ->whereNull('unit_cost')
                ->count(),
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
            'overdue_renewal_decisions' => SoftwareRenewalDecision::where('organization_id', $organizationId)
                ->where('status', 'planned')
                ->whereNotNull('due_date')
                ->where('due_date', '<', $now->toDateString())
                ->count(),
            'renewal_decisions_due_soon' => SoftwareRenewalDecision::where('organization_id', $organizationId)
                ->where('status', 'planned')
                ->whereBetween('due_date', [$now->toDateString(), $now->copy()->addDays(14)->toDateString()])
                ->count(),
            'open_usage_reviews' => SoftwareUsageReview::where('organization_id', $organizationId)
                ->where('status', 'pending_user')
                ->count(),
            'reclaimed_savings' => SoftwareUsageReview::where('organization_id', $organizationId)
                ->where('status', 'reclaimed')
                ->sum('estimated_annual_savings'),
            'open_actions' => SoftwareComplianceAction::where('organization_id', $organizationId)->where('status', 'open')->count(),
            'overdue_actions' => SoftwareComplianceAction::where('organization_id', $organizationId)
                ->where('status', 'open')
                ->whereNotNull('due_date')
                ->where('due_date', '<', $now->toDateString())
                ->count(),
            'actions_due_soon' => SoftwareComplianceAction::where('organization_id', $organizationId)
                ->where('status', 'open')
                ->whereBetween('due_date', [$now->toDateString(), $now->copy()->addDays(7)->toDateString()])
                ->count(),
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

        $usageReviews = SoftwareUsageReview::where('organization_id', $organizationId)
            ->with(['assignment.user', 'assignment.license.software', 'owner'])
            ->orderByRaw("CASE WHEN status = 'pending_user' THEN 0 ELSE 1 END")
            ->latest()
            ->limit(8)
            ->get();

        $softwareRequests = SoftwareRequest::where('organization_id', $organizationId)
            ->whereIn('status', ['pending', 'approved'])
            ->with(['requester.department', 'software', 'purchaseOrderItem.purchaseOrder'])
            ->orderByRaw('CASE WHEN needed_by IS NOT NULL AND needed_by < ? THEN 0 ELSE 1 END', [$now->toDateString()])
            ->orderByRaw('CASE WHEN created_at < ? THEN 0 ELSE 1 END', [$now->copy()->subDays(7)])
            ->orderByRaw("CASE WHEN urgency = 'critical' THEN 0 WHEN urgency = 'high' THEN 1 ELSE 2 END")
            ->orderByRaw('CASE WHEN needed_by IS NULL THEN 1 ELSE 0 END')
            ->orderBy('needed_by')
            ->latest('id')
            ->limit(8)
            ->get();

        $softwareProcurement = PurchaseOrderItem::where('item_type', 'software')
            ->whereHas('purchaseOrder', fn ($query) => $query->where('organization_id', $organizationId))
            ->with(['purchaseOrder.supplier', 'software', 'softwareRequests', 'softwareLicenses'])
            ->latest('id')
            ->limit(8)
            ->get();

        $inventoryGaps = DeviceAgent::where('organization_id', $organizationId)
            ->where(function ($query) use ($now) {
                $query->whereNull('asset_id')
                    ->orWhereNull('user_id')
                    ->orWhereNull('last_seen_at')
                    ->orWhere('last_seen_at', '<', $now->copy()->subHours(24))
                    ->orWhereNotNull('last_error');
            })
            ->with(['asset', 'user'])
            ->orderByRaw('CASE WHEN last_seen_at IS NULL THEN 0 WHEN last_seen_at < ? THEN 1 ELSE 2 END', [$now->copy()->subDays(7)])
            ->latest('last_error_at')
            ->latest('id')
            ->limit(8)
            ->get();

        $policyGaps = Software::where('organization_id', $organizationId)
            ->with('policyReviewedBy')
            ->withCount(['discoveries as installed_count' => fn ($query) => $query->where('status', 'mapped')->where('is_installed', true)])
            ->where(function ($query) use ($now) {
                $query->whereIn('policy_status', ['unreviewed', 'restricted', 'prohibited'])
                    ->orWhereNull('policy_reviewed_at')
                    ->orWhere('policy_reviewed_at', '<', $now->copy()->subYear());
            })
            ->orderByRaw("CASE policy_status WHEN 'prohibited' THEN 0 WHEN 'restricted' THEN 1 WHEN 'unreviewed' THEN 2 ELSE 3 END")
            ->orderByDesc('installed_count')
            ->orderBy('name')
            ->limit(8)
            ->get();

        $policyExceptionRisks = SoftwarePolicyException::where('organization_id', $organizationId)
            ->where('status', 'approved')
            ->where('expires_at', '<=', $now->copy()->addDays(14)->toDateString())
            ->with(['software', 'user', 'asset', 'discovery'])
            ->orderBy('expires_at')
            ->latest('id')
            ->limit(8)
            ->get();

        $licenseEvidenceGaps = SoftwareLicense::where('organization_id', $organizationId)
            ->where('status', 'active')
            ->where(function ($query) {
                $query->whereNull('evidence_document')
                    ->orWhereNull('invoice_number')
                    ->orWhereNull('po_number')
                    ->orWhereNull('vendor_id')
                    ->orWhereNull('purchase_date')
                    ->orWhere(function ($costQuery) {
                        $costQuery->whereNull('purchase_price')->whereNull('unit_cost');
                    });
            })
            ->with(['software', 'vendor'])
            ->latest('id')
            ->limit(8)
            ->get();

        $actionSlaRisks = SoftwareComplianceAction::where('organization_id', $organizationId)
            ->where('status', 'open')
            ->whereNotNull('due_date')
            ->with(['software', 'owner'])
            ->orderBy('due_date')
            ->latest('id')
            ->limit(8)
            ->get();

        $renewalSlaRisks = SoftwareRenewalDecision::where('organization_id', $organizationId)
            ->where('status', 'planned')
            ->whereNotNull('due_date')
            ->with(['license.software', 'owner'])
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
        $samHealth = $this->samHealth($stats, $coverage, $riskRows->count());

        return view('admin.sam-dashboard.index', compact(
            'stats',
            'samHealth',
            'coverage',
            'normalizationGroups',
            'riskRows',
            'renewals',
            'openActions',
            'usageReviews',
            'softwareRequests',
            'softwareProcurement',
            'inventoryGaps',
            'policyGaps',
            'policyExceptionRisks',
            'licenseEvidenceGaps',
            'actionSlaRisks',
            'renewalSlaRisks'
        ));
    }

    private function samHealth(array $stats, array $coverage, int $riskRowCount): array
    {
        $penalties = [
            'Inventory coverage' => $coverage['healthy_percent'] >= 80 ? 0 : ($coverage['healthy_percent'] >= 60 ? 8 : 15),
            'Normalization backlog' => $coverage['normalized_percent'] >= 85 ? 0 : ($coverage['normalized_percent'] >= 65 ? 8 : 15),
            'Compliance risk' => $riskRowCount === 0 ? 0 : min(20, $riskRowCount * 3),
            'Overdue remediation' => min(15, $stats['overdue_actions'] * 5),
            'Overdue renewals' => min(10, $stats['overdue_renewal_decisions'] * 4),
            'Demand SLA' => min(10, ($stats['overdue_software_requests'] * 4) + ($stats['aging_software_requests'] * 2)),
            'Policy governance' => min(12, ($stats['unreviewed_policies'] + $stats['stale_policies'] + $stats['prohibited_installations'] + $stats['policy_exceptions_expiring'] + $stats['expired_policy_exceptions']) * 2),
            'License evidence' => min(10, $stats['licenses_missing_evidence'] * 2),
            'Inventory data quality' => min(10, ($stats['unlinked_devices'] + $stats['unassigned_devices'] + $stats['devices_with_errors']) * 2),
        ];
        $score = max(0, 100 - array_sum($penalties));

        return [
            'score' => $score,
            'label' => $score >= 80 ? 'Healthy' : ($score >= 60 ? 'Needs Attention' : 'High Risk'),
            'badge' => $score >= 80 ? 'success' : ($score >= 60 ? 'warning' : 'danger'),
            'penalties' => collect($penalties)->filter()->sortDesc(),
        ];
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
