<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\OrganizationProductSubscription;
use App\Models\Product;
use App\Support\ModuleRegistry;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

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
        return view('super-admin.organizations.create');
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
        ]);

        $validated['slug'] = Str::slug($validated['name']) . '-' . Str::random(4);

        if ($request->hasFile('logo')) {
            $validated['logo'] = $request->file('logo')->store('logos', 'public');
        }

        $organization = Organization::create($validated);
        $organization->forceFill([
            'trial_months' => 1,
            'trial_started_at' => now(),
            'trial_ends_at' => now()->addMonth()->toDateString(),
            'billing_status' => 'trial',
            'billing_cycle' => 'monthly',
        ])->save();
        $organization->syncModules(ModuleRegistry::keys(), auth()->id());
        $this->syncOpsBridgeSubscription($organization);

        return redirect()->route('super-admin.organizations.index')
            ->with('success', 'Organization created successfully.');
    }

    public function show(Organization $organization)
    {
        $organization->load(['users', 'assets', 'suppliers', 'modules', 'payments.recorder']);
        $modules = ModuleRegistry::all();

        return view('super-admin.organizations.show', compact('organization', 'modules'));
    }

    public function edit(Organization $organization)
    {
        return view('super-admin.organizations.edit', compact('organization'));
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
        ]);

        if ($request->hasFile('logo')) {
            $validated['logo'] = $request->file('logo')->store('logos', 'public');
        }

        $organization->update($validated);

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

        $organization->payments()->create([
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

        return back()->with('success', 'Payment recorded and subscription activated.');
    }

    public function destroy(Organization $organization)
    {
        $organization->delete();
        return redirect()->route('super-admin.organizations.index')
            ->with('success', 'Organization deleted successfully.');
    }

    private function syncOpsBridgeSubscription(Organization $organization): void
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
            [
                'status' => $organization->billing_status ?: 'trial',
                'plan_name' => 'OpsBridge',
                'billing_cycle' => $organization->billing_cycle ?: 'monthly',
                'monthly_amount' => $organization->monthly_amount ?: $this->registeredOpsBridgeAmount(),
                'trial_started_at' => $organization->trial_started_at,
                'trial_ends_at' => $organization->trial_ends_at,
                'subscription_started_at' => $organization->last_payment_at,
                'subscription_ends_at' => $organization->subscription_ends_at,
                'last_payment_at' => $organization->last_payment_at,
                'product_database' => config('database.connections.' . config('database.product_connection', 'opsbridge') . '.database'),
                'product_domain' => $product->domain ?: 'opsbridge.niyantron.com',
            ]
        );
    }

    private function registeredOpsBridgeAmount(): float
    {
        return collect(ModuleRegistry::keys())
            ->sum(fn (string $key) => ModuleRegistry::monthlyPrice($key));
    }
}
