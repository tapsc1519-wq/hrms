<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Support\DepartmentTemplateRegistry;
use Illuminate\Http\Request;

class DepartmentController extends Controller
{


    public function index()
    {
        $departments = Department::where('organization_id', $this->orgId())
            ->withCount('users')
            ->latest()->paginate(20);
        $suggestedDepartments = DepartmentTemplateRegistry::all();

        return view('admin.departments.index', compact('departments', 'suggestedDepartments'));
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

    public function storeSuggested(Request $request)
    {
        $validated = $request->validate([
            'departments' => ['required', 'array', 'min:1'],
            'departments.*' => ['string', 'in:' . implode(',', DepartmentTemplateRegistry::names())],
        ]);

        $templates = collect(DepartmentTemplateRegistry::all())
            ->whereIn('name', $validated['departments'])
            ->values();
        $existingNames = Department::where('organization_id', $this->orgId())
            ->pluck('name')
            ->map(fn (string $name) => mb_strtolower($name))
            ->all();
        $existingCodes = Department::where('organization_id', $this->orgId())
            ->whereNotNull('code')
            ->pluck('code')
            ->map(fn (string $code) => mb_strtolower($code))
            ->all();

        $created = 0;
        $skipped = 0;

        foreach ($templates as $template) {
            if (in_array(mb_strtolower($template['name']), $existingNames, true)
                || in_array(mb_strtolower($template['code']), $existingCodes, true)) {
                $skipped++;
                continue;
            }

            Department::create([
                'organization_id' => $this->orgId(),
                'name' => $template['name'],
                'code' => $template['code'],
                'description' => $template['description'],
                'status' => 'active',
            ]);

            $existingNames[] = mb_strtolower($template['name']);
            $existingCodes[] = mb_strtolower($template['code']);
            $created++;
        }

        return back()->with('success', "{$created} suggested department(s) created. {$skipped} already existed and were skipped.");
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
