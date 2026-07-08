<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\OrganizationProductSubscription;
use App\Models\Partner;
use App\Models\PartnerCommission;
use App\Models\Product;
use App\Support\ModuleRegistry;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class OrganizationController extends Controller
{
    public function index(Request $request)
    {
        $query = Organization::withCount(['users', 'assets', 'suppliers'])->with('modules');

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('email', 'like', '%' . $request->search . '%');
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('billing_status')) {
            $query->where('billing_status', $request->billing_status);
        }

        if ($request->filled('trial')) {
            if ($request->trial === 'ending_soon') {
                $query->where('billing_status', 'trial')
                    ->whereNotNull('trial_ends_at')
                    ->whereDate('trial_ends_at', '>=', now()->toDateString())
                    ->whereDate('trial_ends_at', '<=', now()->addDays(7)->toDateString());
            }

            if ($request->trial === 'expired') {
                $query->where('billing_status', 'trial')
                    ->whereNotNull('trial_ends_at')
                    ->whereDate('trial_ends_at', '<', now()->toDateString());
            }
        }

        $organizations = $query->latest()->paginate(15)->withQueryString();

        return view('super-admin.organizations.index', compact('organizations'));
    }

    public function create()
    {
        return view('super-admin.organizations.create', [
            'opsBridgeProduct' => $this->opsBridgeProduct(),
            'partners' => $this->activePartners(),
            'subscriptionStatuses' => $this->subscriptionStatuses(),
            'billingCycles' => $this->billingCycles(),
            'defaultMonthlyAmount' => $this->registeredOpsBridgeAmount(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'       => 'required|string|max:255',
            'email'      => 'nullable|email|max:255',
            'phone'      => 'nullable|string|max:50',
            'address'    => 'nullable|string',
            'city'       => 'nullable|string|max:100',
            'country'    => 'nullable|string|max:100',
            'website'    => 'nullable|url|max:255',
            'tax_number' => 'nullable|string|max:100',
            'status'     => 'required|in:active,inactive,suspended',
            'logo'       => 'nullable|image|max:2048',
            ...$this->subscriptionValidationRules(),
        ]);

        $validated['slug'] = Str::slug($validated['name']) . '-' . Str::random(4);

        if ($request->hasFile('logo')) {
            $validated['logo'] = $request->file('logo')->store('logos', 'public');
        }

        $organization = Organization::create(collect($validated)->except($this->subscriptionInputKeys())->all());
        $organization->forceFill([
            'trial_months' => (int) ($validated['trial_months'] ?? 1),
            'trial_started_at' => now(),
            'trial_ends_at' => now()->addMonths((int) ($validated['trial_months'] ?? 1))->toDateString(),
            'billing_status' => $validated['subscription_status'] ?? 'trial',
            'billing_cycle' => $validated['billing_cycle'] ?? 'monthly',
        ])->save();

        $provisionOpsBridge = $request->boolean('provision_opsbridge', true);
        $organization->syncModules($provisionOpsBridge ? ModuleRegistry::keys() : [], auth()->id());

        if ($provisionOpsBridge) {
            $this->syncOpsBridgeSubscription($organization, $validated);
        }

        return redirect()->route('super-admin.organizations.index')
            ->with('success', 'Organization created successfully.');
    }

    public function show(Organization $organization)
    {
        $organization->load(['users', 'assets', 'suppliers', 'modules', 'payments.recorder', 'productSubscriptions.product', 'productSubscriptions.partner']);
        $modules = ModuleRegistry::all();

        return view('super-admin.organizations.show', compact('organization', 'modules'));
    }

    public function edit(Organization $organization)
    {
        $organization->load(['modules', 'productSubscriptions.product', 'productSubscriptions.partner']);

        return view('super-admin.organizations.edit', [
            'organization' => $organization,
            'opsBridgeProduct' => $this->opsBridgeProduct(),
            'partners' => $this->activePartners(),
            'subscriptionStatuses' => $this->subscriptionStatuses(),
            'billingCycles' => $this->billingCycles(),
            'defaultMonthlyAmount' => $this->registeredOpsBridgeAmount(),
            'opsBridgeSubscription' => $organization->productSubscriptions
                ->first(fn ($subscription) => $subscription->product?->slug === 'opsbridge'),
        ]);
    }

    public function update(Request $request, Organization $organization)
    {
        $validated = $request->validate([
            'name'       => 'required|string|max:255',
            'email'      => 'nullable|email|max:255',
            'phone'      => 'nullable|string|max:50',
            'address'    => 'nullable|string',
            'city'       => 'nullable|string|max:100',
            'country'    => 'nullable|string|max:100',
            'website'    => 'nullable|url|max:255',
            'tax_number' => 'nullable|string|max:100',
            'status'     => 'required|in:active,inactive,suspended',
            'logo'       => 'nullable|image|max:2048',
            ...$this->subscriptionValidationRules(false),
        ]);

        if ($request->hasFile('logo')) {
            $validated['logo'] = $request->file('logo')->store('logos', 'public');
        }

        $organization->update(collect($validated)->except($this->subscriptionInputKeys())->all());

        if ($request->boolean('provision_opsbridge', true)) {
            $trialStartedAt = $organization->trial_started_at ?: now();
            $organization->forceFill([
                'trial_months' => (int) ($validated['trial_months'] ?? $organization->trial_months ?: 1),
                'trial_started_at' => $trialStartedAt,
                'trial_ends_at' => $trialStartedAt->copy()->addMonths((int) ($validated['trial_months'] ?? $organization->trial_months ?: 1))->toDateString(),
                'billing_status' => $validated['subscription_status'] ?? $organization->billing_status,
                'billing_cycle' => $validated['billing_cycle'] ?? $organization->billing_cycle,
                'monthly_amount' => $validated['monthly_amount'] ?? $organization->monthly_amount,
                'subscription_ends_at' => $validated['subscription_ends_at'] ?? $organization->subscription_ends_at,
            ])->save();

            $this->syncOpsBridgeSubscription($organization, $validated);
        } else {
            $this->cancelOpsBridgeSubscription($organization);
            $organization->syncModules([], auth()->id());
        }

        return redirect()->route('super-admin.organizations.index')
            ->with('success', 'Organization updated successfully.');
    }

    public function updateModules(Request $request, Organization $organization)
    {
        $validated = $request->validate([
            'modules' => ['nullable', 'array'],
            'modules.*' => ['string', 'in:' . implode(',', ModuleRegistry::keys())],
            'trial_months' => ['required', 'integer', 'between:1,6'],
            'billing_status' => ['required', 'string', 'in:trial,active,overdue,suspended,cancelled'],
            'billing_cycle' => ['required', 'string', 'in:monthly,annual'],
        ]);

        $trialStartedAt = $organization->trial_started_at ?: now();
        $organization->forceFill([
            'trial_months' => $validated['trial_months'],
            'trial_started_at' => $trialStartedAt,
            'trial_ends_at' => $trialStartedAt->copy()->addMonths((int) $validated['trial_months'])->toDateString(),
            'billing_status' => $validated['billing_status'],
            'billing_cycle' => $validated['billing_cycle'],
        ])->save();

        $organization->syncModules($validated['modules'] ?? [], auth()->id());
        $this->syncOpsBridgeSubscription($organization);

        return back()->with('success', 'Organization module access and pricing updated.');
    }

    public function recordPayment(Request $request, Organization $organization)
    {
        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:1', 'max:99999999'],
            'payment_date' => ['required', 'date'],
            'period_start' => ['required', 'date'],
            'period_end' => ['required', 'date', 'after_or_equal:period_start'],
            'payment_method' => ['required', 'in:bank_transfer,upi,cheque,cash,card,other'],
            'reference_no' => ['nullable', 'string', 'max:120'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $payment = $organization->payments()->create([
            ...$validated,
            'recorded_by' => auth()->id(),
        ]);

        $organization->forceFill([
            'billing_status' => 'active',
            'status' => 'active',
            'last_payment_at' => $validated['payment_date'],
            'subscription_ends_at' => $validated['period_end'],
        ])->save();
        $this->syncOpsBridgeSubscription($organization);
        $this->createPartnerCommission($organization, $payment);

        return back()->with('success', 'Payment recorded and subscription activated.');
    }

    public function destroy(Organization $organization)
    {
        $organization->delete();
        return redirect()->route('super-admin.organizations.index')
            ->with('success', 'Organization deleted successfully.');
    }

    private function syncOpsBridgeSubscription(Organization $organization, array $subscriptionData = []): void
    {
        $product = Product::where('slug', 'opsbridge')->first();

        if (!$product) {
            return;
        }

        OrganizationProductSubscription::updateOrCreate(
            [
                'organization_id' => $organization->id,
                'product_id' => $product->id,
            ],
            array_filter([
                'partner_id' => $subscriptionData['partner_id'] ?? null,
                'status' => $organization->billing_status ?: 'trial',
                'plan_name' => $subscriptionData['plan_name'] ?? 'OpsBridge',
                'billing_cycle' => $organization->billing_cycle ?: 'monthly',
                'monthly_amount' => $subscriptionData['monthly_amount'] ?? $organization->monthly_amount ?: $this->registeredOpsBridgeAmount(),
                'commission_percent' => $subscriptionData['commission_percent'] ?? null,
                'trial_started_at' => $organization->trial_started_at,
                'trial_ends_at' => $organization->trial_ends_at,
                'subscription_started_at' => $organization->last_payment_at,
                'subscription_ends_at' => $subscriptionData['subscription_ends_at'] ?? $organization->subscription_ends_at,
                'last_payment_at' => $organization->last_payment_at,
                'product_database' => $subscriptionData['product_database'] ?? config('database.connections.' . config('database.product_connection', 'opsbridge') . '.database'),
                'product_domain' => $subscriptionData['product_domain'] ?? ($product->domain ?: 'opsbridge.niyantron.com'),
                'notes' => $subscriptionData['subscription_notes'] ?? null,
            ], fn ($value) => $value !== null)
        );
    }

    private function cancelOpsBridgeSubscription(Organization $organization): void
    {
        $product = Product::where('slug', 'opsbridge')->first();

        if (!$product) {
            return;
        }

        OrganizationProductSubscription::where('organization_id', $organization->id)
            ->where('product_id', $product->id)
            ->update([
                'status' => 'cancelled',
                'notes' => 'OpsBridge access disabled from organization provisioning.',
            ]);

        $organization->forceFill([
            'billing_status' => 'cancelled',
        ])->save();
    }

    private function opsBridgeProduct(): ?Product
    {
        return Product::where('slug', 'opsbridge')->first();
    }

    private function activePartners()
    {
        return Partner::where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name', 'company_name', 'default_commission_percent']);
    }

    private function subscriptionStatuses(): array
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

    private function subscriptionValidationRules(bool $required = true): array
    {
        $requiredWhenProvisioned = $required ? 'required_if:provision_opsbridge,1' : 'nullable';

        return [
            'provision_opsbridge' => ['nullable', 'boolean'],
            'subscription_status' => [$requiredWhenProvisioned, 'in:' . implode(',', array_keys($this->subscriptionStatuses()))],
            'plan_name' => ['nullable', 'string', 'max:120'],
            'billing_cycle' => [$requiredWhenProvisioned, 'in:' . implode(',', array_keys($this->billingCycles()))],
            'trial_months' => [$requiredWhenProvisioned, 'integer', 'between:1,12'],
            'monthly_amount' => [$requiredWhenProvisioned, 'numeric', 'min:0', 'max:99999999'],
            'partner_id' => ['nullable', Rule::exists((new Partner())->getConnectionName() . '.partners', 'id')],
            'commission_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'subscription_ends_at' => ['nullable', 'date'],
            'product_domain' => ['nullable', 'string', 'max:255'],
            'product_database' => ['nullable', 'string', 'max:120'],
            'subscription_notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    private function subscriptionInputKeys(): array
    {
        return [
            'provision_opsbridge',
            'subscription_status',
            'plan_name',
            'billing_cycle',
            'trial_months',
            'monthly_amount',
            'partner_id',
            'commission_percent',
            'subscription_ends_at',
            'product_domain',
            'product_database',
            'subscription_notes',
        ];
    }

    private function registeredOpsBridgeAmount(): float
    {
        return collect(ModuleRegistry::keys())
            ->sum(fn (string $key) => ModuleRegistry::monthlyPrice($key));
    }

    private function createPartnerCommission(Organization $organization, $payment): void
    {
        $subscription = OrganizationProductSubscription::with(['partner', 'product'])
            ->where('organization_id', $organization->id)
            ->whereNotNull('partner_id')
            ->orderByDesc('id')
            ->first();

        if (!$subscription?->partner || !$subscription->product) {
            return;
        }

        $commissionPercent = (float) ($subscription->commission_percent ?? $subscription->partner->default_commission_percent ?? 0);

        if ($commissionPercent <= 0) {
            return;
        }

        $paymentAmount = (float) $payment->amount;
        $commissionAmount = round(($paymentAmount * $commissionPercent) / 100, 2);

        PartnerCommission::updateOrCreate(
            ['organization_payment_id' => $payment->id],
            [
                'partner_id' => $subscription->partner_id,
                'product_id' => $subscription->product_id,
                'organization_id' => $organization->id,
                'organization_product_subscription_id' => $subscription->id,
                'payment_amount' => $paymentAmount,
                'commission_percent' => $commissionPercent,
                'commission_amount' => $commissionAmount,
                'payment_date' => $payment->payment_date,
                'period_start' => $payment->period_start,
                'period_end' => $payment->period_end,
                'status' => 'pending',
            ]
        );
    }
}
