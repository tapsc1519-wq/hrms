<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class VendorController extends Controller
{
    public function index(Request $request)
    {
        $query = Supplier::where('organization_id', $this->orgId())
            ->whereIn('partner_type', ['vendor', 'both'])
            ->withCount(['assetRepairs', 'amcContracts']);

        if ($request->filled('search')) {
            $query->where(fn($q) =>
                $q->where('name', 'like', '%' . $request->search . '%')
                    ->orWhere('email', 'like', '%' . $request->search . '%')
            );
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $vendors = $query->latest()->paginate(20)->withQueryString();

        return view('admin.vendors.index', compact('vendors'));
    }

    public function create()
    {
        return view('admin.vendors.form', ['vendor' => new Supplier()]);
    }

    public function store(Request $request)
    {
        $validated = $this->validatedData($request);
        $validated['organization_id'] = $this->orgId();
        $validated['partner_type'] = 'vendor';

        if ($request->hasFile('logo')) {
            $validated['logo'] = $request->file('logo')->store('vendors', 'public');
        }

        $portalData = $this->validatedPortalData($request);
        $vendor = Supplier::create($validated);
        $this->syncPortalAccount($vendor, $portalData, true);

        return redirect()->route('admin.vendors.index')->with('success', 'Vendor added successfully.');
    }

    public function edit(Supplier $vendor)
    {
        $this->authorizeVendor($vendor);

        return view('admin.vendors.form', compact('vendor'));
    }

    public function update(Request $request, Supplier $vendor)
    {
        $this->authorizeVendor($vendor);

        $validated = $this->validatedData($request);

        if ($request->hasFile('logo')) {
            if ($vendor->logo) {
                Storage::disk('public')->delete($vendor->logo);
            }
            $validated['logo'] = $request->file('logo')->store('vendors', 'public');
        }

        $vendor->update($validated);
        $this->syncPortalAccount($vendor, $this->validatedPortalData($request), false);

        return redirect()->route('admin.vendors.index')->with('success', 'Vendor updated successfully.');
    }

    public function destroy(Supplier $vendor)
    {
        $this->authorizeVendor($vendor);

        if ($vendor->logo) {
            Storage::disk('public')->delete($vendor->logo);
        }

        $vendor->delete();

        return redirect()->route('admin.vendors.index')->with('success', 'Vendor deleted successfully.');
    }

    private function validatedData(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string'],
            'city' => ['nullable', 'string', 'max:100'],
            'country' => ['nullable', 'string', 'max:100'],
            'contact_person' => ['nullable', 'string', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:50'],
            'website' => ['nullable', 'url', 'max:255'],
            'tax_number' => ['nullable', 'string', 'max:100'],
            'bank_details' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
            'status' => ['required', 'in:active,inactive,blacklisted'],
            'rating' => ['nullable', 'numeric', 'min:0', 'max:5'],
            'logo' => ['nullable', 'image', 'max:2048'],
        ]);
    }

    private function validatedPortalData(Request $request): array
    {
        return $request->validate([
            'enable_portal' => ['nullable', 'boolean'],
            'portal_email' => ['nullable', 'required_if:enable_portal,1', 'email', 'max:255'],
            'portal_password' => ['nullable', 'string', 'min:8'],
        ]);
    }

    private function syncPortalAccount(Supplier $vendor, array $portalData, bool $creating): void
    {
        if (empty($portalData['enable_portal'])) {
            return;
        }

        if ($creating && empty($portalData['portal_password'])) {
            throw ValidationException::withMessages([
                'portal_password' => 'Portal password is required when creating vendor portal access.',
            ]);
        }

        $email = strtolower($portalData['portal_email']);
        $existingUser = User::whereRaw('LOWER(email) = ?', [$email])
            ->when($vendor->user_id, fn($query) => $query->where('id', '!=', $vendor->user_id))
            ->first();

        if ($existingUser) {
            throw ValidationException::withMessages([
                'portal_email' => 'This portal email is already used by another account.',
            ]);
        }

        if ($vendor->user) {
            $payload = [
                'name' => $vendor->contact_person ?: $vendor->name,
                'email' => $email,
                'phone' => $vendor->contact_phone ?: $vendor->phone,
                'status' => $vendor->status === 'active' ? 'active' : 'inactive',
            ];

            if (!empty($portalData['portal_password'])) {
                $payload['password'] = Hash::make($portalData['portal_password']);
            }

            $vendor->user->update($payload);
            return;
        }

        $user = User::create([
            'organization_id' => $vendor->organization_id,
            'name' => $vendor->contact_person ?: $vendor->name,
            'email' => $email,
            'phone' => $vendor->contact_phone ?: $vendor->phone,
            'password' => Hash::make($portalData['portal_password']),
            'role' => 'supplier',
            'status' => $vendor->status === 'active' ? 'active' : 'inactive',
        ]);

        $vendor->update(['user_id' => $user->id]);
    }

    private function authorizeVendor(Supplier $vendor): void
    {
        abort_if($vendor->organization_id !== $this->orgId() || !in_array($vendor->partner_type, ['vendor', 'both'], true), 403);
    }
}
