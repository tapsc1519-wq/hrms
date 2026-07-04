<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\OrganizationRole;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{


    public function index(Request $request)
    {
        $query = User::where('organization_id', $this->orgId())
            ->where('role', '!=', 'super_admin')
            ->with(['department', 'customRole', 'employeeProfile']);

        if ($request->filled('role')) $query->where('role', $request->role);
        if ($request->filled('status')) $query->where('status', $request->status);
        if ($request->filled('search')) {
            $query->where(fn($q) => $q->where('name', 'like', '%' . $request->search . '%')
                ->orWhere('email', 'like', '%' . $request->search . '%'));
        }

        $users       = $query->latest()->paginate(20)->withQueryString();
        $customRoles = OrganizationRole::where('organization_id', $this->orgId())
            ->where('status', 'active')
            ->orderBy('portal_role')
            ->orderBy('name')
            ->get();
        $unlinkedInternalUsers = User::where('organization_id', $this->orgId())
            ->whereIn('role', ['admin', 'staff'])
            ->whereDoesntHave('employeeProfile')
            ->count();

        return view('admin.users.index', compact('users', 'customRoles', 'unlinkedInternalUsers'));
    }

    public function store(Request $request)
    {
        return back()
            ->withInput()
            ->with('error', 'Create employees, suppliers, vendors, auditors, and disposal buyers from their own sections. Access & Permissions only manages existing login access.');
    }

    public function update(Request $request, User $user)
    {
        abort_if($user->organization_id !== $this->orgId(), 403);

        $validated = $request->validate([
            'name'          => 'required|string|max:255',
            'email'         => 'required|email|unique:users,email,' . $user->id,
            'password'      => 'nullable|string|min:8',
            'role'          => 'required|in:admin,supplier,staff',
            'custom_role_id'=> 'nullable|exists:organization_roles,id',
            'status'        => 'required|in:active,inactive',
        ]);

        if ($validated['role'] !== $user->role) {
            return back()
                ->withInput()
                ->with('error', 'Change identity type from the related section. Access & Permissions only controls existing login access.');
        }

        if ($validated['password']) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }
        $this->validateCustomRole($validated['custom_role_id'] ?? null, $validated['role']);

        if ($validated['status'] === 'active' && in_array($validated['role'], ['admin', 'staff'], true) && !$user->employeeProfile) {
            return back()
                ->withInput()
                ->with('error', 'This internal account is not linked to an employee profile. Link/create the employee profile before activating this account.');
        }

        $user->update($validated);
        return back()->with('success', 'Access updated.');
    }

    public function destroy(User $user)
    {
        abort_if($user->organization_id !== $this->orgId(), 403);
        abort_if($user->id === auth()->id(), 403, 'Cannot delete your own account.');
        $user->delete();
        return back()->with('success', 'Access account deleted.');
    }

    private function validateCustomRole(?string $roleId, string $portalRole): void
    {
        if (!$roleId) {
            return;
        }

        $role = OrganizationRole::where('organization_id', $this->orgId())->findOrFail($roleId);
        abort_if($role->portal_role !== $portalRole, 422, 'Roles and permissions type must match the selected access type.');
    }
}
