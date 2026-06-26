<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\Software;
use App\Models\SoftwareDiscovery;
use App\Models\SoftwareRecognitionRule;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SoftwareDiscoveryController extends Controller
{
    public function index(Request $request)
    {
        return $this->listDiscoveries($request, false);
    }

    public function workbench(Request $request)
    {
        return $this->listDiscoveries($request, true);
    }

    private function listDiscoveries(Request $request, bool $workbench)
    {
        $query = SoftwareDiscovery::where('organization_id', $this->orgId())
            ->with(['asset', 'user', 'software'])
            ->latest();

        if ($workbench) {
            $query->where('status', 'unknown');
        } elseif ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $query->where(function ($searchQuery) use ($request) {
                $searchQuery->where('raw_name', 'like', '%' . $request->search . '%')
                    ->orWhere('raw_publisher', 'like', '%' . $request->search . '%')
                    ->orWhereHas('asset', fn ($assetQuery) => $assetQuery->where('asset_tag', 'like', '%' . $request->search . '%'))
                    ->orWhereHas('user', fn ($userQuery) => $userQuery->where('name', 'like', '%' . $request->search . '%')->orWhere('email', 'like', '%' . $request->search . '%'));
            });
        }

        $discoveries = $query->paginate(25)->withQueryString();
        $software = Software::where('organization_id', $this->orgId())->orderBy('name')->get(['id', 'name', 'vendor', 'edition']);

        $stats = [
            'total' => SoftwareDiscovery::where('organization_id', $this->orgId())->count(),
            'unknown' => SoftwareDiscovery::where('organization_id', $this->orgId())->where('status', 'unknown')->count(),
            'mapped' => SoftwareDiscovery::where('organization_id', $this->orgId())->where('status', 'mapped')->count(),
            'ignored' => SoftwareDiscovery::where('organization_id', $this->orgId())->where('status', 'ignored')->count(),
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
                $match = $this->recognize($rawName, $value('raw_publisher'));

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

    private function recognize(string $rawName, ?string $publisher): array
    {
        $rawNameLower = strtolower($rawName);
        $publisherLower = strtolower((string) $publisher);

        $rules = SoftwareRecognitionRule::where('organization_id', $this->orgId())->with('software')->get();
        foreach ($rules as $rule) {
            $nameMatches = str_contains($rawNameLower, strtolower($rule->raw_name_pattern));
            $publisherMatches = !$rule->raw_publisher_pattern || str_contains($publisherLower, strtolower($rule->raw_publisher_pattern));
            if ($nameMatches && $publisherMatches) {
                return ['software_id' => $rule->software_id, 'confidence' => $rule->confidence_score];
            }
        }

        $software = Software::where('organization_id', $this->orgId())->get()->first(function (Software $software) use ($rawNameLower, $publisherLower) {
            $nameMatches = str_contains($rawNameLower, strtolower($software->name));
            $publisherMatches = !$software->vendor || !$publisherLower || str_contains($publisherLower, strtolower($software->vendor));

            return $nameMatches && $publisherMatches;
        });

        return [
            'software_id' => $software?->id,
            'confidence' => $software ? 85 : null,
        ];
    }

    private function authorizeDiscovery(SoftwareDiscovery $discovery): void
    {
        abort_if($discovery->organization_id !== $this->orgId(), 403);
    }
}
