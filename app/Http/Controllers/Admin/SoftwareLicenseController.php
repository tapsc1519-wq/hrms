<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Software;
use App\Models\SoftwareAssignment;
use App\Models\SoftwareLicense;
use App\Models\User;
use App\Models\Supplier;
use Illuminate\Http\Request;

class SoftwareLicenseController extends Controller
{
    public function index(Request $request)
    {
        $query = SoftwareLicense::where('organization_id', $this->orgId())
            ->with(['software', 'supplier'])
            ->latest();

        if ($request->filled('status'))   $query->where('status', $request->status);
        if ($request->filled('software_id')) $query->where('software_id', $request->software_id);

        $licenses = $query->paginate(25)->withQueryString();

        $softwareList = Software::where('organization_id', $this->orgId())
            ->orderBy('name')->get(['id','name']);

        // Compliance summary
        $allActive = SoftwareLicense::where('organization_id', $this->orgId())
            ->where('status','active')->with('activeAssignments')->get();

        $compliance = [
            'over'   => $allActive->filter(fn($l) => $l->is_over_licensed)->count(),
            'expiring' => $allActive->filter(fn($l) => $l->is_expiring_soon)->count(),
            'total_seats' => $allActive->sum('seats'),
            'used_seats'  => $allActive->sum(fn($l) => $l->used_seats),
        ];

        return view('admin.software-licenses.index', compact('licenses', 'softwareList', 'compliance'));
    }

    public function create(Request $request)
    {
        $software = Software::where('organization_id', $this->orgId())
            ->orderBy('name')->get(['id','name','vendor']);
        $suppliers = Supplier::where('organization_id', $this->orgId())
            ->orderBy('name')->get(['id','name']);
        $selectedSoftware = $request->filled('software_id')
            ? $request->software_id : null;

        return view('admin.software-licenses.create', compact('software', 'suppliers', 'selectedSoftware'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'software_id'    => 'required|exists:software,id',
            'license_type'   => 'required|in:perpetual,subscription,concurrent,per_seat,per_device,oem,volume,open_source,freeware',
            'seats'          => 'required|integer|min:1|max:99999',
            'license_key'    => 'nullable|string|max:500',
            'purchase_date'  => 'nullable|date',
            'expiry_date'    => 'nullable|date|after_or_equal:purchase_date',
            'purchase_price' => 'nullable|numeric|min:0',
            'vendor_id'      => 'nullable|exists:suppliers,id',
            'po_number'      => 'nullable|string|max:100',
            'notes'          => 'nullable|string|max:1000',
        ]);

        $software = Software::findOrFail($validated['software_id']);
        abort_if($software->organization_id !== $this->orgId(), 403);

        SoftwareLicense::create(array_merge($validated, [
            'organization_id' => $this->orgId(),
            'status'          => 'active',
        ]));

        return redirect()->route('admin.software.show', $validated['software_id'])
            ->with('success', 'License record added.');
    }

    public function show(SoftwareLicense $softwareLicense)
    {
        abort_if($softwareLicense->organization_id !== $this->orgId(), 403);

        $softwareLicense->load(['software','supplier','assignments.user','assignments.assignedBy']);

        $employees = User::where('organization_id', $this->orgId())
            ->where('role', 'staff')
            ->orderBy('name')
            ->get(['id','name','email','job_title']);

        return view('admin.software-licenses.show', compact('softwareLicense', 'employees'));
    }

    public function assign(Request $request, SoftwareLicense $softwareLicense)
    {
        abort_if($softwareLicense->organization_id !== $this->orgId(), 403);

        $request->validate([
            'user_id'       => 'required|exists:users,id',
            'assigned_date' => 'required|date',
            'notes'         => 'nullable|string|max:500',
        ]);

        // Warn if already assigned to this user under this license
        $existing = SoftwareAssignment::where('software_license_id', $softwareLicense->id)
            ->where('user_id', $request->user_id)
            ->where('status', 'active')
            ->first();

        if ($existing) {
            return back()->with('error', 'This license is already assigned to that employee.');
        }

        SoftwareAssignment::create([
            'software_license_id' => $softwareLicense->id,
            'user_id'             => $request->user_id,
            'assigned_by'         => auth()->id(),
            'assigned_date'       => $request->assigned_date,
            'notes'               => $request->notes,
            'status'              => 'active',
        ]);

        return back()->with('success', 'License assigned successfully.');
    }

    public function returnLicense(SoftwareLicense $softwareLicense, SoftwareAssignment $assignment)
    {
        abort_if($softwareLicense->organization_id !== $this->orgId(), 403);

        $assignment->update([
            'status'        => 'returned',
            'returned_date' => now()->toDateString(),
        ]);

        return back()->with('success', 'License returned and seat freed.');
    }

    public function destroy(SoftwareLicense $softwareLicense)
    {
        abort_if($softwareLicense->organization_id !== $this->orgId(), 403);
        $softwareLicense->delete();
        return redirect()->route('admin.software.show', $softwareLicense->software_id)
            ->with('success', 'License record deleted.');
    }
}
