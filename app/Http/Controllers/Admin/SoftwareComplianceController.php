<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AgentCommand;
use App\Models\Software;
use App\Models\SoftwareAssignment;
use App\Models\SoftwareComplianceAction;
use App\Models\SoftwareDiscovery;
use App\Models\SoftwareLicense;
use App\Models\SoftwarePolicyException;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class SoftwareComplianceController extends Controller
{
    public function index(Request $request)
    {
        $organizationId = $this->orgId();

        $softwareQuery = Software::query()
            ->where('organization_id', $organizationId)
            ->with([
                'licenses.activeAssignments',
                'discoveries' => fn ($query) => $query->where('status', 'mapped')->where('is_installed', true)->with('activePolicyException'),
            ])
            ->withCount([
                'policyExceptions as expiring_policy_exceptions_count' => fn ($query) => $query
                    ->where('status', 'approved')
                    ->whereDate('expires_at', '>=', today())
                    ->whereDate('expires_at', '<=', today()->addDays(14)),
                'policyExceptions as expired_policy_exceptions_count' => fn ($query) => $query
                    ->where('status', 'approved')
                    ->whereDate('expires_at', '<', today()),
            ])
            ->orderBy('name');

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();

            $softwareQuery->where(function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('vendor', 'like', "%{$search}%")
                    ->orWhere('edition', 'like', "%{$search}%");
            });
        }

        $rows = $softwareQuery->get()
            ->map(fn (Software $software) => $this->buildComplianceRow($software))
            ->filter(fn (array $row) => $row['installed_count'] > 0 || $row['purchased_seats'] > 0 || $row['allocated_count'] > 0)
            ->values();

        if ($request->filled('status')) {
            $rows = $rows->filter(fn (array $row) => $row['status'] === $request->string('status')->toString())->values();
        }

        if (in_array($request->string('exception_risk')->toString(), ['expired', 'expiring'], true)) {
            $exceptionQuery = SoftwarePolicyException::where('organization_id', $organizationId)
                ->where('status', 'approved');

            match ($request->string('exception_risk')->toString()) {
                'expired' => $exceptionQuery->where('expires_at', '<', today()),
                'expiring' => $exceptionQuery->whereBetween('expires_at', [today(), today()->addDays(14)]),
                default => null,
            };

            $softwareIds = $exceptionQuery->pluck('software_id')->unique();
            $rows = $rows->filter(fn (array $row) => $softwareIds->contains($row['software']->id))->values();
        }

        $unknownDiscoveryCount = SoftwareDiscovery::where('organization_id', $organizationId)
            ->where('status', 'unknown')
            ->where('is_installed', true)
            ->count();

        $stats = [
            'high_risk' => $rows->where('risk_level', 'high')->count(),
            'prohibited' => $rows->where('status', 'prohibited')->count(),
            'under_licensed' => $rows->where('status', 'under_licensed')->count(),
            'unauthorized' => $rows->where('status', 'unauthorized')->count(),
            'allocation_mismatch' => $rows->where('allocation_mismatch_count', '>', 0)->count(),
            'unknown_discovery' => $unknownDiscoveryCount,
            'financial_exposure' => $rows->sum('financial_exposure'),
        ];

        return view('admin.software-compliance.index', [
            'rows' => $this->paginateCollection($rows, 25, 'software_page'),
            'stats' => $stats,
            'statuses' => $this->statuses(),
        ]);
    }

    public function show(Software $software)
    {
        abort_if($software->organization_id !== $this->orgId(), 404);

        $software->load([
            'licenses.activeAssignments.user',
            'discoveries' => fn ($query) => $query
                ->where('status', 'mapped')
                ->where('is_installed', true)
                ->with(['asset', 'user', 'activePolicyException.approvedBy'])
                ->latest('last_used_date'),
        ]);

        $row = $this->buildComplianceRow($software);
        $validLicenses = $software->licenses
            ->where('status', 'active')
            ->reject(fn ($license) => $license->is_expired);

        $discoveries = $software->discoveries;
        $assignments = $validLicenses->flatMap(fn ($license) => $license->activeAssignments);
        $discoveredUserIds = $discoveries->pluck('user_id')->filter()->unique()->values();
        $assignedUserIds = $assignments->pluck('user_id')->filter()->unique()->values();

        $mismatchedDiscoveries = $discoveries
            ->filter(fn ($discovery) => $discovery->user_id && ! $assignedUserIds->contains($discovery->user_id))
            ->values();

        $allocatedWithoutDiscovery = $assignments
            ->filter(fn ($assignment) => $assignment->user_id && ! $discoveredUserIds->contains($assignment->user_id))
            ->values();

        $missingUsers = $mismatchedDiscoveries
            ->pluck('user')
            ->filter()
            ->unique('id')
            ->sortBy('name')
            ->values();

        $availableLicenses = $validLicenses
            ->filter(fn ($license) => $license->available_seats > 0)
            ->sortBy(fn ($license) => $license->expiry_date?->timestamp ?? PHP_INT_MAX)
            ->values();

        $actions = SoftwareComplianceAction::where('organization_id', $this->orgId())
            ->where('software_id', $software->id)
            ->with(['owner', 'createdBy', 'user', 'asset', 'discovery.deviceAgent.credential'])
            ->latest()
            ->paginate(10, ['*'], 'actions_page')
            ->withQueryString();

        $openUninstallDiscoveryIds = SoftwareComplianceAction::where('organization_id', $this->orgId())
            ->where('software_id', $software->id)
            ->where('action_type', 'uninstall_reclaim')
            ->where('status', 'open')
            ->whereNotNull('software_discovery_id')
            ->pluck('software_discovery_id');

        $exceptions = SoftwarePolicyException::where('organization_id', $this->orgId())
            ->where('software_id', $software->id)
            ->with(['discovery', 'user', 'asset', 'approvedBy', 'revokedBy'])
            ->latest()->paginate(10, ['*'], 'exceptions_page')->withQueryString();

        $exceptionRiskCounts = [
            'expiring' => SoftwarePolicyException::where('organization_id', $this->orgId())
                ->where('software_id', $software->id)
                ->where('status', 'approved')
                ->whereDate('expires_at', '>=', today())
                ->whereDate('expires_at', '<=', today()->addDays(14))
                ->count(),
            'expired' => SoftwarePolicyException::where('organization_id', $this->orgId())
                ->where('software_id', $software->id)
                ->where('status', 'approved')
                ->whereDate('expires_at', '<', today())
                ->count(),
        ];

        $owners = User::where('organization_id', $this->orgId())
            ->whereIn('role', ['admin', 'staff'])
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        return view('admin.software-compliance.show', [
            'software' => $software,
            'row' => $row,
            'discoveries' => $discoveries,
            'validLicenses' => $validLicenses,
            'assignments' => $this->paginateCollection($assignments, 25, 'allocations_page'),
            'assignedUserIds' => $assignedUserIds,
            'mismatchedDiscoveries' => $mismatchedDiscoveries,
            'allocatedWithoutDiscovery' => $allocatedWithoutDiscovery,
            'discoveriesPage' => $this->paginateCollection($discoveries, 25, 'discoveries_page'),
            'missingUsers' => $this->paginateCollection($missingUsers, 25, 'missing_page'),
            'availableLicenses' => $availableLicenses,
            'actions' => $actions,
            'openUninstallDiscoveryIds' => $openUninstallDiscoveryIds,
            'exceptions' => $exceptions,
            'exceptionRiskCounts' => $exceptionRiskCounts,
            'owners' => $owners,
            'actionTypes' => $this->actionTypes(),
        ]);
    }

    public function assignMissingLicense(Request $request, Software $software)
    {
        abort_if($software->organization_id !== $this->orgId(), 404);

        $validated = $request->validate([
            'software_license_id' => 'required|exists:software_licenses,id',
            'user_id' => 'required|exists:users,id',
            'assigned_date' => 'required|date',
            'notes' => 'nullable|string|max:500',
        ]);

        $license = SoftwareLicense::where('organization_id', $this->orgId())
            ->where('software_id', $software->id)
            ->where('status', 'active')
            ->findOrFail($validated['software_license_id']);

        abort_if($license->is_expired, 422, 'This license is expired and cannot be assigned.');
        abort_if($license->available_seats <= 0, 422, 'This license has no available seats.');

        $userBelongsToOrganization = User::where('organization_id', $this->orgId())
            ->whereKey($validated['user_id'])
            ->exists();

        abort_unless($userBelongsToOrganization, 403);

        $alreadyAssigned = SoftwareAssignment::where('user_id', $validated['user_id'])
            ->where('status', 'active')
            ->whereHas('license', fn ($query) => $query
                ->where('organization_id', $this->orgId())
                ->where('software_id', $software->id))
            ->exists();

        if ($alreadyAssigned) {
            return back()->with('error', 'This employee already has an active license allocation for this software.');
        }

        SoftwareAssignment::create([
            'software_license_id' => $license->id,
            'user_id' => $validated['user_id'],
            'assigned_by' => auth()->id(),
            'assigned_date' => $validated['assigned_date'],
            'notes' => $validated['notes'] ?: 'Allocated from software compliance review.',
            'status' => 'active',
        ]);

        return back()->with('success', 'License allocated successfully from compliance review.');
    }

    public function createUninstallAction(Request $request, Software $software, SoftwareDiscovery $discovery)
    {
        abort_if($software->organization_id !== $this->orgId(), 404);
        abort_if($discovery->organization_id !== $this->orgId() || $discovery->software_id !== $software->id, 404);
        abort_if($discovery->activePolicyException()->exists(), 422, 'This installation has an active policy exception and cannot be queued for uninstall.');

        $validated = $request->validate([
            'due_date' => 'nullable|date',
            'owner_id' => 'nullable|exists:users,id',
            'notes' => 'nullable|string|max:1000',
        ]);

        if (! empty($validated['owner_id'])) {
            $ownerBelongsToOrganization = User::where('organization_id', $this->orgId())
                ->whereKey($validated['owner_id'])
                ->exists();

            abort_unless($ownerBelongsToOrganization, 403);
        }

        $existing = SoftwareComplianceAction::where('organization_id', $this->orgId())
            ->where('software_id', $software->id)
            ->where('software_discovery_id', $discovery->id)
            ->where('action_type', 'uninstall_reclaim')
            ->where('status', 'open')
            ->exists();

        if ($existing) {
            return back()->with('error', 'An open uninstall/reclaim task already exists for this discovery record.');
        }

        SoftwareComplianceAction::create([
            'organization_id' => $this->orgId(),
            'software_id' => $software->id,
            'software_discovery_id' => $discovery->id,
            'user_id' => $discovery->user_id,
            'asset_id' => $discovery->asset_id,
            'action_type' => 'uninstall_reclaim',
            'status' => 'open',
            'quantity' => 1,
            'due_date' => $validated['due_date'] ?? now()->addDays(7)->toDateString(),
            'owner_id' => $validated['owner_id'] ?? null,
            'created_by' => auth()->id(),
            'notes' => $validated['notes'] ?: 'Remove or reclaim this detected software install.',
        ]);

        return back()->with('success', 'Uninstall/reclaim task created.');
    }

    public function approvePolicyException(Request $request, Software $software, SoftwareDiscovery $discovery)
    {
        abort_if($software->organization_id !== $this->orgId(), 404);
        abort_if($discovery->organization_id !== $this->orgId() || $discovery->software_id !== $software->id || ! $discovery->is_installed, 404);
        abort_unless(in_array($software->policy_status, ['restricted', 'prohibited'], true), 422, 'Policy exceptions are only available for restricted or prohibited software.');
        abort_if($discovery->activePolicyException()->exists(), 422, 'This installation already has an active policy exception.');
        $validated = $request->validate([
            'valid_from' => 'required|date',
            'expires_at' => 'required|date|after_or_equal:valid_from',
            'reason' => 'required|string|max:2000',
            'conditions' => 'nullable|string|max:2000',
        ]);

        SoftwarePolicyException::create($validated + [
            'organization_id' => $this->orgId(), 'software_id' => $software->id,
            'software_discovery_id' => $discovery->id, 'user_id' => $discovery->user_id,
            'asset_id' => $discovery->asset_id, 'status' => 'approved', 'approved_by' => auth()->id(),
        ]);
        SoftwareComplianceAction::where('organization_id', $this->orgId())
            ->where('software_discovery_id', $discovery->id)
            ->where('action_type', 'uninstall_reclaim')->where('status', 'open')
            ->update(['status' => 'cancelled', 'completed_at' => now()]);

        return back()->with('success', 'Time-bound policy exception approved for this installation.');
    }

    public function revokePolicyException(Software $software, SoftwarePolicyException $exception)
    {
        abort_if($software->organization_id !== $this->orgId(), 404);
        abort_if($exception->organization_id !== $this->orgId() || $exception->software_id !== $software->id, 404);
        abort_unless($exception->is_active, 422, 'Only an active policy exception can be revoked.');
        $exception->update(['status' => 'revoked', 'revoked_by' => auth()->id(), 'revoked_at' => now()]);

        return back()->with('success', 'Policy exception revoked. The installation is included in policy compliance again.');
    }

    public function extendPolicyException(Request $request, Software $software, SoftwarePolicyException $exception)
    {
        abort_if($software->organization_id !== $this->orgId(), 404);
        abort_if($exception->organization_id !== $this->orgId() || $exception->software_id !== $software->id, 404);
        abort_if($exception->status === 'revoked', 422, 'Revoked policy exceptions cannot be extended.');

        $minimumExpiryRule = $exception->expires_at->gte(today())
            ? 'after:'.$exception->expires_at->toDateString()
            : 'after_or_equal:today';

        $validated = $request->validate([
            'expires_at' => ['required', 'date', $minimumExpiryRule],
            'reason' => 'required|string|max:2000',
            'conditions' => 'nullable|string|max:2000',
        ], [
            'expires_at.after' => 'Choose a date after the current exception expiry.',
            'expires_at.after_or_equal' => 'Choose today or a future date for the renewed exception.',
        ]);

        $previousExpiry = $exception->expires_at?->toDateString();
        $extensionNote = 'Extended on '.now()->format('Y-m-d').' by '.auth()->user()->name.'. Previous expiry: '.$previousExpiry.'.';
        $conditions = trim(collect([$validated['conditions'] ?? null, $extensionNote])->filter()->implode("\n"));

        $exception->update([
            'status' => 'approved',
            'expires_at' => $validated['expires_at'],
            'reason' => $validated['reason'],
            'conditions' => $conditions,
            'revoked_by' => null,
            'revoked_at' => null,
        ]);

        SoftwareComplianceAction::where('organization_id', $this->orgId())
            ->where('software_discovery_id', $exception->software_discovery_id)
            ->where('action_type', 'uninstall_reclaim')
            ->where('status', 'open')
            ->update(['status' => 'cancelled', 'completed_at' => now()]);

        return back()->with('success', 'Policy exception extended until '.Carbon::parse($validated['expires_at'])->format('d M Y').'.');
    }

    public function storeAction(Request $request, Software $software)
    {
        abort_if($software->organization_id !== $this->orgId(), 404);

        $validated = $request->validate([
            'action_type' => 'required|in:allocate_license,purchase_license,approve_exception,uninstall_reclaim',
            'quantity' => 'nullable|integer|min:1|max:99999',
            'due_date' => 'nullable|date',
            'owner_id' => 'nullable|exists:users,id',
            'notes' => 'required|string|max:1000',
        ]);

        if (! empty($validated['owner_id'])) {
            $ownerBelongsToOrganization = User::where('organization_id', $this->orgId())
                ->whereKey($validated['owner_id'])
                ->exists();

            abort_unless($ownerBelongsToOrganization, 403);
        }

        SoftwareComplianceAction::create(array_merge($validated, [
            'organization_id' => $this->orgId(),
            'software_id' => $software->id,
            'created_by' => auth()->id(),
            'status' => 'open',
        ]));

        return back()->with('success', 'Compliance action recorded.');
    }

    public function completeAction(Software $software, SoftwareComplianceAction $action)
    {
        abort_if($software->organization_id !== $this->orgId(), 404);
        abort_if($action->organization_id !== $this->orgId() || $action->software_id !== $software->id, 404);

        $action->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        if ($action->action_type === 'uninstall_reclaim' && $action->discovery) {
            $action->discovery->update([
                'status' => 'ignored',
                'reviewed_by' => auth()->id(),
                'reviewed_at' => now(),
            ]);
        }

        return back()->with('success', 'Compliance action marked as completed.');
    }

    public function queueUninstallCommand(Software $software, SoftwareComplianceAction $action)
    {
        abort_if($software->organization_id !== $this->orgId(), 404);
        abort_if($action->organization_id !== $this->orgId() || $action->software_id !== $software->id, 404);
        abort_unless($action->status === 'open' && $action->action_type === 'uninstall_reclaim', 422, 'Only open uninstall/reclaim actions can be sent to an endpoint.');
        abort_unless($software->endpoint_management_enabled && filled($software->winget_package_id), 422, 'Endpoint uninstall requires this software to be enabled for endpoint deployment with a WinGet package ID.');

        $discovery = $action->discovery()->with('deviceAgent.credential')->first();
        abort_unless($discovery?->deviceAgent, 422, 'This remediation action is not linked to a managed endpoint.');
        abort_unless($discovery->deviceAgent->credential?->is_active, 422, 'This endpoint does not have an active device credential.');

        $alreadyQueued = AgentCommand::where('device_agent_id', $discovery->deviceAgent->id)
            ->where('command_type', 'software_uninstall')
            ->whereIn('status', ['queued', 'delivered'])
            ->exists();
        if ($alreadyQueued) {
            return back()->with('error', 'A software uninstall command is already pending for this endpoint.');
        }

        AgentCommand::create([
            'organization_id' => $this->orgId(),
            'device_agent_id' => $discovery->deviceAgent->id,
            'command_uuid' => (string) Str::uuid(),
            'command_type' => 'software_uninstall',
            'payload' => [
                'software_id' => $software->id,
                'name' => $software->name,
                'package_id' => $software->winget_package_id,
                'compliance_action_id' => $action->id,
                'discovery_id' => $discovery->id,
                'reason' => 'SAM remediation action',
            ],
            'priority' => 8,
            'status' => 'queued',
            'available_at' => now(),
            'expires_at' => now()->addDay(),
            'created_by' => auth()->id(),
        ]);

        $note = trim((string) $action->notes);
        $action->update([
            'notes' => trim($note."\nEndpoint uninstall command queued on ".now()->format('Y-m-d H:i').'.'),
        ]);

        return back()->with('success', 'Endpoint uninstall command queued for '.$discovery->deviceAgent->hostname.'.');
    }

    private function buildComplianceRow(Software $software): array
    {
        $discoveries = $software->discoveries;
        $licenses = $software->licenses;
        $validLicenses = $licenses
            ->where('status', 'active')
            ->reject(fn ($license) => $license->is_expired);

        $installedCount = $discoveries->count();
        $activeExceptionCount = $discoveries->filter(fn ($discovery) => $discovery->activePolicyException)->count();
        $policyViolationCount = in_array($software->policy_status, ['restricted', 'prohibited'], true)
            ? max(0, $installedCount - $activeExceptionCount) : 0;
        $discoveredUserIds = $discoveries->pluck('user_id')->filter()->unique()->values();
        $discoveredAssetIds = $discoveries->pluck('asset_id')->filter()->unique()->values();
        $allocatedAssignments = $validLicenses->flatMap(fn ($license) => $license->activeAssignments);
        $allocatedUserIds = $allocatedAssignments->pluck('user_id')->filter()->unique()->values();

        $requiredSeats = $this->requiredSeats($software, $discoveries, $discoveredUserIds, $discoveredAssetIds);
        $purchasedSeats = (int) $validLicenses->sum('seats');
        $allocatedCount = $allocatedAssignments->count();
        $allocationMismatchCount = $discoveredUserIds->diff($allocatedUserIds)->count();
        $expiredLicenseCount = $licenses->where('status', 'active')->filter(fn ($license) => $license->is_expired)->count();
        $missingSeats = max(0, $requiredSeats - $purchasedSeats);
        $financialExposure = $missingSeats * $this->averageSeatCost($validLicenses);
        $status = $this->status($software, $installedCount, $requiredSeats, $purchasedSeats, $expiredLicenseCount, $allocationMismatchCount, $policyViolationCount);
        $riskScore = $this->riskScore($software, $requiredSeats, $missingSeats, $financialExposure, $status);

        return [
            'software' => $software,
            'installed_count' => $installedCount,
            'active_exception_count' => $activeExceptionCount,
            'expiring_policy_exception_count' => (int) ($software->expiring_policy_exceptions_count ?? 0),
            'expired_policy_exception_count' => (int) ($software->expired_policy_exceptions_count ?? 0),
            'policy_violation_count' => $policyViolationCount,
            'discovered_users' => $discoveredUserIds->count(),
            'discovered_devices' => $discoveredAssetIds->count(),
            'required_seats' => $requiredSeats,
            'purchased_seats' => $purchasedSeats,
            'allocated_count' => $allocatedCount,
            'allocation_mismatch_count' => $allocationMismatchCount,
            'expired_license_count' => $expiredLicenseCount,
            'missing_seats' => $missingSeats,
            'financial_exposure' => $financialExposure,
            'status' => $status,
            'status_meta' => $this->statuses()[$status],
            'risk_score' => $riskScore,
            'risk_level' => $this->riskLevel($riskScore),
        ];
    }

    private function paginateCollection(Collection $items, int $perPage, string $pageName): LengthAwarePaginator
    {
        $page = LengthAwarePaginator::resolveCurrentPage($pageName);

        return new LengthAwarePaginator(
            $items->forPage($page, $perPage)->values(),
            $items->count(),
            $perPage,
            $page,
            [
                'path' => request()->url(),
                'pageName' => $pageName,
                'query' => request()->query(),
            ]
        );
    }

    private function requiredSeats(Software $software, Collection $discoveries, Collection $userIds, Collection $assetIds): int
    {
        if (! $software->license_required) {
            return 0;
        }

        return match ($software->license_metric) {
            'per_device' => $assetIds->count() ?: $discoveries->count(),
            'site', 'enterprise' => $discoveries->isNotEmpty() ? 1 : 0,
            default => $userIds->count() ?: $discoveries->count(),
        };
    }

    private function status(Software $software, int $installedCount, int $requiredSeats, int $purchasedSeats, int $expiredLicenseCount, int $allocationMismatchCount, int $policyViolationCount): string
    {
        if ($policyViolationCount > 0 && in_array($software->policy_status, ['prohibited', 'restricted'], true)) {
            return $software->policy_status;
        }

        if (! $software->license_required) {
            return 'free';
        }

        if ($installedCount > 0 && $purchasedSeats === 0) {
            return $expiredLicenseCount > 0 ? 'expired' : 'unauthorized';
        }

        if ($requiredSeats > $purchasedSeats) {
            return 'under_licensed';
        }

        if ($allocationMismatchCount > 0) {
            return 'allocation_mismatch';
        }

        if ($purchasedSeats > $requiredSeats && $requiredSeats > 0) {
            return 'over_licensed';
        }

        return 'compliant';
    }

    private function averageSeatCost(Collection $licenses): float
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

        if (in_array($status, ['free', 'compliant', 'over_licensed'], true)) {
            return 0;
        }

        $shortagePercentage = $requiredSeats > 0 ? ($missingSeats / $requiredSeats) * 100 : 100;
        $shortageScore = match (true) {
            $shortagePercentage <= 0 => 0,
            $shortagePercentage <= 10 => 20,
            $shortagePercentage <= 30 => 50,
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
            $financialExposure <= 0 => 0,
            $financialExposure <= 100000 => 25,
            $financialExposure <= 1000000 => 50,
            $financialExposure <= 5000000 => 75,
            default => 100,
        };

        return (int) round(($shortageScore * 0.4) + ($criticalityScore * 0.3) + ($costScore * 0.3));
    }

    private function riskLevel(int $score): string
    {
        return match (true) {
            $score > 70 => 'high',
            $score > 30 => 'medium',
            default => 'low',
        };
    }

    private function statuses(): array
    {
        return [
            'prohibited' => ['label' => 'Prohibited by Policy', 'badge' => 'danger'],
            'restricted' => ['label' => 'Restricted - Exception Required', 'badge' => 'warning'],
            'compliant' => ['label' => 'Compliant', 'badge' => 'success'],
            'under_licensed' => ['label' => 'Under Licensed', 'badge' => 'danger'],
            'over_licensed' => ['label' => 'Over Licensed', 'badge' => 'info'],
            'unauthorized' => ['label' => 'Unauthorized Install', 'badge' => 'danger'],
            'expired' => ['label' => 'Expired License', 'badge' => 'warning'],
            'allocation_mismatch' => ['label' => 'Allocation Mismatch', 'badge' => 'warning'],
            'free' => ['label' => 'No License Required', 'badge' => 'secondary'],
        ];
    }

    private function actionTypes(): array
    {
        return [
            'allocate_license' => 'Allocate License',
            'purchase_license' => 'Purchase License',
            'approve_exception' => 'Approve Exception',
            'uninstall_reclaim' => 'Uninstall / Reclaim',
        ];
    }
}
