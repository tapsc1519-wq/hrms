<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\OrganizationProductSubscription;
use App\Models\Partner;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductSubscriptionController extends Controller
{
    public function index(Request $request)
    {
        $query = OrganizationProductSubscription::with(['organization', 'product', 'partner'])
            ->latest();

        if ($request->filled('product_id')) {
            $query->where('product_id', $request->product_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('partner_id')) {
            $query->where('partner_id', $request->partner_id);
        }

        if ($request->filled('search')) {
            $search = trim((string) $request->search);
            $organizationIds = Organization::where('name', 'like', '%' . $search . '%')
                ->orWhere('email', 'like', '%' . $search . '%')
                ->orWhere('slug', 'like', '%' . $search . '%')
                ->pluck('id');

            $query->whereIn('organization_id', $organizationIds);
        }

        $summaryQuery = clone $query;
        $subscriptions = $query->paginate(20)->withQueryString();
        $products = Product::orderBy('sort_order')->orderBy('name')->get(['id', 'name']);
        $partners = Partner::where('status', 'active')->orderBy('name')->get(['id', 'name', 'company_name', 'default_commission_percent']);
        $statuses = $this->statuses();

        $totalSubscriptions = (clone $summaryQuery)->count();
        $activeSubscriptions = (clone $summaryQuery)->where('status', 'active')->count();
        $trialSubscriptions = (clone $summaryQuery)->where('status', 'trial')->count();
        $monthlyValue = (float) (clone $summaryQuery)
            ->whereIn('status', ['trial', 'active', 'overdue'])
            ->sum('monthly_amount');

        return view('super-admin.product-subscriptions.index', compact(
            'subscriptions',
            'products',
            'partners',
            'statuses',
            'totalSubscriptions',
            'activeSubscriptions',
            'trialSubscriptions',
            'monthlyValue'
        ));
    }

    public function edit(OrganizationProductSubscription $productSubscription)
    {
        $productSubscription->load(['organization', 'product', 'partner']);
        $statuses = $this->statuses();
        $billingCycles = $this->billingCycles();
        $partners = Partner::where('status', 'active')->orderBy('name')->get(['id', 'name', 'company_name', 'default_commission_percent']);

        return view('super-admin.product-subscriptions.edit', compact(
            'productSubscription',
            'statuses',
            'billingCycles',
            'partners'
        ));
    }

    public function update(Request $request, OrganizationProductSubscription $productSubscription)
    {
        $validated = $request->validate([
            'status' => ['required', 'in:' . implode(',', array_keys($this->statuses()))],
            'partner_id' => ['nullable', 'exists:partners,id'],
            'plan_name' => ['nullable', 'string', 'max:120'],
            'billing_cycle' => ['required', 'in:' . implode(',', array_keys($this->billingCycles()))],
            'monthly_amount' => ['required', 'numeric', 'min:0', 'max:99999999'],
            'commission_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'trial_started_at' => ['nullable', 'date'],
            'trial_ends_at' => ['nullable', 'date'],
            'subscription_started_at' => ['nullable', 'date'],
            'subscription_ends_at' => ['nullable', 'date'],
            'last_payment_at' => ['nullable', 'date'],
            'product_database' => ['nullable', 'string', 'max:120'],
            'product_domain' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $productSubscription->update($validated);
        $this->syncLegacyOrganizationBilling($productSubscription);

        return redirect()
            ->route('super-admin.product-subscriptions.index')
            ->with('success', 'Product subscription updated successfully.');
    }

    private function statuses(): array
    {
        return [
            'trial' => 'Trial',
            'active' => 'Active',
            'overdue' => 'Overdue',
            'suspended' => 'Suspended',
            'cancelled' => 'Cancelled',
        ];
    }

    private function billingCycles(): array
    {
        return [
            'monthly' => 'Monthly',
            'annual' => 'Annual',
        ];
    }

    private function syncLegacyOrganizationBilling(OrganizationProductSubscription $subscription): void
    {
        if ($subscription->product?->slug !== 'opsbridge') {
            return;
        }

        $organization = Organization::find($subscription->organization_id);

        if (!$organization) {
            return;
        }

        $organization->forceFill([
            'billing_status' => $subscription->status,
            'billing_cycle' => $subscription->billing_cycle,
            'monthly_amount' => $subscription->monthly_amount,
            'trial_started_at' => $subscription->trial_started_at,
            'trial_ends_at' => $subscription->trial_ends_at,
            'subscription_ends_at' => $subscription->subscription_ends_at,
            'last_payment_at' => $subscription->last_payment_at,
        ])->save();
    }
}
