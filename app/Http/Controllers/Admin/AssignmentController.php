<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\AssetAssignment;
use App\Models\AssetHandoverRequest;
use App\Models\Department;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AssignmentController extends Controller
{


    public function index(Request $request)
    {
        $query = AssetAssignment::whereHas('asset', fn($q) => $q->where('organization_id', $this->orgId()))
            ->with(['asset', 'user', 'department', 'assignedBy']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $query->whereHas('asset', fn($q) => $q->where('name', 'like', '%' . $request->search . '%')
                ->orWhere('asset_tag', 'like', '%' . $request->search . '%'))
                ->orWhereHas('user', fn($q) => $q->where('name', 'like', '%' . $request->search . '%'));
        }

        $assignments = $query->latest()->paginate(20)->withQueryString();
        $pendingHandovers = AssetHandoverRequest::whereHas('asset', fn($q) => $q->where('organization_id', $this->orgId()))
            ->whereIn('status', ['pending', 'pending_admin'])
            ->with(['asset.category', 'assignment.user', 'fromUser.department', 'toUser.department'])
            ->latest()
            ->get();
        $staffUsers = User::where('organization_id', $this->orgId())
            ->with('department')
            ->where('role', 'staff')
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        return view('admin.assignments.index', compact('assignments', 'staffUsers', 'pendingHandovers'));
    }

    public function create()
    {
        $assets      = Asset::where('organization_id', $this->orgId())->where('status', 'available')->orderBy('name')->get();
        $users       = User::where('organization_id', $this->orgId())->where('status', 'active')->orderBy('name')->get();
        $departments = Department::where('organization_id', $this->orgId())->where('status', 'active')->orderBy('name')->get();

        return view('admin.assignments.create', compact('assets', 'users', 'departments'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'asset_id'             => 'required|exists:assets,id',
            'user_id'              => 'nullable|exists:users,id',
            'department_id'        => 'nullable|exists:departments,id',
            'assigned_date'        => 'required|date',
            'expected_return_date' => 'nullable|date|after:assigned_date',
            'condition_out'        => 'required|in:excellent,good,fair,poor',
            'purpose'              => 'nullable|string',
            'notes'                => 'nullable|string',
        ]);

        $asset = Asset::findOrFail($validated['asset_id']);
        abort_if($asset->organization_id !== $this->orgId(), 403);

        $validated['assigned_by'] = auth()->id();
        $validated['status']      = 'active';

        AssetAssignment::create($validated);
        $asset->update(['status' => 'assigned']);

        return redirect()->route('admin.assignments.index')
            ->with('success', 'Asset assigned successfully.');
    }

    // Bulk CSV assignment

    public function bulk()
    {
        return view('admin.assignments.bulk');
    }

    public function bulkTemplate()
    {
        $rows = [
            ['asset_tag', 'employee_code', 'assigned_date', 'expected_return_date', 'condition_out', 'purpose', 'notes'],
            ['LAP-001', 'EMP001', now()->format('Y-m-d'), '', 'good', 'Work laptop', 'Optional notes'],
        ];

        return response()->streamDownload(function () use ($rows) {
            $handle = fopen('php://output', 'w');
            foreach ($rows as $row) {
                fputcsv($handle, $row);
            }
            fclose($handle);
        }, 'bulk-asset-assignments-template.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }

    public function storeBulk(Request $request)
    {
        $request->validate([
            'csv_file' => ['required', 'file', 'mimes:csv,txt', 'max:5120'],
        ]);

        $handle = fopen($request->file('csv_file')->getRealPath(), 'r');
        if (!$handle) {
            return back()->withErrors(['csv_file' => 'Unable to read the uploaded CSV file.']);
        }

        $header = fgetcsv($handle);
        if (!$header) {
            fclose($handle);
            return back()->withErrors(['csv_file' => 'The CSV file is empty.']);
        }

        $header = array_map(fn($value) => strtolower(trim((string) $value)), $header);
        $required = ['asset_tag', 'employee_code', 'assigned_date'];
        $missing = array_diff($required, $header);

        if ($missing) {
            fclose($handle);
            return back()->withErrors(['csv_file' => 'Missing required columns: ' . implode(', ', $missing) . '.']);
        }

        $rows = [];
        $errors = [];
        $seenAssets = [];
        $line = 1;
        $allowedConditions = ['excellent', 'good', 'fair', 'poor'];

        while (($data = fgetcsv($handle)) !== false) {
            $line++;

            if (count(array_filter($data, fn($value) => trim((string) $value) !== '')) === 0) {
                continue;
            }

            $row = array_combine($header, array_slice(array_pad($data, count($header), ''), 0, count($header)));
            $assetKey = trim((string) ($row['asset_tag'] ?? ''));
            $employeeCode = trim((string) ($row['employee_code'] ?? ''));
            $assignedDate = trim((string) ($row['assigned_date'] ?? ''));
            $expectedReturnDate = trim((string) ($row['expected_return_date'] ?? ''));
            $conditionOut = strtolower(trim((string) ($row['condition_out'] ?? 'good')));

            if ($assetKey === '') {
                $errors[] = "Line {$line}: asset_tag is required.";
                continue;
            }

            if (isset($seenAssets[$assetKey])) {
                $errors[] = "Line {$line}: duplicate asset_tag '{$assetKey}' in this CSV.";
                continue;
            }
            $seenAssets[$assetKey] = true;

            if ($employeeCode === '') {
                $errors[] = "Line {$line}: employee_code is required.";
                continue;
            }

            if ($assignedDate === '' || strtotime($assignedDate) === false) {
                $errors[] = "Line {$line}: assigned_date must be a valid date.";
                continue;
            }

            if ($expectedReturnDate !== '' && strtotime($expectedReturnDate) === false) {
                $errors[] = "Line {$line}: expected_return_date must be a valid date.";
                continue;
            }

            if ($expectedReturnDate !== '' && strtotime($expectedReturnDate) <= strtotime($assignedDate)) {
                $errors[] = "Line {$line}: expected_return_date must be after assigned_date.";
                continue;
            }

            if (!in_array($conditionOut, $allowedConditions, true)) {
                $errors[] = "Line {$line}: condition_out must be excellent, good, fair, or poor.";
                continue;
            }

            $asset = Asset::where('organization_id', $this->orgId())
                ->where('status', 'available')
                ->where('asset_tag', $assetKey)
                ->first();

            if (!$asset) {
                $errors[] = "Line {$line}: available asset '{$assetKey}' was not found.";
                continue;
            }

            $user = User::where('organization_id', $this->orgId())
                ->where('status', 'active')
                ->where('employee_id', $employeeCode)
                ->first();

            if (!$user) {
                $errors[] = "Line {$line}: active employee code '{$employeeCode}' was not found.";
                continue;
            }

            $rows[] = [
                'asset' => $asset,
                'user' => $user,
                'assigned_date' => date('Y-m-d', strtotime($assignedDate)),
                'expected_return_date' => $expectedReturnDate !== '' ? date('Y-m-d', strtotime($expectedReturnDate)) : null,
                'condition_out' => $conditionOut,
                'purpose' => trim((string) ($row['purpose'] ?? '')) ?: null,
                'notes' => trim((string) ($row['notes'] ?? '')) ?: null,
            ];
        }

        fclose($handle);

        if (!$rows && !$errors) {
            return back()->withErrors(['csv_file' => 'The CSV file does not contain any assignment rows.']);
        }

        if ($errors) {
            return back()->withErrors(['csv_file' => implode(' ', array_slice($errors, 0, 8))]);
        }

        DB::transaction(function () use ($rows) {
            foreach ($rows as $row) {
                AssetAssignment::create([
                    'asset_id' => $row['asset']->id,
                    'user_id' => $row['user']->id,
                    'department_id' => $row['user']->department_id,
                    'assigned_by' => auth()->id(),
                    'assigned_date' => $row['assigned_date'],
                    'expected_return_date' => $row['expected_return_date'],
                    'condition_out' => $row['condition_out'],
                    'purpose' => $row['purpose'],
                    'notes' => $row['notes'],
                    'status' => 'active',
                ]);

                $row['asset']->update(['status' => 'assigned']);
            }
        });

        return redirect()->route('admin.assignments.index')
            ->with('success', count($rows) . ' asset assignment' . (count($rows) !== 1 ? 's' : '') . ' imported successfully.');
    }

    // RETURN ───────────────────────────────────────────────────────────────

    public function returnAsset(Request $request, AssetAssignment $assignment)
    {
        abort_if($assignment->asset->organization_id !== $this->orgId(), 403);

        $request->validate([
            'actual_return_date' => 'required|date',
            'condition_in'       => 'required|in:excellent,good,fair,poor',
            'notes'              => 'nullable|string',
        ]);

        $assignment->update([
            'status'             => 'returned',
            'actual_return_date' => $request->actual_return_date,
            'condition_in'       => $request->condition_in,
            'notes'              => $request->notes,
        ]);

        $assignment->asset->update(['status' => 'available']);

        return back()->with('success', 'Asset returned successfully.');
    }

    public function handover(Request $request, AssetAssignment $assignment)
    {
        abort_if($assignment->asset->organization_id !== $this->orgId(), 403);
        abort_if($assignment->status !== 'active', 422, 'Only active assignments can be handed over.');

        $validated = $request->validate([
            'handover_type' => 'required|in:staff,it_admin',
            'new_user_id'   => 'required_if:handover_type,staff|nullable|exists:users,id',
            'handover_date' => 'required|date',
            'condition_in'  => 'required|in:excellent,good,fair,poor',
            'notes'         => 'nullable|string',
        ]);

        $newUser = null;
        if ($validated['handover_type'] === 'staff') {
            $newUser = User::where('organization_id', $this->orgId())
                ->where('role', 'staff')
                ->where('status', 'active')
                ->findOrFail($validated['new_user_id']);

            if ($assignment->user_id && $assignment->user_id === $newUser->id) {
                return back()->withErrors(['new_user_id' => 'Please select a different staff member for handover.']);
            }
        }

        if ($newUser) {
            $pendingExists = AssetHandoverRequest::where('asset_assignment_id', $assignment->id)
                ->whereIn('status', ['pending', 'pending_admin', 'approved'])
                ->exists();

            if ($pendingExists) {
                return back()->withErrors(['new_user_id' => 'A pending handover request already exists for this asset.']);
            }

            AssetHandoverRequest::create([
                'asset_assignment_id' => $assignment->id,
                'asset_id'            => $assignment->asset_id,
                'from_user_id'        => $assignment->user_id,
                'to_user_id'          => $newUser->id,
                'handover_date'       => $validated['handover_date'],
                'condition_in'        => $validated['condition_in'],
                'notes'               => $validated['notes'],
                'approved_by'         => auth()->id(),
                'approved_at'         => now(),
                'approval_notes'      => 'Created and approved by Admin/IT.',
                'status'              => 'approved',
            ]);

            return back()->with('success', 'Handover request sent to ' . $newUser->name . '. The asset will transfer after recipient acceptance.');
        }

        DB::transaction(function () use ($assignment, $validated) {
            $assignment->update([
                'status'             => 'returned',
                'actual_return_date' => $validated['handover_date'],
                'condition_in'       => $validated['condition_in'],
                'notes'              => $validated['notes'],
            ]);

            $assignment->asset->update(['status' => 'available']);

            AssetHandoverRequest::where('asset_assignment_id', $assignment->id)
                ->whereIn('status', ['pending', 'pending_admin', 'approved'])
                ->update(['status' => 'cancelled', 'responded_at' => now()]);
        });

        return back()->with('success', 'Asset handed over to IT/Admin successfully.');
    }

    public function approveHandover(Request $request, AssetHandoverRequest $handover)
    {
        abort_if($handover->asset->organization_id !== $this->orgId(), 403);
        abort_unless(in_array($handover->status, ['pending', 'pending_admin'], true), 422, 'This handover request is not pending IT approval.');
        abort_if($handover->assignment->status !== 'active', 422, 'The original assignment is no longer active.');

        $validated = $request->validate([
            'approval_notes' => 'nullable|string|max:2000',
        ]);

        $handover->update([
            'status' => 'approved',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
            'approval_notes' => $validated['approval_notes'] ?? null,
        ]);

        return back()->with('success', 'Handover approved. Recipient can now accept or reject it.');
    }

    public function rejectHandover(Request $request, AssetHandoverRequest $handover)
    {
        abort_if($handover->asset->organization_id !== $this->orgId(), 403);
        abort_unless(in_array($handover->status, ['pending', 'pending_admin'], true), 422, 'This handover request is not pending IT approval.');

        $validated = $request->validate([
            'approval_notes' => 'nullable|string|max:2000',
        ]);

        $handover->update([
            'status' => 'admin_rejected',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
            'approval_notes' => $validated['approval_notes'] ?? null,
            'responded_at' => now(),
        ]);

        return back()->with('success', 'Handover request rejected. The asset remains with the current employee.');
    }
}
