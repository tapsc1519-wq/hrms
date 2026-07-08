<?php

namespace App\Http\Controllers\Partner;

use App\Http\Controllers\Controller;
use App\Models\PartnerLead;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LeadController extends Controller
{
    public function index(Request $request)
    {
        $partner = $this->partner();
        $query = PartnerLead::with('product')->where('partner_id', $partner->id)->latest();

        if ($request->filled('stage')) {
            $query->where('stage', $request->stage);
        }

        if ($request->filled('search')) {
            $search = trim((string) $request->search);
            $query->where(function ($query) use ($search) {
                $query->where('company_name', 'like', '%' . $search . '%')
                    ->orWhere('contact_person', 'like', '%' . $search . '%')
                    ->orWhere('email', 'like', '%' . $search . '%')
                    ->orWhere('phone', 'like', '%' . $search . '%');
            });
        }

        return view('partner.leads.index', [
            'partner' => $partner,
            'leads' => $query->paginate(20)->withQueryString(),
            'stages' => $this->stages(),
        ]);
    }

    public function create()
    {
        return view('partner.leads.create', [
            'lead' => new PartnerLead(['stage' => 'new']),
            'products' => $this->products(),
            'stages' => $this->stages(),
            'partner' => $this->partner(),
        ]);
    }

    public function store(Request $request)
    {
        $partner = $this->partner();
        $data = $this->validated($request);
        $data['partner_id'] = $partner->id;
        $data['commission_percent'] = $data['commission_percent'] ?? $partner->default_commission_percent;
        $data['stage'] = 'new';

        PartnerLead::create($data);

        return redirect()->route('partner.leads.index')->with('success', 'Lead submitted successfully.');
    }

    private function partner()
    {
        $partner = auth()->user()->partner;
        abort_if(!$partner, 403, 'Your partner account is not linked yet.');

        return $partner;
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'product_id' => ['nullable', Rule::exists((new Product())->getConnectionName() . '.products', 'id')],
            'company_name' => ['required', 'string', 'max:255'],
            'contact_person' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'expected_monthly_value' => ['required', 'numeric', 'min:0', 'max:99999999'],
            'commission_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'expected_close_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);
    }

    private function products()
    {
        return Product::where('status', 'active')->orderBy('sort_order')->orderBy('name')->get(['id', 'name']);
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
}
