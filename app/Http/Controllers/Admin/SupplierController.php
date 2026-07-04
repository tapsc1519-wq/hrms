<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SupplierController extends Controller
{
    public function index(Request $request)
    {
        $query = Supplier::where('organization_id', $this->orgId())
            ->whereIn('partner_type', ['supplier', 'both'])
            ->withCount(['assets', 'purchaseOrders']);

        if ($request->filled('search')) {
            $query->where(fn($q) =>
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('email', 'like', '%' . $request->search . '%')
            );
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $suppliers = $query->latest()->paginate(20)->withQueryString();

        return view('admin.suppliers.index', compact('suppliers'));
    }

    public function create()
    {
        return view('admin.suppliers.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'           => 'required|string|max:255',
            'code'           => 'nullable|string|max:50',
            'email'          => 'nullable|email|max:255',
            'phone'          => 'nullable|string|max:50',
            'address'        => 'nullable|string',
            'city'           => 'nullable|string|max:100',
            'country'        => 'nullable|string|max:100',
            'contact_person' => 'nullable|string|max:255',
            'contact_phone'  => 'nullable|string|max:50',
            'website'        => 'nullable|url|max:255',
            'tax_number'     => 'nullable|string|max:100',
            'bank_details'   => 'nullable|string',
            'notes'          => 'nullable|string',
            'status'         => 'required|in:active,inactive,blacklisted',
            'logo'           => 'nullable|image|max:2048',
        ]);

        $validated['organization_id'] = $this->orgId();
        $validated['partner_type'] = 'supplier';

        if ($request->hasFile('logo')) {
            $validated['logo'] = $request->file('logo')->store('suppliers', 'public');
        }

        Supplier::create($validated);

        return redirect()->route('admin.suppliers.index')
            ->with('success', 'Supplier added successfully.');
    }

    public function show(Supplier $supplier)
    {
        abort_if($supplier->organization_id !== $this->orgId(), 403);
        $supplier->load(['assets', 'purchaseOrders', 'invoices']);
        return view('admin.suppliers.show', compact('supplier'));
    }

    public function edit(Supplier $supplier)
    {
        abort_if($supplier->organization_id !== $this->orgId(), 403);
        return view('admin.suppliers.edit', compact('supplier'));
    }

    public function update(Request $request, Supplier $supplier)
    {
        abort_if($supplier->organization_id !== $this->orgId(), 403);

        $validated = $request->validate([
            'name'           => 'required|string|max:255',
            'code'           => 'nullable|string|max:50',
            'email'          => 'nullable|email|max:255',
            'phone'          => 'nullable|string|max:50',
            'address'        => 'nullable|string',
            'city'           => 'nullable|string|max:100',
            'country'        => 'nullable|string|max:100',
            'contact_person' => 'nullable|string|max:255',
            'contact_phone'  => 'nullable|string|max:50',
            'website'        => 'nullable|url|max:255',
            'tax_number'     => 'nullable|string|max:100',
            'bank_details'   => 'nullable|string',
            'notes'          => 'nullable|string',
            'status'         => 'required|in:active,inactive,blacklisted',
            'logo'           => 'nullable|image|max:2048',
        ]);

        if ($request->hasFile('logo')) {
            if ($supplier->logo) Storage::disk('public')->delete($supplier->logo);
            $validated['logo'] = $request->file('logo')->store('suppliers', 'public');
        }

        $supplier->update($validated);

        return redirect()->route('admin.suppliers.index')
            ->with('success', 'Supplier updated successfully.');
    }

    public function destroy(Supplier $supplier)
    {
        abort_if($supplier->organization_id !== $this->orgId(), 403);
        if ($supplier->logo) Storage::disk('public')->delete($supplier->logo);
        $supplier->delete();
        return redirect()->route('admin.suppliers.index')
            ->with('success', 'Supplier deleted successfully.');
    }
}
