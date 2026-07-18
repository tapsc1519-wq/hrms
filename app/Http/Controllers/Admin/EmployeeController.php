<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AssetAssignment;
use App\Models\Department;
use App\Models\EmployeeDocument;
use App\Models\EmployeeDocumentRequest;
use App\Models\EmployeeProfile;
use App\Models\Facility;
use App\Models\HrShift;
use App\Models\Location;
use App\Models\SoftwareAssignment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class EmployeeController extends Controller
{
    public function index(Request $request)
    {
        $query = EmployeeProfile::where('organization_id', $this->orgId())
            ->with(['user.department', 'manager', 'facility', 'location', 'shift']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('employee_code', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($u) use ($search) {
                        $u->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%")
                            ->orWhere('employee_id', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->filled('status')) {
            $query->where('employment_status', $request->status);
        }

        if ($request->filled('department_id')) {
            $query->whereHas('user', fn($q) => $q->where('department_id', $request->department_id));
        }

        $employees = $query->latest()->paginate(20)->withQueryString();
        $departments = Department::where('organization_id', $this->orgId())->orderBy('name')->get();
        $pendingProfiles = User::where('organization_id', $this->orgId())
            ->whereIn('role', ['admin', 'staff'])
            ->whereDoesntHave('employeeProfile')
            ->count();

        return view('admin.employees.index', compact('employees', 'departments', 'pendingProfiles'));
    }

    public function create()
    {
        return view('admin.employees.create', $this->formData());
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $profile = DB::transaction(function () use ($data) {
            $user = User::create([
                'organization_id' => $this->orgId(),
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password'] ?: 'Welcome@123'),
                'must_change_password' => true,
                'role' => $data['role'] ?? 'staff',
                'custom_role_id' => $data['custom_role_id'] ?? null,
                'department_id' => $data['department_id'] ?? null,
                'phone' => $data['phone'] ?? null,
                'employee_id' => $data['employee_code'] ?? null,
                'job_title' => $data['job_title'] ?? null,
                'status' => in_array($data['employment_status'], ['resigned', 'terminated'], true) ? 'inactive' : 'active',
            ]);

            $profile = EmployeeProfile::create($this->profilePayload($data, $user->id));
            $this->syncUserFields($user, $data, $profile);

            return $profile;
        });

        return redirect()->route('admin.employees.show', $profile)
            ->with('success', 'Employee and login user created successfully. Default password is Welcome@123 if no password was entered.');
    }

    public function show(EmployeeProfile $employee)
    {
        $this->authorizeEmployee($employee);

        $employee->load(['user.department', 'manager', 'facility', 'location', 'shift', 'documents.uploader', 'documentRequests.requester', 'documentRequests.reviewer', 'documentRequests.fulfilledDocument']);

        $assetAssignments = AssetAssignment::where('user_id', $employee->user_id)
            ->where('status', 'active')
            ->with(['asset.category', 'asset.location'])
            ->latest('assigned_date')
            ->get();

        $softwareAssignments = SoftwareAssignment::where('user_id', $employee->user_id)
            ->where('status', 'active')
            ->with(['license.software'])
            ->latest('assigned_date')
            ->get();

        return view('admin.employees.show', compact('employee', 'assetAssignments', 'softwareAssignments'));
    }

    public function storeDocument(Request $request, EmployeeProfile $employee)
    {
        $this->authorizeEmployee($employee);

        $data = $request->validate([
            'document_type' => ['required', Rule::in([
                'offer_letter',
                'id_proof',
                'address_proof',
                'education',
                'experience',
                'policy_acknowledgement',
                'other',
            ])],
            'title' => ['required', 'string', 'max:255'],
            'file' => ['required', 'file', 'max:10240', 'mimes:pdf,jpg,jpeg,png,doc,docx,xls,xlsx,txt,zip'],
            'expiry_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $file = $request->file('file');
        $path = $file->store("employee-documents/{$employee->id}", 'public');

        EmployeeDocument::create([
            'organization_id' => $this->orgId(),
            'employee_profile_id' => $employee->id,
            'uploaded_by' => auth()->id(),
            'document_type' => $data['document_type'],
            'title' => $data['title'],
            'original_name' => $file->getClientOriginalName(),
            'file_path' => $path,
            'mime_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
            'expiry_date' => $data['expiry_date'] ?? null,
            'notes' => $data['notes'] ?? null,
        ]);

        return back()->with('success', 'Employee document uploaded successfully.');
    }

    public function destroyDocument(EmployeeProfile $employee, EmployeeDocument $document)
    {
        $this->authorizeEmployee($employee);
        abort_if($document->employee_profile_id !== $employee->id || $document->organization_id !== $this->orgId(), 403);

        Storage::disk('public')->delete($document->file_path);
        $document->delete();

        return back()->with('success', 'Employee document deleted successfully.');
    }

    public function requestDocument(Request $request, EmployeeProfile $employee)
    {
        $this->authorizeEmployee($employee);

        $data = $request->validate([
            'document_type' => ['required', Rule::in([
                'offer_letter',
                'id_proof',
                'address_proof',
                'education',
                'experience',
                'policy_acknowledgement',
                'other',
            ])],
            'title' => ['required', 'string', 'max:255'],
            'due_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        EmployeeDocumentRequest::create([
            'organization_id' => $this->orgId(),
            'employee_profile_id' => $employee->id,
            'requested_by' => auth()->id(),
            'document_type' => $data['document_type'],
            'title' => $data['title'],
            'due_date' => $data['due_date'] ?? null,
            'notes' => $data['notes'] ?? null,
            'status' => 'pending',
        ]);

        return back()->with('success', 'Document request sent to employee.');
    }

    public function approveDocumentRequest(Request $request, EmployeeProfile $employee, EmployeeDocumentRequest $documentRequest)
    {
        $this->authorizeDocumentRequest($employee, $documentRequest);
        abort_if($documentRequest->status !== 'submitted', 422, 'Only submitted documents can be approved.');

        $data = $request->validate([
            'review_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $documentRequest->update([
            'status' => 'approved',
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
            'review_notes' => $data['review_notes'] ?? null,
        ]);

        return back()->with('success', 'Document approved successfully.');
    }

    public function rejectDocumentRequest(Request $request, EmployeeProfile $employee, EmployeeDocumentRequest $documentRequest)
    {
        $this->authorizeDocumentRequest($employee, $documentRequest);
        abort_if(!in_array($documentRequest->status, ['submitted', 'approved'], true), 422, 'Only submitted or approved documents can be rejected.');

        $data = $request->validate([
            'review_notes' => ['required', 'string', 'max:1000'],
        ]);

        $documentRequest->update([
            'status' => 'rejected',
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
            'review_notes' => $data['review_notes'],
        ]);

        return back()->with('success', 'Document rejected. Employee can upload the corrected document again.');
    }

    public function edit(EmployeeProfile $employee)
    {
        $this->authorizeEmployee($employee);

        return view('admin.employees.edit', array_merge($this->formData($employee), compact('employee')));
    }

    public function update(Request $request, EmployeeProfile $employee)
    {
        $this->authorizeEmployee($employee);

        $data = $this->validated($request, $employee);
        $employee->update($this->profilePayload($data, $employee->user_id, $employee));
        $this->syncUserFields($employee->user, $data, $employee);

        return redirect()->route('admin.employees.show', $employee)->with('success', 'Employee profile updated successfully.');
    }

    public function destroy(EmployeeProfile $employee)
    {
        $this->authorizeEmployee($employee);
        $employee->delete();

        return redirect()->route('admin.employees.index')->with('success', 'Employee profile removed. The login user was not deleted.');
    }

    private function formData(?EmployeeProfile $employee = null): array
    {
        $orgId = $this->orgId();

        return [
            'managers' => User::where('organization_id', $orgId)->whereIn('role', ['admin', 'staff'])->orderBy('name')->get(),
            'departments' => Department::where('organization_id', $orgId)->orderBy('name')->get(),
            'facilities' => Facility::where('organization_id', $orgId)->with('activeLocations')->orderBy('name')->get(),
            'locations' => Location::where('organization_id', $orgId)->orderBy('name')->get(),
            'shifts' => HrShift::where('organization_id', $orgId)->where('status', 'active')->orderBy('name')->get(),
            'customRoles' => \App\Models\OrganizationRole::where('organization_id', $orgId)->where('status', 'active')->orderBy('name')->get(),
        ];
    }

    private function validated(Request $request, ?EmployeeProfile $employee = null): array
    {
        $orgId = $this->orgId();

        return $request->validate([
            'name' => [$employee ? 'nullable' : 'required', 'string', 'max:255'],
            'email' => [
                $employee ? 'nullable' : 'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($employee?->user_id),
            ],
            'password' => [$employee ? 'nullable' : 'nullable', 'string', 'min:8'],
            'role' => [$employee ? 'nullable' : 'required', Rule::in(['staff', 'admin'])],
            'custom_role_id' => ['nullable', 'exists:organization_roles,id'],
            'employee_code' => [
                'nullable',
                'string',
                'max:60',
                Rule::unique('employee_profiles')->where('organization_id', $orgId)->ignore($employee?->id),
            ],
            'department_id' => ['nullable', 'exists:departments,id'],
            'job_title' => ['nullable', 'string', 'max:120'],
            'phone' => ['nullable', 'string', 'max:60'],
            'joining_date' => ['nullable', 'date'],
            'date_of_birth' => ['nullable', 'date', 'before:today'],
            'gender' => ['nullable', Rule::in(['male', 'female', 'other', 'prefer_not_to_say'])],
            'employment_type' => ['required', Rule::in(['full_time', 'part_time', 'contract', 'intern', 'consultant'])],
            'employment_status' => ['required', Rule::in(['active', 'probation', 'notice', 'resigned', 'terminated'])],
            'reporting_manager_id' => ['nullable', 'exists:users,id'],
            'facility_id' => ['nullable', 'exists:facilities,id'],
            'location_id' => ['nullable', 'exists:locations,id'],
            'shift_id' => ['nullable', 'exists:hr_shifts,id'],
            'personal_email' => ['nullable', 'email', 'max:255'],
            'bank_name' => ['nullable', 'string', 'max:120'],
            'bank_account_name' => ['nullable', 'string', 'max:255'],
            'bank_account_number' => ['nullable', 'string', 'max:40'],
            'ifsc_code' => ['nullable', 'string', 'max:20'],
            'pan_number' => ['nullable', 'string', 'max:20'],
            'uan_number' => ['nullable', 'string', 'max:30'],
            'pf_number' => ['nullable', 'string', 'max:40'],
            'esi_number' => ['nullable', 'string', 'max:40'],
            'emergency_contact_name' => ['nullable', 'string', 'max:255'],
            'emergency_contact_phone' => ['nullable', 'string', 'max:60'],
            'address' => ['nullable', 'string', 'max:1000'],
            'exit_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);
    }

    private function profilePayload(array $data, int $userId, ?EmployeeProfile $employee = null): array
    {
        $this->assertOrgReferences($data, $userId);

        return [
            'organization_id' => $this->orgId(),
            'user_id' => $employee?->user_id ?? $userId,
            'reporting_manager_id' => $data['reporting_manager_id'] ?? null,
            'facility_id' => $data['facility_id'] ?? null,
            'location_id' => $data['location_id'] ?? null,
            'shift_id' => $data['shift_id'] ?? null,
            'employee_code' => $data['employee_code'] ?? null,
            'joining_date' => $data['joining_date'] ?? null,
            'date_of_birth' => $data['date_of_birth'] ?? null,
            'gender' => $data['gender'] ?? null,
            'employment_type' => $data['employment_type'],
            'employment_status' => $data['employment_status'],
            'personal_email' => $data['personal_email'] ?? null,
            'bank_name' => $data['bank_name'] ?? null,
            'bank_account_name' => $data['bank_account_name'] ?? null,
            'bank_account_number' => $data['bank_account_number'] ?? null,
            'ifsc_code' => isset($data['ifsc_code']) ? strtoupper($data['ifsc_code']) : null,
            'pan_number' => isset($data['pan_number']) ? strtoupper($data['pan_number']) : null,
            'uan_number' => $data['uan_number'] ?? null,
            'pf_number' => $data['pf_number'] ?? null,
            'esi_number' => $data['esi_number'] ?? null,
            'emergency_contact_name' => $data['emergency_contact_name'] ?? null,
            'emergency_contact_phone' => $data['emergency_contact_phone'] ?? null,
            'address' => $data['address'] ?? null,
            'exit_date' => $data['exit_date'] ?? null,
            'notes' => $data['notes'] ?? null,
        ];
    }

    private function syncUserFields(User $user, array $data, EmployeeProfile $profile): void
    {
        $payload = [
            'name' => $data['name'] ?? $user->name,
            'email' => $data['email'] ?? $user->email,
            'role' => $data['role'] ?? $user->role,
            'custom_role_id' => $data['custom_role_id'] ?? null,
            'employee_id' => $profile->employee_code,
            'department_id' => $data['department_id'] ?? null,
            'job_title' => $data['job_title'] ?? null,
            'phone' => $data['phone'] ?? null,
            'status' => in_array($profile->employment_status, ['resigned', 'terminated'], true) ? 'inactive' : 'active',
        ];

        if (!empty($data['password'])) {
            $payload['password'] = Hash::make($data['password']);
        }

        $user->update($payload);
    }

    private function orgUser(int $id): User
    {
        return User::where('organization_id', $this->orgId())->whereKey($id)->firstOrFail();
    }

    private function assertOrgReferences(array $data, int $userId): void
    {
        $this->orgUser($userId);

        if (!empty($data['custom_role_id'])) {
            $role = \App\Models\OrganizationRole::where('organization_id', $this->orgId())->whereKey($data['custom_role_id'])->first();
            abort_if(!$role, 403);
            abort_if(isset($data['role']) && $role->portal_role !== $data['role'], 422, 'Selected permission role does not match the portal role.');
        }

        foreach ([
            'reporting_manager_id' => User::class,
            'department_id' => Department::class,
            'facility_id' => Facility::class,
            'location_id' => Location::class,
            'shift_id' => HrShift::class,
        ] as $field => $model) {
            if (!empty($data[$field])) {
                abort_if(!$model::where('organization_id', $this->orgId())->whereKey($data[$field])->exists(), 403);
            }
        }
    }

    private function authorizeEmployee(EmployeeProfile $employee): void
    {
        abort_if($employee->organization_id !== $this->orgId(), 403);
    }

    private function authorizeDocumentRequest(EmployeeProfile $employee, EmployeeDocumentRequest $documentRequest): void
    {
        $this->authorizeEmployee($employee);
        abort_if($documentRequest->organization_id !== $this->orgId(), 403);
        abort_if($documentRequest->employee_profile_id !== $employee->id, 403);
    }
}
