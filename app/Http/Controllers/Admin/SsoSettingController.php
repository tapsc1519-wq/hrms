<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\OrganizationSsoSetting;
use Illuminate\Http\Request;

class SsoSettingController extends Controller
{
    public function edit()
    {
        $settings = collect(['microsoft', 'google'])
            ->mapWithKeys(fn($provider) => [
                $provider => OrganizationSsoSetting::firstOrNew([
                    'organization_id' => $this->orgId(),
                    'provider' => $provider,
                ]),
            ]);

        return view('admin.sso-settings.edit', [
            'settings' => $settings,
            'callbackUrl' => route('sso.callback'),
        ]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'provider' => ['required', 'in:microsoft,google'],
            'is_enabled' => ['nullable', 'boolean'],
            'client_id' => ['nullable', 'string', 'max:255'],
            'client_secret' => ['nullable', 'string', 'max:2000'],
            'tenant' => ['nullable', 'string', 'max:255'],
            'allowed_domains' => ['nullable', 'string', 'max:2000'],
        ]);

        $setting = OrganizationSsoSetting::firstOrNew([
            'organization_id' => $this->orgId(),
            'provider' => $validated['provider'],
        ]);

        $setting->fill([
            'is_enabled' => $request->boolean('is_enabled'),
            'client_id' => $validated['client_id'] ?? null,
            'tenant' => $validated['provider'] === 'microsoft' ? ($validated['tenant'] ?: 'common') : null,
            'allowed_domains' => $this->normalizeDomains($validated['allowed_domains'] ?? ''),
        ]);

        if (filled($validated['client_secret'] ?? null)) {
            $setting->client_secret = $validated['client_secret'];
        } elseif (!$setting->exists) {
            $setting->client_secret = null;
        }

        $setting->save();

        return back()->with('success', ucfirst($validated['provider']) . ' SSO settings saved.');
    }

    private function normalizeDomains(string $domains): array
    {
        return collect(preg_split('/[\r\n,]+/', $domains) ?: [])
            ->map(fn($domain) => strtolower(trim($domain)))
            ->map(fn($domain) => str_starts_with($domain, '@') ? substr($domain, 1) : $domain)
            ->filter()
            ->unique()
            ->values()
            ->all();
    }
}
