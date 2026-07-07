<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DisposalBuyer;
use Illuminate\Http\Request;

class DisposalBuyerController extends Controller
{
    public function index(Request $request)
    {
        $query = DisposalBuyer::where('organization_id', $this->orgId())
            ->withCount('disposals')
            ->latest();

        if ($request->filled('search')) {
            $query->where(function ($searchQuery) use ($request) {
                $searchQuery->where('name', 'like', '%' . $request->search . '%')
                    ->orWhere('contact_person', 'like', '%' . $request->search . '%')
                    ->orWhere('email', 'like', '%' . $request->search . '%')
                    ->orWhere('phone', 'like', '%' . $request->search . '%');
            });
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $buyers = $query->paginate(20)->withQueryString();

        return view('admin.disposal-buyers.index', compact('buyers'));
    }

    public function create()
    {
        $buyer = new DisposalBuyer(['status' => 'active', 'type' => 'external_buyer']);

        return view('admin.disposal-buyers.create', compact('buyer'));
    }

    public function store(Request $request)
    {
        DisposalBuyer::create([
            ...$this->validated($request),
            'organization_id' => $this->orgId(),
        ]);

        return redirect()->route('admin.disposal-buyers.index')->with('success', 'Disposal buyer created successfully.');
    }

    public function edit(DisposalBuyer $disposalBuyer)
    {
        $this->authorizeBuyer($disposalBuyer);
        $buyer = $disposalBuyer;

        return view('admin.disposal-buyers.edit', compact('buyer'));
    }

    public function update(Request $request, DisposalBuyer $disposalBuyer)
    {
        $this->authorizeBuyer($disposalBuyer);
        $disposalBuyer->update($this->validated($request));

        return redirect()->route('admin.disposal-buyers.index')->with('success', 'Disposal buyer updated successfully.');
    }

    public function destroy(DisposalBuyer $disposalBuyer)
    {
        $this->authorizeBuyer($disposalBuyer);

        if ($disposalBuyer->disposals()->exists()) {
            return back()->with('error', 'This buyer is already linked with disposal records. Mark it inactive instead.');
        }

        $disposalBuyer->delete();

        return back()->with('success', 'Disposal buyer deleted successfully.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:employee,external_buyer,vendor_recycler,auction_buyer,donation_recipient'],
            'contact_person' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:2000'],
            'tax_number' => ['nullable', 'string', 'max:100'],
            'status' => ['required', 'in:active,inactive,blacklisted'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);
    }

    private function authorizeBuyer(DisposalBuyer $buyer): void
    {
        abort_if($buyer->organization_id !== $this->orgId(), 403);
    }
}
