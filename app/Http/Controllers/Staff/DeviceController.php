<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\AgentApiToken;
use App\Models\DeviceAgent;
use App\Support\AgentPackageBuilder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use ZipArchive;

class DeviceController extends Controller
{
    public function index()
    {
        $devices = DeviceAgent::where('organization_id', $this->orgId())
            ->where('user_id', auth()->id())
            ->with('credential')
            ->latest('last_seen_at')
            ->get();

        $tokens = AgentApiToken::where('organization_id', $this->orgId())
            ->where('assigned_user_id', auth()->id())
            ->latest()
            ->limit(5)
            ->get();

        $currentVersion = config('agent.current_version', '0.1.0');
        $agentEndpoint = url('/api/v1/agent/check-in');

        return view('staff.devices.index', compact('devices', 'tokens', 'currentVersion', 'agentEndpoint'));
    }

    public function createToken(Request $request)
    {
        $user = $request->user();
        $plainToken = 'ops_agent_' . Str::random(64);

        AgentApiToken::create([
            'organization_id' => $this->orgId(),
            'name' => 'Employee self-install: ' . $user->name,
            'token_prefix' => substr($plainToken, 0, 16),
            'token_hash' => hash('sha256', $plainToken),
            'created_by' => $user->id,
            'assigned_user_id' => $user->id,
            'purpose' => 'employee_self_install',
            'expires_at' => now()->addDay(),
        ]);

        return back()
            ->with('success', 'Device setup token created. Copy it now; it will not be shown again.')
            ->with('new_agent_token', $plainToken);
    }

    public function downloadWindowsInstaller()
    {
        $installer = base_path('agent/windows/dist/OpsBridge-Agent-Setup.exe');
        abort_unless(File::isFile($installer), 404, 'The Windows installer has not been built for this release.');

        return response()->download($installer, 'OpsBridge-Agent-Setup.exe', [
            'Content-Type' => 'application/vnd.microsoft.portable-executable',
        ]);
    }

    public function downloadUnixPackage()
    {
        $source = base_path('agent/unix');
        abort_unless(File::isDirectory($source), 404, 'macOS/Linux agent package is not available.');
        $tempDirectory = storage_path('app/temp');
        File::ensureDirectoryExists($tempDirectory);
        $zipPath = $tempDirectory . DIRECTORY_SEPARATOR . 'opsbridge-macos-linux-agent-' . Str::uuid() . '.zip';
        $zip = new ZipArchive();
        abort_unless($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true, 500, 'Unable to build the agent package.');
        foreach (['opsbridge_agent.py', 'install.sh', 'uninstall.sh', 'README.md'] as $file) {
            $zip->addFile($source . DIRECTORY_SEPARATOR . $file, $file);
        }
        $zip->close();

        return response()->download($zipPath, 'opsbridge-macos-linux-agent.zip')->deleteFileAfterSend(true);
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
        return response(AgentPackageBuilder::unixInstallerScript(), 200, [
            'Content-Type' => 'application/x-sh',
            'Content-Disposition' => 'attachment; filename="OpsBridge-Agent-Installer.command"',
        ]);
    }
}
