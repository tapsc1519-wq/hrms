<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\OrganizationProductSubscription;
use App\Models\Partner;
use App\Models\PartnerLead;
use App\Models\Product;
use App\Models\User;
use App\Support\ModuleRegistry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PartnerLeadController extends Controller
{
    public function index(Request $request)
    {
        $query = PartnerLead::with(['partner', 'product'])->latest();

        if ($request->filled('search')) {
            $search = trim((string) $request->search);
            $query->where(function ($query) use ($search) {
                $query->where('company_name', 'like', '%' . $search . '%')
                    ->orWhere('contact_person', 'like', '%' . $search . '%')
                    ->orWhere('email', 'like', '%' . $search . '%')
                    ->orWhere('phone', 'like', '%' . $search . '%');
            });
        }

        if ($request->filled('partner_id')) {
            $query->where('partner_id', $request->partner_id);
        }

        if ($request->filled('stage')) {
            $query->where('stage', $request->stage);
        }

        $leads = $query->paginate(20)->withQueryString();
        $partners = $this->partners();
        $products = $this->products();
        $stages = $this->stages();

        $summary = [
            'total' => PartnerLead::count(),
            'open' => PartnerLead::whereNotIn('stage', ['won', 'lost'])->count(),
            'won' => PartnerLead::where('stage', 'won')->count(),
            'pipeline_value' => PartnerLead::whereNotIn('stage', ['won', 'lost'])->sum('expected_monthly_value'),
        ];

        return view('super-admin.partner-leads.index', compact('leads', 'partners', 'products', 'stages', 'summary'));
    }

    public function create()
    {
        return view('super-admin.partner-leads.create', [
            'lead' => new PartnerLead(['stage' => 'new']),
            'partners' => $this->partners(),
            'products' => $this->products(),
            'stages' => $this->stages(),
        ]);
    }

    public function store(Request $request)
    {
        PartnerLead::create($this->validated($request));

        return redirect()
            ->route('super-admin.partner-leads.index')
            ->with('success', 'Partner lead created successfully.');
    }

    public function edit(PartnerLead $partnerLead)
    {
        $partnerLead->load(['partner', 'product']);

        return view('super-admin.partner-leads.edit', [
            'lead' => $partnerLead,
            'partners' => $this->partners(),
            'products' => $this->products(),
            'stages' => $this->stages(),
        ]);
    }

    public function update(Request $request, PartnerLead $partnerLead)
    {
        $partnerLead->update($this->validated($request));

        return redirect()
            ->route('super-admin.partner-leads.index')
            ->with('success', 'Partner lead updated successfully.');
    }

    public function convert(PartnerLead $partnerLead)
    {
        if ($partnerLead->converted_organization_id) {
            return back()->with('error', 'This lead is already converted.');
        }

        $partnerLead->load(['partner', 'product']);
        $product = $partnerLead->product ?: Product::where('slug', 'opsbridge')->first();

        if (!$product) {
            return back()->with('error', 'No product is available for conversion.');
        }

        if (! filled($partnerLead->email)) {
            return back()->with('error', 'Add a contact email before converting this lead. It will be used for the organization admin login.');
        }

        if (User::where('email', $partnerLead->email)->exists()) {
            return back()->with('error', 'A portal account already exists with ' . $partnerLead->email . '. Use a different lead email before conversion.');
        }

        $temporaryPassword = Str::random(10) . '#9Aa';

        $organization = DB::transaction(function () use ($partnerLead, $temporaryPassword) {
            $organization = Organization::create([
                'name' => $partnerLead->company_name,
                'slug' => $this->uniqueOrganizationSlug($partnerLead->company_name),
                'email' => $partnerLead->email,
                'phone' => $partnerLead->phone,
                'status' => 'active',
                'trial_months' => 1,
                'trial_started_at' => now(),
                'trial_ends_at' => now()->addMonth()->toDateString(),
                'billing_status' => 'trial',
                'billing_cycle' => 'monthly',
                'monthly_amount' => $partnerLead->expected_monthly_value,
            ]);

            $organization->syncModules(ModuleRegistry::keys(), auth()->id());

            User::create([
                'organization_id' => $organization->id,
                'name' => $partnerLead->contact_person ?: $partnerLead->company_name . ' Admin',
                'email' => $partnerLead->email,
                'password' => Hash::make($temporaryPassword),
                'must_change_password' => true,
                'role' => 'admin',
                'phone' => $partnerLead->phone,
                'status' => 'active',
            ]);

            return $organization;
        });

        $productDomain = $product->domain ?: config('niyantron.products.opsbridge.domain');

        OrganizationProductSubscription::updateOrCreate(
            [
                'organization_id' => $organization->id,
                'product_id' => $product->id,
            ],
            [
                'partner_id' => $partnerLead->partner_id,
                'status' => 'trial',
                'plan_name' => $product->short_name ?: $product->name,
                'billing_cycle' => 'monthly',
                'monthly_amount' => $partnerLead->expected_monthly_value,
                'commission_percent' => $partnerLead->commission_percent ?? $partnerLead->partner?->default_commission_percent,
                'trial_started_at' => $organization->trial_started_at,
                'trial_ends_at' => $organization->trial_ends_at,
                'product_database' => config('database.connections.' . config('database.product_connection', 'opsbridge') . '.database'),
                'product_domain' => $productDomain,
                'notes' => 'Converted from partner lead #' . $partnerLead->id,
            ]
        );

        $partnerLead->forceFill([
            'stage' => 'won',
            'converted_organization_id' => $organization->id,
            'converted_at' => now(),
        ])->save();

        return redirect()
            ->route('super-admin.organizations.edit', $organization)
            ->with('success', 'Lead converted to organization, product subscription, and first admin account.')
            ->with('onboarding_credentials', [
                'organization' => $organization->name,
                'product' => $product->short_name ?: $product->name,
                'login_url' => 'https://' . rtrim((string) $productDomain, '/') . '/login',
                'email' => $partnerLead->email,
                'password' => $temporaryPassword,
            ]);
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'partner_id' => ['required', Rule::exists((new Partner())->getConnectionName() . '.partners', 'id')],
            'product_id' => ['nullable', Rule::exists((new Product())->getConnectionName() . '.products', 'id')],
            'company_name' => ['required', 'string', 'max:255'],
            'contact_person' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'expected_monthly_value' => ['required', 'numeric', 'min:0', 'max:99999999'],
            'commission_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'stage' => ['required', 'in:' . implode(',', array_keys($this->stages()))],
            'expected_close_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);
    }

    private function partners()
    {
        return Partner::where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name', 'company_name', 'default_commission_percent']);
    }

    private function products()
    {
        return Product::whereIn('status', ['active', 'coming_soon'])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name', 'short_name', 'slug', 'domain']);
    }

    private function stages(): array
    {
        return [
            'new' => 'New',
            'contacted' => 'Contacted',
            'demo' => 'Demo',
            'proposal' => 'Proposal',
            'won' => 'Won',
            'lost' => 'Lost',
        ];
    }

    private function uniqueOrganizationSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'organization';
        $slug = $base;
        $counter = 2;

        while (Organization::where('slug', $slug)->exists()) {
            $slug = $base . '-' . $counter;
            $counter++;
        }

        return $slug;
    }
}
