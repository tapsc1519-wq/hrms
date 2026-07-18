<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\User;
use App\Support\UserInvitationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::with('organization');

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('email', 'like', '%' . $request->search . '%');
            });
        }

        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $users = $query->latest()->paginate(20)->withQueryString();

        return view('super-admin.users.index', compact('users'));
    }

    public function create()
    {
        $organizations = Organization::where('status', 'active')->orderBy('name')->get();
        $selectedOrganizationId = request('organization_id');
        $selectedRole = request('role');

        return view('super-admin.users.create', compact('organizations', 'selectedOrganizationId', 'selectedRole'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'organization_id' => 'nullable|exists:organizations,id',
            'name'            => 'required|string|max:255',
            'email'           => 'required|email|unique:users,email',
            'password'        => 'required|string|min:8|confirmed',
            'role'            => 'required|in:super_admin,admin,supplier,staff',
            'phone'           => 'nullable|string|max:50',
            'employee_id'     => 'nullable|string|max:50',
            'job_title'       => 'nullable|string|max:100',
            'status'          => 'required|in:active,inactive',
            'onboarding_redirect' => 'nullable|boolean',
        ]);

        if (($validated['role'] ?? null) === 'super_admin' && !empty($validated['organization_id'])) {
            return back()
                ->withInput()
                ->with('error', 'Super Admin is only for Niyantron platform accounts. Do not assign an organization to Super Admin.');
        }

        if (($validated['role'] ?? null) !== 'super_admin' && empty($validated['organization_id'])) {
            return back()
                ->withInput()
                ->with('error', 'Customer Admin, Staff, and Supplier accounts must be assigned to an organization.');
        }

        $validated['password'] = Hash::make($validated['password']);
        $redirectToOnboarding = (bool) ($validated['onboarding_redirect'] ?? false);
        unset($validated['onboarding_redirect']);
        if ($redirectToOnboarding && ($validated['role'] ?? null) === 'admin') {
            $validated['must_change_password'] = true;
        }
        if (($validated['role'] ?? null) === 'super_admin') {
            $validated['organization_id'] = null;
        }

        $user = User::create($validated);

        if ($redirectToOnboarding && $user->organization_id && $user->role === 'admin') {
            return redirect()->route('super-admin.organizations.edit', $user->organization_id)
                ->with('success', 'First admin account created. Continue handover and share credentials.');
        }

        return redirect()->route('super-admin.users.index')
            ->with('success', 'User created successfully.');
    }

    public function edit(User $user)
    {
        $organizations = Organization::where('status', 'active')->orderBy('name')->get();
        return view('super-admin.users.edit', compact('user', 'organizations'));
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'organization_id' => 'nullable|exists:organizations,id',
            'name'            => 'required|string|max:255',
            'email'           => 'required|email|unique:users,email,' . $user->id,
            'password'        => 'nullable|string|min:8|confirmed',
            'role'            => 'required|in:super_admin,admin,supplier,staff',
            'phone'           => 'nullable|string|max:50',
            'employee_id'     => 'nullable|string|max:50',
            'job_title'       => 'nullable|string|max:100',
            'status'          => 'required|in:active,inactive',
        ]);

        if (($validated['role'] ?? null) === 'super_admin' && !empty($validated['organization_id'])) {
            return back()
                ->withInput()
                ->with('error', 'Super Admin is only for Niyantron platform accounts. Do not assign an organization to Super Admin.');
        }

        if (($validated['role'] ?? null) !== 'super_admin' && empty($validated['organization_id'])) {
            return back()
                ->withInput()
                ->with('error', 'Customer Admin, Staff, and Supplier accounts must be assigned to an organization.');
        }

        if (($validated['role'] ?? null) === 'super_admin') {
            $validated['organization_id'] = null;
        }

        if (isset($validated['password']) && $validated['password']) {
            $validated['password'] = Hash::make($validated['password']);
            $validated['must_change_password'] = true;
            $validated['invitation_sent_at'] = now();
            $validated['invitation_accepted_at'] = null;
        } else {
            unset($validated['password']);
        }

        $user->update($validated);

        return redirect()->route('super-admin.users.index')
            ->with('success', 'User updated successfully.');
    }

    public function invite(Request $request, User $user)
    {
        abort_if($user->id === auth()->id(), 422, 'Use change password for your own account.');
        abort_if($user->status !== 'active', 422, 'Only active users can be invited.');

        $validated = $request->validate([
            'temporary_password' => ['nullable', 'string', 'min:8', 'max:80'],
        ]);

        $invite = UserInvitationService::prepare($user, $validated['temporary_password'] ?? null);

        return back()
            ->with('success', 'Invitation prepared. Copy the message and share it with the user.')
            ->with('invite_pack', $invite);
    }

    public function destroy(User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'You cannot delete your own account.');
        }
        $user->delete();
        return redirect()->route('super-admin.users.index')
            ->with('success', 'User deleted successfully.');
    }
}
