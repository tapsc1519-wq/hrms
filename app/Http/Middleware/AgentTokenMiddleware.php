<?php

namespace App\Http\Middleware;

use App\Models\AgentApiToken;
use App\Models\DeviceAgentCredential;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AgentTokenMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $plainToken = $request->bearerToken();
        if (! $plainToken) {
            return response()->json(['message' => 'A device-agent bearer token is required.'], 401);
        }

        $hash = hash('sha256', $plainToken);
        $token = AgentApiToken::where('token_hash', $hash)->first();
        $credential = $token ? null : DeviceAgentCredential::where('key_hash', $hash)->first();
        if ((! $token || ! $token->is_active) && (! $credential || ! $credential->is_active)) {
            return response()->json(['message' => 'The device-agent token is invalid, expired, or revoked.'], 401);
        }

        $identity = $token ?: $credential;
        if (! $identity->last_used_at || $identity->last_used_at->lt(now()->subMinutes(5))) {
            $identity->forceFill(['last_used_at' => now()])->save();
        }

        $request->attributes->set('agent_auth_type', $token ? 'enrollment' : 'device');
        $request->attributes->set('agent_token', $identity);
        $request->attributes->set('agent_organization_id', $identity->organization_id);
        $request->attributes->set('agent_device_id', $credential?->device_agent_id);

        return $next($request);
    }
}
