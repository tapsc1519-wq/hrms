<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Partner;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class PartnerController extends Controller
{
    public function index(Request $request)
    {
        $query = Partner::withCount('subscriptions')
            ->withSum('subscriptions as monthly_revenue', 'monthly_amount')
            ->latest();

        if ($request->filled('search')) {
            $search = trim((string) $request->search);
            $query->where(function ($query) use ($search) {
                $query->where('name', 'like', '%' . $search . '%')
                    ->orWhere('company_name', 'like', '%' . $search . '%')
                    ->orWhere('contact_person', 'like', '%' . $search . '%')
                    ->orWhere('email', 'like', '%' . $search . '%')
                    ->orWhere('phone', 'like', '%' . $search . '%');
            });
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $partners = $query->paginate(20)->withQueryString();
        $types = $this->types();
        $statuses = $this->statuses();

        $totalPartners = Partner::count();
        $activePartners = Partner::where('status', 'active')->count();
        $linkedSubscriptions = Partner::withCount('subscriptions')->get()->sum('subscriptions_count');

        return view('super-admin.partners.index', compact(
            'partners',
            'types',
            'statuses',
            'totalPartners',
            'activePartners',
            'linkedSubscriptions'
        ));
    }

    public function create()
    {
        $types = $this->types();
        $statuses = $this->statuses();

        return view('super-admin.partners.create', compact('types', 'statuses'));
    }

    public function store(Request $request)
    {
        $validated = $this->validated($request);
        $partner = Partner::create($this->partnerData($validated));
        $this->syncPortalUser($partner, $validated);

        return redirect()
            ->route('super-admin.partners.index')
            ->with('success', 'Partner created successfully.');
    }

    public function edit(Partner $partner)
    {
        $partner->load(['subscriptions.organization', 'subscriptions.product', 'commissions', 'portalUser']);
        $types = $this->types();
        $statuses = $this->statuses();
        $commissionSummary = [
            'pending' => $partner->commissions->where('status', 'pending')->sum('commission_amount'),
            'approved' => $partner->commissions->where('status', 'approved')->sum('commission_amount'),
            'paid' => $partner->commissions->where('status', 'paid')->sum('commission_amount'),
        ];

        return view('super-admin.partners.edit', compact('partner', 'types', 'statuses', 'commissionSummary'));
    }

    public function update(Request $request, Partner $partner)
    {
        $validated = $this->validated($request, $partner);
        $partner->update($this->partnerData($validated));
        $this->syncPortalUser($partner, $validated);

        return redirect()
            ->route('super-admin.partners.index')
            ->with('success', 'Partner updated successfully.');
    }

    public function destroy(Partner $partner)
    {
        if ($partner->subscriptions()->exists()) {
            return back()->with('error', 'This partner is linked with product subscriptions and cannot be deleted.');
        }

        $partner->delete();

        return redirect()
            ->route('super-admin.partners.index')
            ->with('success', 'Partner deleted successfully.');
    }

    private function validated(Request $request, ?Partner $partner = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'company_name' => ['nullable', 'string', 'max:255'],
            'contact_person' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'type' => ['required', 'in:' . implode(',', array_keys($this->types()))],
            'status' => ['required', 'in:' . implode(',', array_keys($this->statuses()))],
            'default_commission_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'payout_method' => ['nullable', 'string', 'max:120'],
            'payout_details' => ['nullable', 'string', 'max:2000'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'create_portal_account' => ['nullable', 'boolean'],
            'portal_email' => [
                $partner?->user_id || $request->boolean('create_portal_account') ? 'required' : 'nullable',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($partner?->user_id),
            ],
            'portal_password' => [
                $partner?->user_id || !$request->boolean('create_portal_account') ? 'nullable' : 'required',
                'string',
                'min:8',
            ],
            'portal_status' => ['nullable', 'in:active,inactive'],
        ]);
    }

    private function partnerData(array $validated): array
    {
        return collect($validated)->except([
            'create_portal_account',
            'portal_email',
            'portal_password',
            'portal_status',
        ])->all();
    }

    private function syncPortalUser(Partner $partner, array $validated): void
    {
        if (!$partner->user_id && empty($validated['create_portal_account'])) {
            return;
        }

        $user = $partner->portalUser ?: new User();
        $user->forceFill([
            'organization_id' => null,
            'name' => $partner->contact_person ?: $partner->name,
            'email' => $validated['portal_email'] ?? $partner->email,
            'role' => 'partner',
            'phone' => $partner->phone,
            'status' => $validated['portal_status'] ?? 'active',
        ]);

        if (!empty($validated['portal_password'])) {
            $user->password = Hash::make($validated['portal_password']);
        }

        if (!$user->exists && empty($user->password)) {
            return;
        }

        $user->save();
        $partner->forceFill(['user_id' => $user->id])->save();
    }

    private function types(): array
    {
        return [
            'individual' => 'Individual',
            'agency' => 'Agency',
            'reseller' => 'Reseller',
            'consultant' => 'Consultant',
        ];
    }

    private function statuses(): array
    {
        return [
            'active' => 'Active',
            'inactive' => 'Inactive',
            'suspended' => 'Suspended',
        ];
    }
}
