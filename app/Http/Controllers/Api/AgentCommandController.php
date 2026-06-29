<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AgentCommand;
use App\Models\DeviceAgent;
use App\Models\SoftwareComplianceAction;
use App\Models\SoftwareDiscovery;
use App\Services\AgentCommandSigningService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AgentCommandController extends Controller
{
    public function __construct(private readonly AgentCommandSigningService $signingService) {}

    public function poll(Request $request): JsonResponse
    {
        abort_unless($request->attributes->get('agent_auth_type') === 'device', 403, 'A device-specific API key is required for command polling.');
        $validated = $request->validate(['device_uuid' => 'required|string|max:100']);
        $organizationId = (int) $request->attributes->get('agent_organization_id');
        $device = DeviceAgent::where('organization_id', $organizationId)->where('device_uuid', $validated['device_uuid'])->firstOrFail();
        abort_unless((int) $request->attributes->get('agent_device_id') === $device->id, 403, 'This API key is not assigned to the requested device.');

        AgentCommand::where('device_agent_id', $device->id)
            ->whereIn('status', ['queued', 'delivered'])->where('expires_at', '<', now())
            ->update(['status' => 'expired', 'error_message' => 'Command expired before completion.']);

        $commands = AgentCommand::where('device_agent_id', $device->id)
            ->whereIn('status', ['queued', 'delivered'])
            ->where(fn ($query) => $query->whereNull('available_at')->orWhere('available_at', '<=', now()))
            ->where(fn ($query) => $query->whereNull('expires_at')->orWhere('expires_at', '>', now()))
            ->with('deviceAgent')->orderByDesc('priority')->oldest()->limit(10)->get();

        $payload = $commands->map(fn ($command) => $this->signingService->signedPayload($command))->values();
        AgentCommand::whereIn('id', $commands->pluck('id'))->update(['status' => 'delivered', 'delivered_at' => now()]);

        return response()->json(['commands' => $payload, 'poll_after_minutes' => 5]);
    }

    public function result(Request $request, string $commandUuid): JsonResponse
    {
        abort_unless($request->attributes->get('agent_auth_type') === 'device', 403, 'A device-specific API key is required for command results.');
        $validated = $request->validate([
            'device_uuid' => 'required|string|max:100',
            'status' => 'required|in:completed,failed',
            'result' => 'nullable|array|max:100',
            'error_message' => 'nullable|string|max:2000',
        ]);
        $organizationId = (int) $request->attributes->get('agent_organization_id');
        abort_unless((int) $request->attributes->get('agent_device_id') > 0, 403);
        $command = AgentCommand::where('organization_id', $organizationId)
            ->where('command_uuid', $commandUuid)
            ->whereHas('deviceAgent', fn ($query) => $query->where('device_uuid', $validated['device_uuid']))
            ->where('device_agent_id', (int) $request->attributes->get('agent_device_id'))
            ->firstOrFail();

        if (in_array($command->status, ['completed', 'failed', 'cancelled', 'expired'], true)) {
            return response()->json(['message' => 'Command result already recorded.']);
        }

        $command->update([
            'status' => $validated['status'],
            'result' => $validated['result'] ?? null,
            'error_message' => $validated['error_message'] ?? null,
            'executed_at' => now(),
        ]);

        $this->syncSamRemediation($command->fresh(), $validated['status'], $validated['error_message'] ?? null);

        return response()->json(['message' => 'Command result recorded.']);
    }

    private function syncSamRemediation(AgentCommand $command, string $status, ?string $errorMessage): void
    {
        if ($command->command_type !== 'software_uninstall') {
            return;
        }

        $payload = $command->payload ?? [];
        $actionId = $payload['compliance_action_id'] ?? null;
        if (! $actionId) {
            return;
        }

        $action = SoftwareComplianceAction::where('organization_id', $command->organization_id)
            ->whereKey($actionId)
            ->where('action_type', 'uninstall_reclaim')
            ->first();
        if (! $action || $action->status !== 'open') {
            return;
        }

        $note = trim((string) $action->notes);

        if ($status === 'completed') {
            $action->update([
                'status' => 'completed',
                'completed_at' => now(),
                'notes' => trim($note."\nEndpoint uninstall command completed on ".now()->format('Y-m-d H:i').'.'),
            ]);

            if (! empty($payload['discovery_id'])) {
                SoftwareDiscovery::where('organization_id', $command->organization_id)
                    ->whereKey($payload['discovery_id'])
                    ->update([
                        'is_installed' => false,
                        'uninstalled_at' => now(),
                        'reviewed_at' => now(),
                    ]);
            }

            return;
        }

        $message = $errorMessage ?: 'Endpoint uninstall command failed.';
        $action->update([
            'notes' => trim($note."\nEndpoint uninstall command failed on ".now()->format('Y-m-d H:i').': '.$message),
        ]);
    }
}
