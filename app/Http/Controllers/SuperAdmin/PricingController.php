<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\Setting;
use App\Support\ModuleRegistry;
use Illuminate\Http\Request;

class PricingController extends Controller
{
    public function edit()
    {
        $modules = collect(ModuleRegistry::all())->map(function (array $module, string $key) {
            $defaultPrice = (float) ($module['monthly_price'] ?? 0);

            return [
                'key' => $key,
                'name' => $module['name'],
                'short_name' => $module['short_name'],
                'description' => $module['description'],
                'icon' => $module['icon'],
                'default_price' => $defaultPrice,
                'price' => ModuleRegistry::monthlyPrice($key),
            ];
        });

        $activeOrganizations = Organization::whereIn('billing_status', ['trial', 'active', 'overdue'])->count();
        $currentMonthlyRevenue = Organization::whereIn('billing_status', ['trial', 'active', 'overdue'])->sum('monthly_amount');

        return view('super-admin.pricing.edit', compact('modules', 'activeOrganizations', 'currentMonthlyRevenue'));
    }

    public function update(Request $request)
    {
        $rules = [];
        foreach (ModuleRegistry::keys() as $key) {
            $rules["prices.{$key}"] = ['required', 'numeric', 'min:0', 'max:9999999'];
        }

        $validated = $request->validate($rules);

        foreach ($validated['prices'] as $key => $price) {
            Setting::set("module_price_{$key}", round((float) $price, 2));
        }

        if ($request->boolean('apply_to_existing')) {
            $this->applyPricesToExistingOrganizations();
        }

        return back()->with('success', 'Module pricing updated successfully.');
    }

    private function applyPricesToExistingOrganizations(): void
    {
        $prices = collect(ModuleRegistry::keys())
            ->mapWithKeys(fn (string $key) => [$key => ModuleRegistry::monthlyPrice($key)]);

        Organization::with('modules')->chunk(50, function ($organizations) use ($prices) {
            foreach ($organizations as $organization) {
                foreach ($organization->modules as $module) {
                    $module->update([
                        'monthly_price' => $prices[$module->module_key] ?? 0,
                    ]);
                }

                $organization->refreshMonthlyAmount();
            }
        });
    }
}
