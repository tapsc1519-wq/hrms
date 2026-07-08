<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Partner;
use Illuminate\Http\Request;

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
        Partner::create($this->validated($request));

        return redirect()
            ->route('super-admin.partners.index')
            ->with('success', 'Partner created successfully.');
    }

    public function edit(Partner $partner)
    {
        $partner->load(['subscriptions.organization', 'subscriptions.product']);
        $types = $this->types();
        $statuses = $this->statuses();

        return view('super-admin.partners.edit', compact('partner', 'types', 'statuses'));
    }

    public function update(Request $request, Partner $partner)
    {
        $partner->update($this->validated($request, $partner));

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
        ]);
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
