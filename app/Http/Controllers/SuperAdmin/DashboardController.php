<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\OrganizationProductSubscription;
use App\Models\Partner;
use App\Models\PartnerCommission;
use App\Models\Product;

class DashboardController extends Controller
{
    public function index()
    {
        $subscriptions = OrganizationProductSubscription::with(['organization', 'product', 'partner'])->get();
        $billableSubscriptions = $subscriptions->whereIn('status', ['trial', 'active', 'overdue']);

        $stats = [
            'organizations' => Organization::count(),
            'products' => Product::count(),
            'active_products' => Product::where('status', 'active')->count(),
            'subscriptions' => $subscriptions->count(),
            'active_subscriptions' => $subscriptions->where('status', 'active')->count(),
            'trial_subscriptions' => $subscriptions->where('status', 'trial')->count(),
            'attention_subscriptions' => $subscriptions->whereIn('status', ['overdue', 'suspended'])->count(),
            'monthly_value' => $billableSubscriptions->sum('monthly_amount'),
            'annualized_value' => $billableSubscriptions->sum('monthly_amount') * 12,
            'partners' => Partner::count(),
            'active_partners' => Partner::where('status', 'active')->count(),
            'pending_commissions' => PartnerCommission::where('status', 'pending')->count(),
            'pending_commission_amount' => PartnerCommission::where('status', 'pending')->sum('commission_amount'),
        ];

        $trialsEndingSoon = OrganizationProductSubscription::with(['organization', 'product'])
            ->where('status', 'trial')
            ->whereNotNull('trial_ends_at')
            ->whereDate('trial_ends_at', '>=', now()->toDateString())
            ->whereDate('trial_ends_at', '<=', now()->addDays(7)->toDateString())
            ->orderBy('trial_ends_at')
            ->take(6)
            ->get();

        $recentSubscriptions = OrganizationProductSubscription::with(['organization', 'product', 'partner'])
            ->latest()
            ->take(8)
            ->get();

        $products = Product::orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map(function (Product $product) use ($subscriptions) {
                $productSubscriptions = $subscriptions->where('product_id', $product->id);

                return [
                    'product' => $product,
                    'subscriptions' => $productSubscriptions->count(),
                    'active_subscriptions' => $productSubscriptions->where('status', 'active')->count(),
                    'trial_subscriptions' => $productSubscriptions->where('status', 'trial')->count(),
                    'monthly_value' => $productSubscriptions->whereIn('status', ['trial', 'active', 'overdue'])->sum('monthly_amount'),
                    'launch_url' => $this->productLaunchUrl($product),
                ];
            });

        $subscriptionBreakdown = collect(['trial', 'active', 'overdue', 'suspended', 'cancelled'])
            ->mapWithKeys(fn (string $status) => [ucfirst($status) => $subscriptions->where('status', $status)->count()]);

        $recentCommissions = PartnerCommission::with(['partner', 'product', 'organization'])
            ->latest()
            ->take(6)
            ->get();

        $organizationHealth = [
            'trial' => Organization::where('billing_status', 'trial')->count(),
            'active' => Organization::where('billing_status', 'active')->count(),
            'attention' => Organization::whereIn('billing_status', ['overdue', 'suspended'])->count(),
            'cancelled' => Organization::where('billing_status', 'cancelled')->count(),
        ];

        $platformChecklist = collect([
            [
                'title' => 'Create product catalog',
                'description' => 'Each Niyantron product should have its own name, domain and subscription plan.',
                'complete' => $stats['products'] > 0,
                'route' => route('super-admin.products.index'),
                'action' => 'Products',
                'icon' => 'bi-grid-3x3-gap-fill',
            ],
            [
                'title' => 'Onboard organizations',
                'description' => 'Organizations must be mapped to the products they purchased.',
                'complete' => $stats['organizations'] > 0,
                'route' => route('super-admin.organizations.index'),
                'action' => 'Organizations',
                'icon' => 'bi-building',
            ],
            [
                'title' => 'Activate subscriptions',
                'description' => 'Keep trial, active, overdue and suspended product access clear.',
                'complete' => $stats['active_subscriptions'] > 0,
                'route' => route('super-admin.product-subscriptions.index'),
                'action' => 'Subscriptions',
                'icon' => 'bi-ui-checks-grid',
            ],
            [
                'title' => 'Build partner channel',
                'description' => 'Partners and commissions will be common for OpsBridge and future ERP.',
                'complete' => $stats['partners'] > 0,
                'route' => route('super-admin.partners.index'),
                'action' => 'Partners',
                'icon' => 'bi-person-workspace',
            ],
            [
                'title' => 'Clear attention items',
                'description' => 'Resolve overdue, suspended subscriptions and pending commission payouts.',
                'complete' => ($stats['attention_subscriptions'] + $stats['pending_commissions']) === 0,
                'route' => route('super-admin.dashboard'),
                'action' => 'Review',
                'icon' => 'bi-lightning-charge-fill',
            ],
        ]);

        $platformProgress = [
            'total' => $platformChecklist->count(),
            'complete' => $platformChecklist->where('complete', true)->count(),
        ];

        return view('super-admin.dashboard', compact(
            'stats',
            'trialsEndingSoon',
            'recentSubscriptions',
            'products',
            'subscriptionBreakdown',
            'recentCommissions',
            'organizationHealth',
            'platformChecklist',
            'platformProgress'
        ));
    }

    private function productLaunchUrl(Product $product): ?string
    {
        if (blank($product->domain)) {
            return null;
        }

        $scheme = parse_url((string) config('app.url'), PHP_URL_SCHEME) ?: 'https';

        return $scheme . '://' . $product->domain;
    }
}
