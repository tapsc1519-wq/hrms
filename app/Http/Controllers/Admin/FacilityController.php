<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Facility;
use App\Models\Location;
use Illuminate\Http\Request;

class FacilityController extends Controller
{
    // ── Facilities ────────────────────────────────────────────────────────────

    public function index()
    {
        $facilities = Facility::where('organization_id', $this->orgId())
            ->withCount('locations')
            ->orderBy('state')
            ->orderBy('name')
            ->paginate(20);

        return view('admin.facilities.index', compact('facilities'));
    }

    public function create()
    {
        return view('admin.facilities.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            'code'        => 'nullable|string|max:50',
            'address'     => 'nullable|string',
            'city'        => 'nullable|string|max:100',
            'state'       => 'nullable|string|max:100',
            'country'     => 'nullable|string|max:100',
            'phone'       => 'nullable|string|max:50',
            'email'       => 'nullable|email|max:255',
            'description' => 'nullable|string',
            'status'      => 'required|in:active,inactive',
        ]);

        $validated['organization_id'] = $this->orgId();
        Facility::create($validated);

        return redirect()->route('admin.facilities.index')
            ->with('success', 'Facility created successfully.');
    }

    public function show(Facility $facility)
    {
        abort_if($facility->organization_id !== $this->orgId(), 403);

        $facility->load(['locations' => fn($q) => $q->orderBy('name')]);

        return view('admin.facilities.show', compact('facility'));
    }

    public function edit(Facility $facility)
    {
        abort_if($facility->organization_id !== $this->orgId(), 403);

        return view('admin.facilities.edit', compact('facility'));
    }

    public function update(Request $request, Facility $facility)
    {
        abort_if($facility->organization_id !== $this->orgId(), 403);

        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            'code'        => 'nullable|string|max:50',
            'address'     => 'nullable|string',
            'city'        => 'nullable|string|max:100',
            'state'       => 'nullable|string|max:100',
            'country'     => 'nullable|string|max:100',
            'phone'       => 'nullable|string|max:50',
            'email'       => 'nullable|email|max:255',
            'description' => 'nullable|string',
            'status'      => 'required|in:active,inactive',
        ]);

        $facility->update($validated);

        return redirect()->route('admin.facilities.show', $facility)
            ->with('success', 'Facility updated successfully.');
    }

    public function destroy(Facility $facility)
    {
        abort_if($facility->organization_id !== $this->orgId(), 403);
        $facility->delete();

        return redirect()->route('admin.facilities.index')
            ->with('success', 'Facility deleted.');
    }

    // ── Work Locations (nested under a facility) ──────────────────────────────

    public function storeLocation(Request $request, Facility $facility)
    {
        abort_if($facility->organization_id !== $this->orgId(), 403);

        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            'building'    => 'nullable|string|max:100',
            'floor'       => 'nullable|string|max:50',
            'room'        => 'nullable|string|max:100',
            'description' => 'nullable|string',
            'status'      => 'required|in:active,inactive',
        ]);

        $validated['facility_id']    = $facility->id;
        $validated['organization_id'] = $this->orgId();

        Location::create($validated);

        return redirect()->route('admin.facilities.show', $facility)
            ->with('success', 'Work location added.');
    }

    public function updateLocation(Request $request, Facility $facility, Location $location)
    {
        abort_if($facility->organization_id !== $this->orgId(), 403);
        abort_if($location->facility_id !== $facility->id, 403);

        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            'building'    => 'nullable|string|max:100',
            'floor'       => 'nullable|string|max:50',
            'room'        => 'nullable|string|max:100',
            'description' => 'nullable|string',
            'status'      => 'required|in:active,inactive',
        ]);

        $location->update($validated);

        return redirect()->route('admin.facilities.show', $facility)
            ->with('success', 'Work location updated.');
    }

    public function destroyLocation(Facility $facility, Location $location)
    {
        abort_if($facility->organization_id !== $this->orgId(), 403);
        abort_if($location->facility_id !== $facility->id, 403);

        $location->delete();

        return redirect()->route('admin.facilities.show', $facility)
            ->with('success', 'Work location removed.');
    }
}
