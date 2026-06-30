<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AgentApiToken;
use App\Models\AgentCommand;
use App\Models\Asset;
use App\Models\DeviceAgent;
use App\Models\Software;
use App\Models\User;
use App\Support\AgentPackageBuilder;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;
use ZipArchive;

class AgentSourceController extends Controller
{
    public function index(Request $request)
    {
        $organizationId = $this->orgId();
        $currentVersion = config('agent.current_version', '0.1.0');
        $perPage = in_array((int) $request->input('per_page'), [25, 50, 100], true) ? (int) $request->input('per_page') : 25;
        $baseQuery = DeviceAgent::where('organization_id', $organizationId);

        $devices = (clone $baseQuery)
            ->with(['asset', 'user', 'credential'])->withCount(['discoveries' => fn ($q) => $q->where('is_installed', true)])
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = trim($request->search);
                $query->where(fn ($q) => $q->where('hostname', 'like', "%{$search}%")
                    ->orWhere('serial_number', 'like', "%{$search}%")
                    ->orWhereHas('asset', fn ($asset) => $asset->where('asset_tag', 'like', "%{$search}%"))
                    ->orWhereHas('user', fn ($user) => $user->where('name', 'like', "%{$search}%")->orWhere('employee_id', 'like', "%{$search}%")));
            })
            ->when($request->health === 'healthy', fn ($query) => $query->where('last_seen_at', '>=', now()->subHours(24)))
            ->when($request->health === 'stale', fn ($query) => $query->where('last_seen_at', '>=', now()->subDays(7))->where('last_seen_at', '<', now()->subHours(24)))
            ->when($request->health === 'offline', fn ($query) => $query->where(fn ($q) => $q->whereNull('last_seen_at')->orWhere('last_seen_at', '<', now()->subDays(7))))
            ->when($request->linking === 'fully_linked', fn ($query) => $query->whereNotNull('asset_id')->whereNotNull('user_id'))
            ->when($request->linking === 'asset_missing', fn ($query) => $query->whereNull('asset_id'))
            ->when($request->linking === 'employee_missing', fn ($query) => $query->whereNull('user_id'))
            ->when($request->access === 'active', fn ($query) => $query->whereHas('credential', fn ($q) => $q->whereNull('revoked_at')))
            ->when($request->access === 'revoked', fn ($query) => $query->whereHas('credential', fn ($q) => $q->whereNotNull('revoked_at')))
            ->when($request->access === 'pending', fn ($query) => $query->doesntHave('credential'))
            ->when($request->filled('version'), fn ($query) => $query->where('agent_version', $request->version))
            ->latest('last_seen_at')->paginate($perPage)->withQueryString();

        $tokens = AgentApiToken::where('organization_id', $organizationId)->with(['createdBy', 'assignedUser'])->latest()->get();
        $healthyCount = (clone $baseQuery)->where('last_seen_at', '>=', now()->subHours(24))->count();
        $staleCount = (clone $baseQuery)->where('last_seen_at', '>=', now()->subDays(7))->where('last_seen_at', '<', now()->subHours(24))->count();
        $offlineCount = (clone $baseQuery)->where(fn ($query) => $query->whereNull('last_seen_at')->orWhere('last_seen_at', '<', now()->subDays(7)))->count();
        $stats = [
            'devices' => (clone $baseQuery)->count(),
            'healthy' => $healthyCount,
            'stale' => $staleCount,
            'offline' => $offlineCount,
            'unlinked' => (clone $baseQuery)->where(fn ($query) => $query->whereNull('asset_id')->orWhereNull('user_id'))->count(),
            'outdated' => (clone $baseQuery)->where(fn ($query) => $query->whereNull('agent_version')->orWhere('agent_version', '!=', $currentVersion))->count(),
            'active_tokens' => $tokens->filter(fn ($token) => $token->is_active)->count(),
        ];
        $versions = (clone $baseQuery)->whereNotNull('agent_version')->select('agent_version')->distinct()->orderBy('agent_version')->pluck('agent_version');

        $assets = Asset::where('organization_id', $organizationId)->orderBy('asset_tag')->get(['id', 'asset_tag', 'name']);
        $users = User::where('organization_id', $organizationId)->whereIn('role', ['admin', 'staff'])->orderBy('name')->get(['id', 'name', 'employee_id']);
        $macosPkgAvailable = AgentPackageBuilder::hasMacosPkg();

        return view('admin.agent-sources.index', compact('devices', 'tokens', 'stats', 'versions', 'currentVersion', 'perPage', 'assets', 'users', 'macosPkgAvailable'));
    }

    public function createToken(Request $request)
    {
        $validated = $request->validate(['name' => 'required|string|max:100', 'expires_at' => 'nullable|date|after:today']);
        $plainToken = 'ops_agent_' . Str::random(64);
        AgentApiToken::create([
            'organization_id' => $this->orgId(), 'name' => $validated['name'],
            'token_prefix' => substr($plainToken, 0, 16), 'token_hash' => hash('sha256', $plainToken),
            'created_by' => auth()->id(), 'purpose' => 'admin_enrollment', 'expires_at' => $validated['expires_at'] ?? null,
        ]);
        return back()->with('success', 'Enrollment token created. Copy it now; it will not be shown again.')->with('new_agent_token', $plainToken);
    }

    public function show(DeviceAgent $deviceAgent)
    {
        abort_if($deviceAgent->organization_id !== $this->orgId(), 403);
        $deviceAgent->load(['asset', 'user', 'credential']);
        $deviceAgent->loadCount(['discoveries as installed_software_count' => fn ($query) => $query->where('is_installed', true)]);
        $discoveries = $deviceAgent->discoveries()->with('software')
            ->orderByDesc('is_installed')->orderBy('raw_name')->paginate(50);
        $commands = $deviceAgent->commands()->with('createdBy')->latest()->paginate(15, ['*'], 'commands_page')->withQueryString();
        $managedSoftware = Software::where('organization_id', $this->orgId())
            ->where('endpoint_management_enabled', true)->whereNotNull('winget_package_id')
            ->orderBy('name')->get(['id', 'name', 'vendor', 'winget_package_id']);
        return view('admin.agent-sources.show', compact('deviceAgent', 'discoveries', 'commands', 'managedSoftware'));
    }

    public function downloadWindowsPackage()
    {
        return $this->downloadAgentPackage('windows', ['OpsBridge.Agent.ps1', 'install.ps1', 'uninstall.ps1', 'README.md'], 'opsbridge-windows-agent.zip');
    }

    public function downloadUnixPackage()
    {
        return $this->downloadAgentPackage('unix', ['opsbridge_agent.py', 'install.sh', 'uninstall.sh', 'README.md'], 'opsbridge-macos-linux-agent.zip');
    }

    public function downloadUnixInstaller()
    {
        return response(AgentPackageBuilder::unixInstallerScript(), 200, [
            'Content-Type' => 'text/x-shellscript',
            'Content-Disposition' => 'attachment; filename="OpsBridge-Agent-Installer.sh"',
        ]);
    }

    public function downloadMacosInstaller()
    {
        abort_unless(AgentPackageBuilder::hasMacosPkg(), 404, 'The macOS PKG installer has not been built for this release.');

        return response()->download(AgentPackageBuilder::macosPkgPath(), 'OpsBridge-Agent-Setup.pkg', [
            'Content-Type' => 'application/octet-stream',
        ]);
    }

    public function downloadWindowsInstaller()
    {
        $installer = base_path('agent/windows/dist/OpsBridge-Agent-Setup.exe');
        abort_unless(File::isFile($installer), 404, 'The Windows installer has not been built for this release.');

        return response()->download($installer, 'OpsBridge-Agent-Setup.exe', [
            'Content-Type' => 'application/vnd.microsoft.portable-executable',
        ]);
    }

    public function revokeToken(AgentApiToken $token)
    {
        abort_if($token->organization_id !== $this->orgId(), 403);
        $token->update(['revoked_at' => now()]);
        return back()->with('success', 'Enrollment token revoked. It can no longer enroll or re-enroll devices. Already enrolled devices keep their own credentials.');
    }

    public function revokeDeviceCredential(DeviceAgent $deviceAgent)
    {
        abort_if($deviceAgent->organization_id !== $this->orgId(), 403);
        $credential = $deviceAgent->credential;
        abort_unless($credential && $credential->is_active, 422, 'This device does not have an active API credential.');
        $credential->update(['revoked_at' => now()]);

        return back()->with('success', 'Device access revoked. Re-enroll this device before it can report inventory or receive commands.');
    }

    public function updateLinking(Request $request, DeviceAgent $deviceAgent)
    {
        abort_if($deviceAgent->organization_id !== $this->orgId(), 403);
        $validated = $request->validate([
            'asset_id' => 'nullable|integer',
            'user_id' => 'nullable|integer',
        ]);

        if (! empty($validated['asset_id'])) {
            abort_unless(Asset::where('organization_id', $this->orgId())->whereKey($validated['asset_id'])->exists(), 403);
        }
        if (! empty($validated['user_id'])) {
            abort_unless(User::where('organization_id', $this->orgId())->whereKey($validated['user_id'])->exists(), 403);
        }

        $deviceAgent->update([
            'asset_id' => $validated['asset_id'] ?? null,
            'user_id' => $validated['user_id'] ?? null,
        ]);

        return back()->with('success', 'Endpoint linking updated for '.$deviceAgent->hostname.'.');
    }

    public function queueInventory(DeviceAgent $deviceAgent)
    {
        abort_if($deviceAgent->organization_id !== $this->orgId(), 403);
        $alreadyQueued = AgentCommand::where('device_agent_id', $deviceAgent->id)
            ->where('command_type', 'inventory_refresh')->whereIn('status', ['queued', 'delivered'])->exists();
        if ($alreadyQueued) return back()->with('error', 'An inventory refresh is already queued for this device.');

        AgentCommand::create([
            'organization_id' => $this->orgId(), 'device_agent_id' => $deviceAgent->id,
            'command_uuid' => (string) Str::uuid(), 'command_type' => 'inventory_refresh',
            'payload' => ['reason' => 'Administrator requested inventory refresh.'],
            'priority' => 5, 'status' => 'queued', 'available_at' => now(),
            'expires_at' => now()->addDay(), 'created_by' => auth()->id(),
        ]);
        return back()->with('success', 'Signed inventory refresh queued. The agent will collect it on the next poll.');
    }

    public function queueLock(DeviceAgent $deviceAgent)
    {
        return $this->queueEndpointCommand($deviceAgent, 'lock_session', [
            'message' => 'Your Windows session was locked by an authorized administrator.',
        ], 10, 15, 'Device lock');
    }

    public function queueRestart(Request $request, DeviceAgent $deviceAgent)
    {
        $validated = $request->validate([
            'delay_minutes' => 'required|integer|min:1|max:60',
            'message' => 'nullable|string|max:180',
        ]);

        return $this->queueEndpointCommand($deviceAgent, 'restart_device', [
            'delay_minutes' => (int) $validated['delay_minutes'],
            'message' => $validated['message'] ?: 'This device will restart to complete an administrator-requested operation.',
        ], 9, 15, 'Device restart');
    }

    public function queueSoftwareInstall(Request $request, DeviceAgent $deviceAgent)
    {
        $software = $this->managedSoftware($request);

        return $this->queueEndpointCommand($deviceAgent, 'software_install', [
            'software_id' => $software->id,
            'name' => $software->name,
            'package_id' => $software->winget_package_id,
        ], 7, 24 * 60, $software->name.' installation');
    }

    public function queueSoftwareUninstall(Request $request, DeviceAgent $deviceAgent)
    {
        $software = $this->managedSoftware($request);

        return $this->queueEndpointCommand($deviceAgent, 'software_uninstall', [
            'software_id' => $software->id,
            'name' => $software->name,
            'package_id' => $software->winget_package_id,
        ], 7, 24 * 60, $software->name.' removal');
    }

    public function bulkQueueInventory(Request $request)
    {
        $validated = $request->validate([
            'device_ids' => 'required|array|min:1|max:500',
            'device_ids.*' => 'required|integer|distinct',
        ]);
        $deviceIds = DeviceAgent::where('organization_id', $this->orgId())
            ->whereIn('id', $validated['device_ids'])->pluck('id');
        abort_if($deviceIds->count() !== count($validated['device_ids']), 403, 'One or more selected devices do not belong to this organization.');

        $pendingIds = AgentCommand::whereIn('device_agent_id', $deviceIds)
            ->where('command_type', 'inventory_refresh')->whereIn('status', ['queued', 'delivered'])
            ->pluck('device_agent_id');
        $queueIds = $deviceIds->diff($pendingIds);
        $now = now();
        $rows = $queueIds->map(fn ($deviceId) => [
            'organization_id' => $this->orgId(), 'device_agent_id' => $deviceId,
            'command_uuid' => (string) Str::uuid(), 'command_type' => 'inventory_refresh',
            'payload' => json_encode(['reason' => 'Administrator requested a bulk inventory refresh.']),
            'priority' => 5, 'status' => 'queued', 'available_at' => $now,
            'expires_at' => $now->copy()->addDay(), 'created_by' => auth()->id(),
            'created_at' => $now, 'updated_at' => $now,
        ])->all();
        if ($rows !== []) AgentCommand::insert($rows);

        $message = $queueIds->count().' inventory refresh '.Str::plural('command', $queueIds->count()).' queued.';
        if ($pendingIds->isNotEmpty()) $message .= ' '.$pendingIds->count().' already-pending '.Str::plural('device', $pendingIds->count()).' skipped.';
        return back()->with('success', $message);
    }

    public function cancelCommand(DeviceAgent $deviceAgent, AgentCommand $command)
    {
        abort_if($deviceAgent->organization_id !== $this->orgId() || $command->organization_id !== $this->orgId() || $command->device_agent_id !== $deviceAgent->id, 403);
        abort_unless(in_array($command->status, ['queued', 'delivered'], true), 422, 'Only pending commands can be cancelled.');
        $command->update(['status' => 'cancelled', 'error_message' => 'Cancelled by '.auth()->user()->name.'.']);
        return back()->with('success', 'Command cancelled.');
    }

    private function managedSoftware(Request $request): Software
    {
        $validated = $request->validate(['software_id' => 'required|integer']);

        return Software::where('organization_id', $this->orgId())
            ->where('endpoint_management_enabled', true)->whereNotNull('winget_package_id')
            ->findOrFail($validated['software_id']);
    }

    private function queueEndpointCommand(DeviceAgent $deviceAgent, string $type, array $payload, int $priority, int $expiresInMinutes, string $label)
    {
        abort_if($deviceAgent->organization_id !== $this->orgId(), 403);
        abort_unless($deviceAgent->credential?->is_active, 422, 'This endpoint does not have an active device credential.');

        $alreadyQueued = AgentCommand::where('device_agent_id', $deviceAgent->id)
            ->where('command_type', $type)->whereIn('status', ['queued', 'delivered'])->exists();
        if ($alreadyQueued) return back()->with('error', "A {$label} command is already pending for this endpoint.");

        AgentCommand::create([
            'organization_id' => $this->orgId(),
            'device_agent_id' => $deviceAgent->id,
            'command_uuid' => (string) Str::uuid(),
            'command_type' => $type,
            'payload' => $payload,
            'priority' => $priority,
            'status' => 'queued',
            'available_at' => now(),
            'expires_at' => now()->addMinutes($expiresInMinutes),
            'created_by' => auth()->id(),
        ]);

        return back()->with('success', "{$label} command queued. It will run after the endpoint's next secure check-in.");
    }

    private function downloadAgentPackage(string $platform, array $files, string $downloadName)
    {
        $source = base_path('agent/'.$platform);
        abort_unless(File::isDirectory($source), 404, 'Agent package is not available.');
        $tempDirectory = storage_path('app/temp');
        File::ensureDirectoryExists($tempDirectory);
        $zipPath = $tempDirectory . DIRECTORY_SEPARATOR . Str::beforeLast($downloadName, '.') . '-' . Str::uuid() . '.zip';
        $zip = new ZipArchive();
        abort_unless($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true, 500, 'Unable to build the agent package.');
        foreach ($files as $file) {
            $zip->addFile($source . DIRECTORY_SEPARATOR . $file, $file);
        }
        $zip->close();

        return response()->download($zipPath, $downloadName)->deleteFileAfterSend(true);
    }
}
