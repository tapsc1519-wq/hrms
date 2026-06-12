<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Location;
use Illuminate\Http\Request;

class DepartmentController extends Controller
{


    public function index()
    {
        $departments = Department::where('organization_id', $this->orgId())
            ->withCount('users')
            ->latest()->paginate(20);
        return view('admin.departments.index', compact('departments'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            'code'        => 'nullable|string|max:50',
            'description' => 'nullable|string',
            'status'      => 'required|in:active,inactive',
        ]);
        $validated['organization_id'] = $this->orgId();
        Department::create($validated);
        return back()->with('success', 'Department added.');
    }

    public function update(Request $request, Department $department)
    {
        abort_if($department->organization_id !== $this->orgId(), 403);
        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            'code'        => 'nullable|string|max:50',
            'description' => 'nullable|string',
            'status'      => 'required|in:active,inactive',
        ]);
        $department->update($validated);
        return back()->with('success', 'Department updated.');
    }

    public function destroy(Department $department)
    {
        abort_if($department->organization_id !== $this->orgId(), 403);
        $department->delete();
        return back()->with('success', 'Department deleted.');
    }
}
