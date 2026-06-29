<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\Software;
use App\Models\SoftwareDiscovery;
use App\Models\SoftwareRecognitionRule;
use App\Models\User;
use App\Services\SoftwareRecognitionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SoftwareDiscoveryController extends Controller
{
    public function __construct(private readonly SoftwareRecognitionService $recognitionService)
    {
    }

    public function index(Request $request)
    {
        return $this->listDiscoveries($request, false);
    }

    public function workbench(Request $request)
    {
        $organizationId = $this->orgId();
        $perPage = in_array((int) $request->input('per_page'), [25, 50, 100], true) ? (int) $request->input('per_page') : 25;
        $baseQuery = SoftwareDiscovery::where('organization_id', $organizationId)
            ->where('status', 'unknown')->where('is_installed', true)
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = trim($request->search);
                $query->where(fn ($q) => $q->where('raw_name', 'like', "%{$search}%")
                    ->orWhere('raw_publisher', 'like', "%{$search}%"));
            });

        $groups = (clone $baseQuery)
            ->select(['raw_name', 'raw_publisher'])
            ->selectRaw('COUNT(*) as installation_count')
            ->selectRaw('COUNT(DISTINCT device_agent_id) as device_count')
            ->selectRaw('COUNT(DISTINCT user_id) as user_count')
            ->selectRaw('COUNT(DISTINCT raw_version) as version_count')
            ->selectRaw('MAX(last_seen_at) as latest_seen_at')
            ->groupBy('raw_name', 'raw_publisher')
            ->orderByDesc('installation_count')->orderBy('raw_name')
            ->paginate($perPage)->withQueryString();

        $signatureQuery = (clone $baseQuery)->select(['raw_name', 'raw_publisher'])->groupBy('raw_name', 'raw_publisher');
        $stats = [
            'records' => (clone $baseQuery)->count(),
            'signatures' => DB::query()->fromSub($signatureQuery, 'unknown_signatures')->count(),
            'devices' => (clone $baseQuery)->whereNotNull('device_agent_id')->distinct()->count('device_agent_id'),
            'publishers' => (clone $baseQuery)->whereNotNull('raw_publisher')->distinct()->count('raw_publisher'),
        ];
        $software = Software::where('organization_id', $organizationId)->orderBy('name')->get(['id', 'name', 'vendor', 'edition']);

        return view('admin.software-discovery.workbench', compact('groups', 'software', 'stats', 'perPage'));
    }

    private function listDiscoveries(Request $request, bool $workbench)
    {
        $query = SoftwareDiscovery::where('organization_id', $this->orgId())
            ->with(['asset', 'user', 'software', 'deviceAgent'])
            ->latest();

        if ($workbench) {
            $query->where('status', 'unknown')->where('is_installed', true);
        } elseif ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if (! $workbench) {
            if ($request->input('inventory_state', 'installed') === 'removed') {
                $query->where('is_installed', false);
            } elseif ($request->input('inventory_state', 'installed') !== 'all') {
                $query->where('is_installed', true);
            }
        }

        if ($request->filled('search')) {
            $query->where(function ($searchQuery) use ($request) {
                $searchQuery->where('raw_name', 'like', '%' . $request->search . '%')
                    ->orWhere('raw_publisher', 'like', '%' . $request->search . '%')
                    ->orWhereHas('asset', fn ($assetQuery) => $assetQuery->where('asset_tag', 'like', '%' . $request->search . '%'))
                    ->orWhereHas('deviceAgent', fn ($agentQuery) => $agentQuery->where('hostname', 'like', '%' . $request->search . '%'))
                    ->orWhereHas('user', fn ($userQuery) => $userQuery->where('name', 'like', '%' . $request->search . '%')->orWhere('email', 'like', '%' . $request->search . '%'));
            });
        }

        $discoveries = $query->paginate(25)->withQueryString();
        $software = Software::where('organization_id', $this->orgId())->orderBy('name')->get(['id', 'name', 'vendor', 'edition']);

        $stats = [
            'total' => SoftwareDiscovery::where('organization_id', $this->orgId())->where('is_installed', true)->count(),
            'unknown' => SoftwareDiscovery::where('organization_id', $this->orgId())->where('is_installed', true)->where('status', 'unknown')->count(),
            'mapped' => SoftwareDiscovery::where('organization_id', $this->orgId())->where('is_installed', true)->where('status', 'mapped')->count(),
            'removed' => SoftwareDiscovery::where('organization_id', $this->orgId())->where('is_installed', false)->count(),
        ];

        return view('admin.software-discovery.index', compact('discoveries', 'software', 'stats', 'workbench'));
    }

    public function import()
    {
        return view('admin.software-discovery.import');
    }

    public function template(): StreamedResponse
    {
        return response()->streamDownload(function () {
            $out = fopen('php://output', 'w');
            fputcsv($out, [
                'device_asset_tag',
                'employee_email',
                'raw_name',
                'raw_publisher',
                'raw_version',
                'executable',
                'install_path',
                'install_date',
                'last_used_date',
                'usage_count',
                'total_runtime_minutes',
            ]);
            fputcsv($out, [
                'LAP-001',
                'employee@company.com',
                'Microsoft 365 Apps for enterprise',
                'Microsoft Corporation',
                '16.0',
                'winword.exe',
                'C:\\Program Files\\Microsoft Office',
                now()->subYear()->format('Y-m-d'),
                now()->subDays(5)->format('Y-m-d'),
                42,
                1250,
            ]);
            fclose($out);
        }, 'software-discovery-template.csv', ['Content-Type' => 'text/csv']);
    }

    public function storeImport(Request $request)
    {
        $request->validate([
            'discovery_file' => ['required', 'file', 'mimes:csv,txt', 'max:5120'],
        ]);

        $path = $request->file('discovery_file')->getRealPath();
        $handle = fopen($path, 'r');
        if (!$handle) {
            return back()->with('error', 'Unable to read uploaded file.');
        }

        $header = fgetcsv($handle);
        if (!$header) {
            fclose($handle);
            return back()->with('error', 'CSV file is empty.');
        }

        $columns = collect($header)->map(fn ($value) => strtolower(trim($value)))->flip();
        $required = ['raw_name'];
        foreach ($required as $field) {
            if (!$columns->has($field)) {
                fclose($handle);
                return back()->with('error', 'Missing required CSV column: ' . $field);
            }
        }

        $created = 0;
        $mapped = 0;
        $skipped = 0;

        DB::transaction(function () use ($handle, $columns, &$created, &$mapped, &$skipped) {
            while (($row = fgetcsv($handle)) !== false) {
                $value = function ($key) use ($columns, $row) {
                    if (!$columns->has($key)) {
                        return null;
                    }

                    $cell = trim((string) ($row[$columns[$key]] ?? ''));

                    return $cell === '' ? null : $cell;
                };
                $rawName = $value('raw_name');
                if (!$rawName) {
                    $skipped++;
                    continue;
                }

                $asset = $value('device_asset_tag')
                    ? Asset::where('organization_id', $this->orgId())->where('asset_tag', $value('device_asset_tag'))->first()
                    : null;
                $user = $value('employee_email')
                    ? User::where('organization_id', $this->orgId())->where('email', $value('employee_email'))->first()
                    : null;
                $match = $this->recognitionService->recognize($this->orgId(), $rawName, $value('raw_publisher'));

                SoftwareDiscovery::create([
                    'organization_id' => $this->orgId(),
                    'asset_id' => $asset?->id,
                    'user_id' => $user?->id,
                    'software_id' => $match['software_id'],
                    'raw_name' => $rawName,
                    'raw_publisher' => $value('raw_publisher') ?: null,
                    'raw_version' => $value('raw_version') ?: null,
                    'executable' => $value('executable') ?: null,
                    'install_path' => $value('install_path') ?: null,
                    'install_date' => $value('install_date'),
                    'last_used_date' => $value('last_used_date'),
                    'usage_count' => $value('usage_count') ? (int) $value('usage_count') : null,
                    'total_runtime_minutes' => $value('total_runtime_minutes') ? (int) $value('total_runtime_minutes') : null,
                    'source' => 'csv',
                    'status' => $match['software_id'] ? 'mapped' : 'unknown',
                    'confidence_score' => $match['confidence'],
                    'reviewed_by' => $match['software_id'] ? auth()->id() : null,
                    'reviewed_at' => $match['software_id'] ? now() : null,
                ]);

                $created++;
                if ($match['software_id']) {
                    $mapped++;
                }
            }
        });

        fclose($handle);

        return redirect()->route('admin.software-discovery.index')
            ->with('success', "{$created} discovery record(s) imported. {$mapped} auto-mapped, {$skipped} skipped.");
    }

    public function normalize(Request $request, SoftwareDiscovery $discovery)
    {
        $this->authorizeDiscovery($discovery);

        $data = $request->validate([
            'software_id' => ['required', 'exists:software,id'],
            'confidence_score' => ['nullable', 'integer', 'min:1', 'max:100'],
            'create_rule' => ['nullable', 'boolean'],
        ]);

        $software = Software::where('organization_id', $this->orgId())->findOrFail($data['software_id']);

        DB::transaction(function () use ($discovery, $software, $data) {
            $discovery->update([
                'software_id' => $software->id,
                'status' => 'mapped',
                'confidence_score' => $data['confidence_score'] ?? 95,
                'reviewed_by' => auth()->id(),
                'reviewed_at' => now(),
            ]);

            if (!empty($data['create_rule'])) {
                SoftwareRecognitionRule::firstOrCreate([
                    'organization_id' => $this->orgId(),
                    'software_id' => $software->id,
                    'raw_name_pattern' => $discovery->raw_name,
                    'raw_publisher_pattern' => $discovery->raw_publisher,
                ], [
                    'confidence_score' => $data['confidence_score'] ?? 95,
                    'approved_by' => auth()->id(),
                ]);
            }
        });

        return back()->with('success', 'Discovery record mapped to software catalogue.');
    }

    public function ignore(SoftwareDiscovery $discovery)
    {
        $this->authorizeDiscovery($discovery);

        $discovery->update([
            'status' => 'ignored',
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);

        return back()->with('success', 'Discovery record ignored.');
    }

    public function normalizeGroup(Request $request)
    {
        $data = $request->validate([
            'raw_name' => ['required', 'string', 'max:255'],
            'raw_publisher' => ['nullable', 'string', 'max:255'],
            'software_id' => ['required', 'integer'],
            'confidence_score' => ['nullable', 'integer', 'min:1', 'max:100'],
            'create_rule' => ['nullable', 'boolean'],
        ]);
        $software = Software::where('organization_id', $this->orgId())->findOrFail($data['software_id']);
        $confidence = $data['confidence_score'] ?? 95;
        $publisher = filled($data['raw_publisher'] ?? null) ? $data['raw_publisher'] : null;

        $updated = DB::transaction(function () use ($data, $software, $confidence, $publisher) {
            $updated = $this->signatureQuery($data['raw_name'], $publisher)
                ->update([
                    'software_id' => $software->id, 'status' => 'mapped',
                    'confidence_score' => $confidence, 'reviewed_by' => auth()->id(),
                    'reviewed_at' => now(), 'updated_at' => now(),
                ]);

            if (! empty($data['create_rule'])) {
                SoftwareRecognitionRule::firstOrCreate([
                    'organization_id' => $this->orgId(),
                    'software_id' => $software->id,
                    'raw_name_pattern' => $data['raw_name'],
                    'raw_publisher_pattern' => $publisher,
                ], ['confidence_score' => $confidence, 'approved_by' => auth()->id()]);
            }

            return $updated;
        });
        $this->recognitionService->forgetOrganization($this->orgId());

        return back()->with('success', $updated.' matching '.str('installation')->plural($updated).' mapped to '.$software->name.'.');
    }

    public function createAndNormalizeGroup(Request $request)
    {
        $data = $request->validate([
            'raw_name' => ['required', 'string', 'max:255'],
            'raw_publisher' => ['nullable', 'string', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
            'vendor' => ['nullable', 'string', 'max:255'],
            'category' => ['required', 'in:productivity,security,design,development,communication,database,erp,operating_system,other'],
            'software_type' => ['required', 'in:commercial,saas,open_source,freeware,os'],
            'license_required' => ['required', 'boolean'],
            'criticality' => ['required', 'in:low,medium,high,critical'],
            'license_metric' => ['required', 'in:per_user,per_device,concurrent,site,enterprise,usage_based'],
            'confidence_score' => ['nullable', 'integer', 'min:1', 'max:100'],
            'create_rule' => ['nullable', 'boolean'],
        ]);

        $publisher = filled($data['raw_publisher'] ?? null) ? $data['raw_publisher'] : null;
        $confidence = $data['confidence_score'] ?? 95;

        $result = DB::transaction(function () use ($data, $publisher, $confidence) {
            $software = Software::create([
                'organization_id' => $this->orgId(),
                'name' => $data['name'],
                'vendor' => $data['vendor'] ?? null,
                'category' => $data['category'],
                'software_type' => $data['software_type'],
                'license_required' => (bool) $data['license_required'],
                'criticality' => $data['criticality'],
                'license_metric' => $data['license_metric'],
                'trusted_publisher' => false,
                'endpoint_management_enabled' => false,
                'policy_status' => 'unreviewed',
            ]);

            $updated = $this->signatureQuery($data['raw_name'], $publisher)
                ->update([
                    'software_id' => $software->id,
                    'status' => 'mapped',
                    'confidence_score' => $confidence,
                    'reviewed_by' => auth()->id(),
                    'reviewed_at' => now(),
                    'updated_at' => now(),
                ]);

            if (! empty($data['create_rule'])) {
                SoftwareRecognitionRule::firstOrCreate([
                    'organization_id' => $this->orgId(),
                    'software_id' => $software->id,
                    'raw_name_pattern' => $data['raw_name'],
                    'raw_publisher_pattern' => $publisher,
                ], ['confidence_score' => $confidence, 'approved_by' => auth()->id()]);
            }

            return [$software, $updated];
        });

        $this->recognitionService->forgetOrganization($this->orgId());

        [$software, $updated] = $result;

        return redirect()
            ->route('admin.software-normalization.index')
            ->with('success', $software->name.' was added to the catalog and '.$updated.' matching '.str('installation')->plural($updated).' mapped.');
    }

    public function ignoreGroup(Request $request)
    {
        $data = $request->validate([
            'raw_name' => ['required', 'string', 'max:255'],
            'raw_publisher' => ['nullable', 'string', 'max:255'],
        ]);
        $publisher = filled($data['raw_publisher'] ?? null) ? $data['raw_publisher'] : null;
        $updated = $this->signatureQuery($data['raw_name'], $publisher)->update([
            'status' => 'ignored', 'reviewed_by' => auth()->id(),
            'reviewed_at' => now(), 'updated_at' => now(),
        ]);

        return back()->with('success', $updated.' matching '.str('installation')->plural($updated).' ignored.');
    }

    public function recognitionRules(Request $request)
    {
        $organizationId = $this->orgId();
        $perPage = in_array((int) $request->input('per_page'), [25, 50, 100], true) ? (int) $request->input('per_page') : 25;
        $query = SoftwareRecognitionRule::where('organization_id', $organizationId)
            ->with(['software', 'approvedBy'])
            ->when($request->filled('software_id'), fn ($q) => $q->where('software_id', $request->software_id))
            ->when($request->filled('search'), function ($q) use ($request) {
                $search = trim($request->search);
                $q->where(fn ($rule) => $rule->where('raw_name_pattern', 'like', "%{$search}%")
                    ->orWhere('raw_publisher_pattern', 'like', "%{$search}%")
                    ->orWhereHas('software', fn ($software) => $software->where('name', 'like', "%{$search}%")->orWhere('vendor', 'like', "%{$search}%")));
            })
            ->latest();

        $rules = $query->paginate($perPage)->withQueryString();
        $software = Software::where('organization_id', $organizationId)->orderBy('name')->get(['id', 'name', 'vendor', 'edition']);
        $allRules = SoftwareRecognitionRule::where('organization_id', $organizationId)->get();
        $stats = [
            'rules' => $allRules->count(),
            'publisher_scoped' => $allRules->filter(fn ($rule) => filled($rule->raw_publisher_pattern))->count(),
            'avg_confidence' => (int) round($allRules->avg('confidence_score') ?? 0),
            'mapped_titles' => $allRules->pluck('software_id')->unique()->count(),
        ];

        return view('admin.software-discovery.recognition-rules', compact('rules', 'software', 'stats', 'perPage'));
    }

    public function storeRecognitionRule(Request $request)
    {
        $data = $request->validate([
            'software_id' => ['required', 'integer'],
            'raw_name_pattern' => ['required', 'string', 'max:255'],
            'raw_publisher_pattern' => ['nullable', 'string', 'max:255'],
            'confidence_score' => ['required', 'integer', 'min:1', 'max:100'],
        ]);
        $software = Software::where('organization_id', $this->orgId())->findOrFail($data['software_id']);
        $publisher = filled($data['raw_publisher_pattern'] ?? null) ? $data['raw_publisher_pattern'] : null;

        $rule = SoftwareRecognitionRule::firstOrCreate([
            'organization_id' => $this->orgId(),
            'software_id' => $software->id,
            'raw_name_pattern' => trim($data['raw_name_pattern']),
            'raw_publisher_pattern' => $publisher ? trim($publisher) : null,
        ], [
            'confidence_score' => $data['confidence_score'],
            'approved_by' => auth()->id(),
        ]);

        if (! $rule->wasRecentlyCreated) {
            $rule->update([
                'confidence_score' => $data['confidence_score'],
                'approved_by' => auth()->id(),
            ]);
        }

        $this->recognitionService->forgetOrganization($this->orgId());

        return back()->with('success', 'Recognition rule saved for '.$software->name.'.');
    }

    public function destroyRecognitionRule(SoftwareRecognitionRule $rule)
    {
        abort_if($rule->organization_id !== $this->orgId(), 403);
        $rule->delete();
        $this->recognitionService->forgetOrganization($this->orgId());

        return back()->with('success', 'Recognition rule deleted.');
    }

    private function signatureQuery(string $rawName, ?string $publisher)
    {
        return SoftwareDiscovery::where('organization_id', $this->orgId())
            ->where('status', 'unknown')->where('is_installed', true)
            ->where('raw_name', $rawName)
            ->when($publisher !== null,
                fn ($query) => $query->where('raw_publisher', $publisher),
                fn ($query) => $query->where(fn ($q) => $q->whereNull('raw_publisher')->orWhere('raw_publisher', ''))
            );
    }

    private function authorizeDiscovery(SoftwareDiscovery $discovery): void
    {
        abort_if($discovery->organization_id !== $this->orgId(), 403);
    }
}
