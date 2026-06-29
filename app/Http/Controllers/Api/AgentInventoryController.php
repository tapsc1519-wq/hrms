<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\DeviceAgent;
use App\Models\DeviceAgentCredential;
use App\Models\SoftwareDiscovery;
use App\Models\User;
use App\Services\SoftwareRecognitionService;
use App\Services\AgentCommandSigningService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AgentInventoryController extends Controller
{
    public function __construct(
        private readonly SoftwareRecognitionService $recognitionService,
        private readonly AgentCommandSigningService $signingService,
    )
    {
    }

    public function checkIn(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'device_uuid' => 'required|string|max:100',
            'hostname' => 'required|string|max:255',
            'serial_number' => 'nullable|string|max:255',
            'asset_tag' => 'nullable|string|max:255',
            'os_name' => 'nullable|string|max:255',
            'os_version' => 'nullable|string|max:255',
            'architecture' => 'nullable|string|max:50',
            'agent_version' => 'nullable|string|max:50',
            'sync_interval_minutes' => 'nullable|integer|min:15|max:1440',
            'employee_code' => 'nullable|string|max:100',
            'employee_email' => 'nullable|email|max:255',
            'hardware' => 'nullable|array|max:100',
            'network' => 'nullable|array|max:100',
            'security' => 'nullable|array|max:100',
            'user' => 'nullable|array|max:50',
            'snapshot_complete' => 'nullable|boolean',
            'software' => 'nullable|array|max:5000',
            'software.*.raw_name' => 'required|string|max:500',
            'software.*.raw_publisher' => 'nullable|string|max:500',
            'software.*.raw_version' => 'nullable|string|max:255',
            'software.*.raw_edition' => 'nullable|string|max:255',
            'software.*.raw_build_number' => 'nullable|string|max:255',
            'software.*.executable' => 'nullable|string|max:500',
            'software.*.product_code' => 'nullable|string|max:255',
            'software.*.install_path' => 'nullable|string|max:1000',
            'software.*.uninstall_string' => 'nullable|string|max:2000',
            'software.*.install_date' => 'nullable|date',
            'software.*.last_used_date' => 'nullable|date',
            'software.*.usage_count' => 'nullable|integer|min:0',
            'software.*.total_runtime_minutes' => 'nullable|integer|min:0',
        ]);

        $organizationId = (int) $request->attributes->get('agent_organization_id');
        $asset = $this->resolveAsset($organizationId, $validated['asset_tag'] ?? null, $validated['serial_number'] ?? null);
        $tokenUserId = $request->attributes->get('agent_auth_type') === 'enrollment'
            ? $request->attributes->get('agent_token')?->assigned_user_id
            : null;
        $user = $tokenUserId
            ? User::where('organization_id', $organizationId)->whereKey($tokenUserId)->first()
            : $this->resolveUser($organizationId, $validated['employee_code'] ?? null, $validated['employee_email'] ?? null, $asset);
        $inventory = collect($validated['software'] ?? []);

        $result = DB::transaction(function () use ($validated, $organizationId, $asset, $user, $inventory, $request) {
            $agent = DeviceAgent::firstOrNew(['organization_id' => $organizationId, 'device_uuid' => $validated['device_uuid']]);
            $assetId = $asset?->id ?? $agent->asset_id;
            $userId = $user?->id ?? $agent->user_id;
            $agent->fill([
                'asset_id' => $assetId,
                'user_id' => $userId,
                'hostname' => $validated['hostname'],
                'serial_number' => $validated['serial_number'] ?? null,
                'os_name' => $validated['os_name'] ?? null,
                'os_version' => $validated['os_version'] ?? null,
                'architecture' => $validated['architecture'] ?? null,
                'agent_version' => $validated['agent_version'] ?? null,
                'ip_address' => $request->ip(),
                'status' => 'active',
                'enrolled_at' => $agent->enrolled_at ?: now(),
                'last_seen_at' => now(),
                'hardware_info' => $validated['hardware'] ?? null,
                'network_info' => $validated['network'] ?? null,
                'security_info' => $validated['security'] ?? null,
                'user_info' => $validated['user'] ?? null,
                'sync_interval_minutes' => $validated['sync_interval_minutes'] ?? 60,
                'last_error' => null,
                'last_error_at' => null,
            ]);
            if ($inventory->isNotEmpty() || ! empty($validated['snapshot_complete'])) {
                $agent->last_inventory_at = now();
            }
            $agent->save();

            $deviceApiKey = null;
            if ($request->attributes->get('agent_auth_type') === 'device') {
                abort_unless((int) $request->attributes->get('agent_device_id') === $agent->id, 403, 'This device credential cannot report for another device UUID.');
            } else {
                $deviceApiKey = 'ops_device_' . Str::random(64);
                DeviceAgentCredential::updateOrCreate(
                    ['device_agent_id' => $agent->id],
                    [
                        'organization_id' => $organizationId,
                        'key_prefix' => substr($deviceApiKey, 0, 20),
                        'key_hash' => hash('sha256', $deviceApiKey),
                        'issued_at' => now(),
                        'last_used_at' => null,
                        'revoked_at' => null,
                    ]
                );
            }

            $seenIds = [];
            $mapped = 0;
            foreach ($inventory as $item) {
                $match = $this->recognitionService->recognize($organizationId, $item['raw_name'], $item['raw_publisher'] ?? null);
                $discovery = SoftwareDiscovery::firstOrNew([
                    'organization_id' => $organizationId,
                    'device_agent_id' => $agent->id,
                    'raw_name' => $item['raw_name'],
                    'raw_version' => $item['raw_version'] ?? null,
                ]);
                if (! $discovery->exists) {
                    $discovery->first_seen_at = now();
                }
                $discovery->fill([
                    'asset_id' => $assetId,
                    'user_id' => $userId,
                    'software_id' => $match['software_id'],
                    'raw_publisher' => $item['raw_publisher'] ?? null,
                    'raw_edition' => $item['raw_edition'] ?? null,
                    'raw_build_number' => $item['raw_build_number'] ?? null,
                    'executable' => $item['executable'] ?? null,
                    'product_code' => $item['product_code'] ?? null,
                    'install_path' => $item['install_path'] ?? null,
                    'uninstall_string' => $item['uninstall_string'] ?? null,
                    'install_date' => $item['install_date'] ?? null,
                    'last_used_date' => $item['last_used_date'] ?? null,
                    'usage_count' => $item['usage_count'] ?? null,
                    'total_runtime_minutes' => $item['total_runtime_minutes'] ?? null,
                    'source' => 'agent',
                    'status' => $match['software_id'] ? 'mapped' : 'unknown',
                    'confidence_score' => $match['confidence'],
                    'is_installed' => true,
                    'last_seen_at' => now(),
                    'uninstalled_at' => null,
                ])->save();
                $seenIds[] = $discovery->id;
                if ($match['software_id']) $mapped++;
            }

            $removed = 0;
            if (! empty($validated['snapshot_complete'])) {
                $removed = SoftwareDiscovery::where('device_agent_id', $agent->id)
                    ->where('is_installed', true)
                    ->when($seenIds !== [], fn ($query) => $query->whereNotIn('id', $seenIds))
                    ->update(['is_installed' => false, 'uninstalled_at' => now()]);
            }

            return compact('agent', 'mapped', 'removed', 'deviceApiKey') + ['received' => $inventory->count()];
        });

        return response()->json([
            'message' => 'Device inventory accepted.',
            'device_agent_id' => $result['agent']->id,
            'asset_matched' => (bool) $result['agent']->asset_id,
            'employee_matched' => (bool) $result['agent']->user_id,
            'software_received' => $result['received'],
            'software_mapped' => $result['mapped'],
            'software_marked_removed' => $result['removed'],
            'next_check_in_minutes' => 60,
            'command_poll_url' => url('/api/v1/agent/commands'),
            'command_signing_public_key_xml' => $this->signingService->keyFor($organizationId)->public_key_xml,
            'device_api_key' => $result['deviceApiKey'],
        ]);
    }

    private function resolveAsset(int $organizationId, ?string $assetTag, ?string $serialNumber): ?Asset
    {
        if (! $assetTag && ! $serialNumber) return null;

        return Asset::where('organization_id', $organizationId)
            ->where(function ($query) use ($assetTag, $serialNumber) {
                if ($assetTag) $query->orWhere('asset_tag', $assetTag);
                if ($serialNumber) $query->orWhere('serial_number', $serialNumber);
            })->first();
    }

    private function resolveUser(int $organizationId, ?string $employeeCode, ?string $email, ?Asset $asset): ?User
    {
        $user = null;
        if ($employeeCode || $email) {
            $user = User::where('organization_id', $organizationId)
                ->where(function ($query) use ($employeeCode, $email) {
                    if ($employeeCode) $query->orWhere('employee_id', $employeeCode);
                    if ($email) $query->orWhere('email', $email);
                })->first();
        }

        if ($user) return $user;
        return $asset?->activeAssignment()->with('user')->first()?->user;
    }
}
