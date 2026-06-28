<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Software;
use App\Models\SoftwareComplianceAction;
use App\Models\SoftwareDiscovery;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SoftwarePolicyController extends Controller
{
    public function index(Request $request)
    {
        $organizationId = $this->orgId();
        $perPage = in_array((int) $request->input('per_page'), [25, 50, 100], true) ? (int) $request->input('per_page') : 25;
        $query = Software::where('organization_id', $organizationId)
            ->with('policyReviewedBy')
            ->withCount(['discoveries as installed_count' => fn ($q) => $q->where('status', 'mapped')->where('is_installed', true)])
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = trim($request->search);
                $query->where(fn ($q) => $q->where('name', 'like', "%{$search}%")->orWhere('vendor', 'like', "%{$search}%"));
            })
            ->when($request->filled('policy_status'), fn ($query) => $query->where('policy_status', $request->policy_status))
            ->when($request->installed === 'yes', fn ($query) => $query->whereHas('discoveries', fn ($q) => $q->where('status', 'mapped')->where('is_installed', true)))
            ->when($request->installed === 'no', fn ($query) => $query->whereDoesntHave('discoveries', fn ($q) => $q->where('status', 'mapped')->where('is_installed', true)))
            ->orderByRaw("CASE policy_status WHEN 'prohibited' THEN 1 WHEN 'restricted' THEN 2 WHEN 'unreviewed' THEN 3 ELSE 4 END")
            ->orderBy('name');

        $stats = [
            'unreviewed' => Software::where('organization_id', $organizationId)->where('policy_status', 'unreviewed')->count(),
            'approved' => Software::where('organization_id', $organizationId)->where('policy_status', 'approved')->count(),
            'restricted' => Software::where('organization_id', $organizationId)->where('policy_status', 'restricted')->count(),
            'prohibited_installs' => SoftwareDiscovery::where('organization_id', $organizationId)
                ->where('status', 'mapped')->where('is_installed', true)
                ->whereHas('software', fn ($q) => $q->where('policy_status', 'prohibited'))->count(),
        ];

        return view('admin.software-policies.index', [
            'software' => $query->paginate($perPage)->withQueryString(),
            'stats' => $stats,
            'perPage' => $perPage,
            'policyStatuses' => $this->policyStatuses(),
        ]);
    }

    public function update(Request $request, Software $software)
    {
        $this->authorizeSoftware($software);
        $validated = $request->validate([
            'policy_status' => 'required|in:unreviewed,approved,restricted,prohibited',
            'policy_notes' => 'nullable|string|max:2000',
        ]);
        $software->update($validated + [
            'policy_reviewed_by' => auth()->id(),
            'policy_reviewed_at' => now(),
        ]);

        return back()->with('success', $software->name.' policy changed to '.$software->policy_status_label.'.');
    }

    public function createRemediationTasks(Software $software)
    {
        $this->authorizeSoftware($software);
        abort_unless($software->policy_status === 'prohibited', 422, 'Remediation tasks can only be generated for prohibited software.');

        $query = SoftwareDiscovery::where('organization_id', $this->orgId())
            ->where('software_id', $software->id)->where('status', 'mapped')->where('is_installed', true)
            ->whereDoesntHave('activePolicyException')
            ->whereDoesntHave('complianceActions', fn ($q) => $q->where('action_type', 'uninstall_reclaim')->where('status', 'open'));
        $created = 0;
        $now = now();
        $query->select(['id', 'user_id', 'asset_id'])->chunkById(500, function ($discoveries) use ($software, $now, &$created) {
            $rows = $discoveries->map(fn ($discovery) => [
                'organization_id' => $this->orgId(), 'software_id' => $software->id,
                'software_discovery_id' => $discovery->id, 'user_id' => $discovery->user_id,
                'asset_id' => $discovery->asset_id, 'action_type' => 'uninstall_reclaim',
                'status' => 'open', 'quantity' => 1, 'due_date' => $now->copy()->addDays(7)->toDateString(),
                'owner_id' => null, 'created_by' => auth()->id(),
                'notes' => 'Remove prohibited software according to the organization software policy.',
                'created_at' => $now, 'updated_at' => $now,
            ])->all();
            if ($rows !== []) SoftwareComplianceAction::insert($rows);
            $created += count($rows);
        });

        return back()->with('success', $created.' new remediation '.str('task')->plural($created).' created. Existing open tasks were skipped.');
    }

    private function authorizeSoftware(Software $software): void
    {
        abort_if($software->organization_id !== $this->orgId(), 404);
    }

    private function policyStatuses(): array
    {
        return [
            'unreviewed' => ['label' => 'Unreviewed', 'badge' => 'secondary'],
            'approved' => ['label' => 'Approved', 'badge' => 'success'],
            'restricted' => ['label' => 'Restricted', 'badge' => 'warning'],
            'prohibited' => ['label' => 'Prohibited', 'badge' => 'danger'],
        ];
    }
}
