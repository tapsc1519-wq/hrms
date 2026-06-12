<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\Organization;
use App\Models\OrganizationModule;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\User;
use App\Support\ModuleRegistry;

class DashboardController extends Controller
{
    public function index()
    {
        $organizations = Organization::with('modules')->get();
        $activeOrganizations = $organizations->where('status', 'active');
        $activeBillingOrganizations = $organizations->whereIn('billing_status', ['trial', 'active', 'overdue']);

        $stats = [
            'organizations' => $organizations->count(),
            'active_orgs' => $activeOrganizations->count(),
            'trial_orgs' => $organizations->where('billing_status', 'trial')->count(),
            'overdue_orgs' => $organizations->where('billing_status', 'overdue')->count(),
            'suspended_orgs' => $organizations->where('billing_status', 'suspended')->count(),
            'monthly_revenue' => $activeBillingOrganizations->sum('monthly_amount'),
            'annualized_revenue' => $activeBillingOrganizations->sum('monthly_amount') * 12,
            'users' => User::count(),
            'assets' => Asset::count(),
            'suppliers' => Supplier::count(),
            'pending_pos' => PurchaseOrder::whereIn('status', ['draft', 'sent'])->count(),
        ];

        $trialsEndingSoon = Organization::where('billing_status', 'trial')
            ->whereNotNull('trial_ends_at')
            ->whereDate('trial_ends_at', '>=', now()->toDateString())
            ->whereDate('trial_ends_at', '<=', now()->addDays(7)->toDateString())
            ->orderBy('trial_ends_at')
            ->take(6)
            ->get();

        $recentOrgs = Organization::with('modules')
            ->latest()
            ->take(7)
            ->get();

        $moduleCounts = OrganizationModule::selectRaw('module_key, COUNT(*) as count')
            ->where('is_enabled', true)
            ->groupBy('module_key')
            ->pluck('count', 'module_key');

        $moduleRevenue = OrganizationModule::selectRaw('module_key, SUM(monthly_price) as revenue')
            ->where('is_enabled', true)
            ->groupBy('module_key')
            ->pluck('revenue', 'module_key');

        $moduleOverview = collect(ModuleRegistry::all())->map(function (array $module, string $key) use ($moduleCounts, $moduleRevenue) {
            return [
                'key' => $key,
                'name' => $module['short_name'],
                'full_name' => $module['name'],
                'icon' => $module['icon'],
                'color' => $module['color'],
                'price' => $module['monthly_price'] ?? 0,
                'enabled_count' => (int) ($moduleCounts[$key] ?? 0),
                'monthly_revenue' => (float) ($moduleRevenue[$key] ?? 0),
            ];
        })->values();

        $billingBreakdown = [
            'Trial' => $organizations->where('billing_status', 'trial')->count(),
            'Active' => $organizations->where('billing_status', 'active')->count(),
            'Overdue' => $organizations->where('billing_status', 'overdue')->count(),
            'Suspended' => $organizations->where('billing_status', 'suspended')->count(),
            'Cancelled' => $organizations->where('billing_status', 'cancelled')->count(),
        ];

        return view('super-admin.dashboard', compact(
            'stats',
            'trialsEndingSoon',
            'recentOrgs',
            'moduleOverview',
            'billingBreakdown'
        ));
    }
}
