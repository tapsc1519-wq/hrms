<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Software;
use App\Models\SoftwareAssignment;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SoftwareController extends Controller
{
    public function index(Request $request)
    {
        $query = Software::where('organization_id', $this->orgId())
            ->withCount(['licenses as total_licenses'])
            ->latest();

        if ($request->filled('search')) {
            $query->where(fn($q) =>
                $q->where('name', 'like', '%'.$request->search.'%')
                  ->orWhere('vendor', 'like', '%'.$request->search.'%')
            );
        }
        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }
        if ($request->filled('criticality')) {
            $query->where('criticality', $request->criticality);
        }
        if ($request->filled('license_required')) {
            $query->where('license_required', $request->license_required === 'yes');
        }

        $software = $query->paginate(20)->withQueryString();

        // Summary stats
        $stats = [
            'total_titles'    => Software::where('organization_id', $this->orgId())->count(),
            'total_licenses'  => \App\Models\SoftwareLicense::where('organization_id', $this->orgId())->where('status','active')->count(),
            'expiring_soon'   => \App\Models\SoftwareLicense::where('organization_id', $this->orgId())
                                    ->where('status','active')
                                    ->whereNotNull('expiry_date')
                                    ->where('expiry_date', '<=', now()->addDays(30))
                                    ->where('expiry_date', '>', now())
                                    ->count(),
            'total_assigned'  => SoftwareAssignment::whereHas('license', fn($q) =>
                                    $q->where('organization_id', $this->orgId())
                                  )->where('status','active')->count(),
        ];

        return view('admin.software.index', compact('software', 'stats'));
    }

    public function create()
    {
        return view('admin.software.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'              => 'required|string|max:255',
            'vendor'            => 'nullable|string|max:255',
            'version'           => 'nullable|string|max:100',
            'edition'           => 'nullable|string|max:100',
            'category'          => 'required|in:productivity,security,design,development,communication,database,erp,operating_system,other',
            'software_type'     => 'required|in:commercial,saas,open_source,freeware,os',
            'license_required'  => 'required|boolean',
            'criticality'       => 'required|in:low,medium,high,critical',
            'license_metric'    => 'required|in:per_user,per_device,concurrent,site,enterprise,usage_based',
            'trusted_publisher' => 'nullable|boolean',
            'description'       => 'nullable|string|max:2000',
            'publisher_website' => 'nullable|url|max:255',
            'icon'              => 'nullable|image|max:2048',
        ]);
        $validated['trusted_publisher'] = $request->boolean('trusted_publisher');

        if ($request->hasFile('icon')) {
            $validated['icon'] = $request->file('icon')->store('software-icons', 'public');
        }

        Software::create(array_merge($validated, ['organization_id' => $this->orgId()]));

        return redirect()->route('admin.software.index')
            ->with('success', 'Software added to catalog.');
    }

    public function show(Software $software)
    {
        abort_if($software->organization_id !== $this->orgId(), 403);

        $software->load(['licenses.vendor', 'licenses.activeAssignments.user']);

        $stats = [
            'total_licenses'   => $software->licenses->count(),
            'total_seats'      => $software->licenses->where('status','active')->sum('seats'),
            'used_seats'       => $software->licenses->sum(fn($l) => $l->used_seats),
            'expiring_soon'    => $software->licenses->filter(fn($l) => $l->is_expiring_soon)->count(),
        ];
        $stats['available_seats'] = max(0, $stats['total_seats'] - $stats['used_seats']);

        return view('admin.software.show', compact('software', 'stats'));
    }

    public function edit(Software $software)
    {
        abort_if($software->organization_id !== $this->orgId(), 403);
        return view('admin.software.edit', compact('software'));
    }

    public function update(Request $request, Software $software)
    {
        abort_if($software->organization_id !== $this->orgId(), 403);

        $validated = $request->validate([
            'name'              => 'required|string|max:255',
            'vendor'            => 'nullable|string|max:255',
            'version'           => 'nullable|string|max:100',
            'edition'           => 'nullable|string|max:100',
            'category'          => 'required|in:productivity,security,design,development,communication,database,erp,operating_system,other',
            'software_type'     => 'required|in:commercial,saas,open_source,freeware,os',
            'license_required'  => 'required|boolean',
            'criticality'       => 'required|in:low,medium,high,critical',
            'license_metric'    => 'required|in:per_user,per_device,concurrent,site,enterprise,usage_based',
            'trusted_publisher' => 'nullable|boolean',
            'description'       => 'nullable|string|max:2000',
            'publisher_website' => 'nullable|url|max:255',
            'icon'              => 'nullable|image|max:2048',
        ]);
        $validated['trusted_publisher'] = $request->boolean('trusted_publisher');

        if ($request->hasFile('icon')) {
            if ($software->icon) Storage::disk('public')->delete($software->icon);
            $validated['icon'] = $request->file('icon')->store('software-icons', 'public');
        }

        $software->update($validated);

        return redirect()->route('admin.software.show', $software)
            ->with('success', 'Software updated.');
    }

    public function destroy(Software $software)
    {
        abort_if($software->organization_id !== $this->orgId(), 403);
        if ($software->icon) Storage::disk('public')->delete($software->icon);
        $software->delete();
        return redirect()->route('admin.software.index')
            ->with('success', 'Software removed from catalog.');
    }
}
