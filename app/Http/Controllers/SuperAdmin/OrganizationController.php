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

        return redirect()->route('super-admin.organizations.edit', $organization)
            ->with('success', 'Organization created. Continue by creating the first customer admin and completing handover.');
    }

    public function show(Organization $organization)
    {
        $organization->load(['users', 'assets', 'suppliers', 'modules', 'payments.recorder', 'productSubscriptions.product', 'productSubscriptions.partner']);
        $modules = ModuleRegistry::all();
        $onboardingChecklist = $this->onboardingChecklist($organization);

        return view('super-admin.organizations.show', compact('organization', 'modules', 'onboardingChecklist'));
    }

    public function edit(Organization $organization)
    {
        $organization->load(['users', 'modules', 'productSubscriptions.product', 'productSubscriptions.partner']);

        return view('super-admin.organizations.edit', [
            'organization' => $organization,
            'opsBridgeProduct' => $this->opsBridgeProduct(),
            'partners' => $this->activePartners(),
            'subscriptionStatuses' => $this->subscriptionStatuses(),
            'billingCycles' => $this->billingCycles(),
            'defaultMonthlyAmount' => $this->registeredOpsBridgeAmount(),
            'opsBridgeSubscription' => $organization->productSubscriptions
                ->first(fn ($subscription) => $subscription->product?->slug === 'opsbridge'),
            'onboardingChecklist' => $this->onboardingChecklist($organization),
        ]);
    }

    public function handover(Organization $organization)
    {
        $organization->load(['users', 'modules', 'productSubscriptions.product', 'productSubscriptions.partner']);
        $admin = $organization->users->firstWhere('role', 'admin');
        $opsBridgeSubscription = $organization->productSubscriptions
            ->first(fn ($subscription) => $subscription->product?->slug === 'opsbridge');
        $onboardingChecklist = $this->onboardingChecklist($organization);
        $enabledModules = $organization->modules
            ->where('is_enabled', true)
            ->map(fn ($module) => ModuleRegistry::get($module->module_key)['short_name'] ?? strtoupper($module->module_key))
            ->values();
        $productDomain = $opsBridgeSubscription?->product_domain
            ?: $opsBridgeSubscription?->product?->domain
            ?: config('niyantron.products.opsbridge.domain', 'opsbridge.niyantron.com');
        $loginUrl = 'https://' . trim(str_replace(['https://', 'http://'], '', (string) $productDomain), '/') . '/login';

        $messageLines = [
            'Hello ' . ($admin?->name ?? 'Admin') . ',',
            '',
            'Your OpsBridge account for ' . $organization->name . ' is ready.',
            '',
            'Login URL: ' . $loginUrl,
            'Admin Email: ' . ($admin?->email ?? 'Create the first admin account before sharing.'),
            'Temporary Password: [enter temporary password here]',
            '',
            'First steps after login:',
            '1. Change your password.',
            '2. Open Setup Wizard from Operations.',
            '3. Add facilities, departments and employees.',
            '4. Download and install the device agent for endpoint inventory.',
            '5. Review Production Readiness before live rollout.',
            '',
            'Please confirm once you are able to sign in.',
        ];

        $handoverMessage = implode("\n", $messageLines);

        return view('super-admin.organizations.handover', compact(
            'organization',
            'admin',
            'opsBridgeSubscription',
            'onboardingChecklist',
            'enabledModules',
            'loginUrl',
            'handoverMessage'
        ));
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

        return redirect()->route('super-admin.organizations.edit', $organization)
            ->with('success', 'Organization onboarding details updated.');
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

    public function updateOnboarding(Request $request, Organization $organization)
    {
        $validated = $request->validate([
            'credentials_shared' => ['nullable', 'boolean'],
            'initial_setup_completed' => ['nullable', 'boolean'],
        ]);

        $credentialsShared = $request->has('credentials_shared')
            ? (bool) ($validated['credentials_shared'] ?? false)
            : (bool) $organization->onboarding_credentials_shared_at;
        $initialSetupCompleted = $request->has('initial_setup_completed')
            ? (bool) ($validated['initial_setup_completed'] ?? false)
            : (bool) $organization->onboarding_initial_setup_completed_at;

        $organization->forceFill([
            'onboarding_credentials_shared_at' => $credentialsShared
                ? ($organization->onboarding_credentials_shared_at ?: now())
                : null,
            'onboarding_initial_setup_completed_at' => $initialSetupCompleted
                ? ($organization->onboarding_initial_setup_completed_at ?: now())
                : null,
        ])->save();

        return back()->with('success', 'Organization onboarding status updated.');
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

    private function onboardingChecklist(Organization $organization): array
    {
        $organization->loadMissing(['users', 'modules', 'productSubscriptions.product', 'productSubscriptions.partner']);

        $admin = $organization->users->firstWhere('role', 'admin');
        $opsBridgeSubscription = $organization->productSubscriptions
            ->first(fn ($subscription) => $subscription->product?->slug === 'opsbridge');
        $enabledModules = $organization->modules->where('is_enabled', true);
        $mailReady = ! in_array(config('mail.default'), ['log', 'array'], true)
            && filled(config('mail.from.address'));
        $firstLoginComplete = $admin && ($admin->last_login_at || ! $admin->must_change_password);

        $items = [
            [
                'label' => 'Organization created',
                'done' => $organization->exists,
                'note' => $organization->created_at?->format('d-m-Y') ?? 'Record is not saved yet.',
                'icon' => 'bi-building-check',
            ],
            [
                'label' => 'OpsBridge subscription',
                'done' => $opsBridgeSubscription && in_array($opsBridgeSubscription->status, ['trial', 'active'], true),
                'note' => $opsBridgeSubscription
                    ? ucfirst($opsBridgeSubscription->status) . ' - ' . ($opsBridgeSubscription->plan_name ?: 'OpsBridge')
                    : 'No OpsBridge subscription mapped.',
                'icon' => 'bi-ui-checks-grid',
            ],
            [
                'label' => 'First admin account',
                'done' => (bool) $admin,
                'note' => $admin ? $admin->email : 'Create or convert a lead to generate admin login.',
                'icon' => 'bi-person-check',
            ],
            [
                'label' => 'Modules enabled',
                'done' => $enabledModules->isNotEmpty(),
                'note' => $enabledModules->count() . ' module' . ($enabledModules->count() === 1 ? '' : 's') . ' enabled.',
                'icon' => 'bi-grid-1x2',
            ],
            [
                'label' => 'Partner linked',
                'done' => ! $opsBridgeSubscription || ! $opsBridgeSubscription->partner_id || (bool) $opsBridgeSubscription->partner,
                'note' => $opsBridgeSubscription?->partner
                    ? $opsBridgeSubscription->partner->display_name
                    : 'Direct sale or no partner selected.',
                'icon' => 'bi-person-workspace',
            ],
            [
                'label' => 'Login credentials shared',
                'done' => (bool) $organization->onboarding_credentials_shared_at,
                'note' => $organization->onboarding_credentials_shared_at?->format('d-m-Y h:i A') ?? 'Mark this after sharing temporary login.',
                'icon' => 'bi-key',
            ],
            [
                'label' => 'SMTP email configured',
                'done' => $mailReady,
                'note' => $mailReady ? config('mail.from.address') : 'Email setup is pending.',
                'icon' => 'bi-envelope-check',
            ],
            [
                'label' => 'Customer first login',
                'done' => (bool) $firstLoginComplete,
                'note' => $admin?->last_login_at?->format('d-m-Y h:i A') ?? ($admin ? 'Waiting for first login/password change.' : 'Admin account is pending.'),
                'icon' => 'bi-box-arrow-in-right',
            ],
            [
                'label' => 'Initial setup completed',
                'done' => (bool) $organization->onboarding_initial_setup_completed_at,
                'note' => $organization->onboarding_initial_setup_completed_at?->format('d-m-Y h:i A') ?? 'Mark after customer setup handover is complete.',
                'icon' => 'bi-flag',
            ],
        ];

        $completed = collect($items)->where('done', true)->count();

        return [
            'items' => $items,
            'completed' => $completed,
            'total' => count($items),
            'percent' => count($items) ? (int) round(($completed / count($items)) * 100) : 0,
        ];
    }
}
