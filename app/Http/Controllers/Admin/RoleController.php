<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\OrganizationRole;
use App\Support\PermissionRegistry;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RoleController extends Controller
{
    public function index()
    {
        $roles = OrganizationRole::where('organization_id', $this->orgId())
            ->withCount('users')
            ->orderBy('portal_role')
            ->orderBy('name')
            ->get();

        $permissionGroups = PermissionRegistry::groups();

        return view('admin.roles.index', compact('roles', 'permissionGroups'));
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['organization_id'] = $this->orgId();
        $data['permissions'] = $this->cleanPermissions($request->input('permissions', []));

        OrganizationRole::create($data);

        return back()->with('success', 'Role created successfully.');
    }

    public function update(Request $request, OrganizationRole $role)
    {
        abort_if($role->organization_id !== $this->orgId(), 403);

        $data = $this->validated($request, $role->id);
        $data['permissions'] = $this->cleanPermissions($request->input('permissions', []));

        $role->update($data);

        return back()->with('success', 'Role updated successfully.');
    }

    public function destroy(OrganizationRole $role)
    {
        abort_if($role->organization_id !== $this->orgId(), 403);

        if ($role->users()->exists()) {
            return back()->withErrors(['role' => 'This role is assigned to users. Reassign them before deleting it.']);
        }

        $role->delete();

        return back()->with('success', 'Role deleted successfully.');
    }

    private function validated(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'name' => [
                'required', 'string', 'max:100',
                Rule::unique('organization_roles', 'name')
                    ->where('organization_id', $this->orgId())
                    ->ignore($ignoreId),
            ],
            'portal_role' => 'required|in:admin,staff,supplier',
            'description' => 'nullable|string|max:1000',
            'status' => 'required|in:active,inactive',
            'permissions' => 'nullable|array',
            'permissions.*' => ['string', Rule::in(PermissionRegistry::all())],
        ]);
    }

    private function cleanPermissions(array $permissions): array
    {
        return array_values(array_unique(array_intersect($permissions, PermissionRegistry::all())));
    }
}
