<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\OrganizationRole;
use App\Models\User;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{


    public function index(Request $request)
    {
        $query = User::where('organization_id', $this->orgId())
            ->where('role', '!=', 'super_admin')
            ->with(['department', 'customRole']);

        if ($request->filled('role')) $query->where('role', $request->role);
        if ($request->filled('status')) $query->where('status', $request->status);
        if ($request->filled('search')) {
            $query->where(fn($q) => $q->where('name', 'like', '%' . $request->search . '%')
                ->orWhere('email', 'like', '%' . $request->search . '%'));
        }

        $users       = $query->latest()->paginate(20)->withQueryString();
        $departments = Department::where('organization_id', $this->orgId())->where('status', 'active')->get();
        $customRoles = OrganizationRole::where('organization_id', $this->orgId())
            ->where('status', 'active')
            ->orderBy('portal_role')
            ->orderBy('name')
            ->get();

        return view('admin.users.index', compact('users', 'departments', 'customRoles'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'          => 'required|string|max:255',
            'email'         => 'required|email|unique:users,email',
            'password'      => 'required|string|min:8',
            'role'          => 'required|in:admin,supplier,staff',
            'custom_role_id'=> 'nullable|exists:organization_roles,id',
            'department_id' => 'nullable|exists:departments,id',
            'phone'         => 'nullable|string|max:50',
            'employee_id'   => 'nullable|string|max:50',
            'job_title'     => 'nullable|string|max:100',
            'status'        => 'required|in:active,inactive',
        ]);

        $validated['organization_id'] = $this->orgId();
        $validated['password']        = Hash::make($validated['password']);
        $this->validateCustomRole($validated['custom_role_id'] ?? null, $validated['role']);

        User::create($validated);

        return back()->with('success', 'User created successfully.');
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
            'department_id' => 'nullable|exists:departments,id',
            'phone'         => 'nullable|string|max:50',
            'employee_id'   => 'nullable|string|max:50',
            'job_title'     => 'nullable|string|max:100',
            'status'        => 'required|in:active,inactive',
        ]);

        if ($validated['password']) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }
        $this->validateCustomRole($validated['custom_role_id'] ?? null, $validated['role']);

        $user->update($validated);
        return back()->with('success', 'User updated.');
    }

    public function destroy(User $user)
    {
        abort_if($user->organization_id !== $this->orgId(), 403);
        abort_if($user->id === auth()->id(), 403, 'Cannot delete your own account.');
        $user->delete();
        return back()->with('success', 'User deleted.');
    }

    private function validateCustomRole(?string $roleId, string $portalRole): void
    {
        if (!$roleId) {
            return;
        }

        $role = OrganizationRole::where('organization_id', $this->orgId())->findOrFail($roleId);
        abort_if($role->portal_role !== $portalRole, 422, 'Custom role portal type must match the user role.');
    }
}
