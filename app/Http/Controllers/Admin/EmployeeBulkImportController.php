<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\EmployeeProfile;
use App\Models\Facility;
use App\Models\Location;
use App\Models\OrganizationRole;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class EmployeeBulkImportController extends Controller
{
    private const HEADERS = [
        'name',
        'email',
        'password',
        'employee_code',
        'portal_role',
        'permission_role',
        'department',
        'job_title',
        'phone',
        'joining_date',
        'employment_type',
        'employment_status',
        'reporting_manager_email',
        'facility',
        'work_location',
        'personal_email',
        'emergency_contact_name',
        'emergency_contact_phone',
        'address',
    ];

    private const SAMPLE = [
        'Amit Sharma',
        'amit.sharma@example.com',
        '',
        'EMP-1001',
        'staff',
        'Employee',
        'IT',
        'Support Executive',
        '9876543210',
        '05-06-2026',
        'full_time',
        'active',
        'admin@techcorp.com',
        'Head Office NCR',
        'IT Room',
        'amit.personal@example.com',
        'Ravi Sharma',
        '9999999999',
        'Delhi, India',
    ];

    private const DEFAULT_PASSWORD = 'Welcome@123';

    public function index()
    {
        return view('admin.employees.bulk-import.index', [
            'headers' => self::HEADERS,
            'defaultPassword' => self::DEFAULT_PASSWORD,
        ]);
    }

    public function template()
    {
        $output = fopen('php://temp', 'r+');
        fputcsv($output, self::HEADERS);
        fputcsv($output, self::SAMPLE);
        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="employee_import_template.csv"',
        ]);
    }

    public function preview(Request $request)
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:5120'],
        ]);

        $tempPath = $request->file('file')->storeAs('imports/employees', Str::uuid() . '.csv');
        [$rows, $error] = $this->parseCsv(Storage::path($tempPath));

        if ($error) {
            Storage::delete($tempPath);
            return back()->withErrors(['file' => $error]);
        }

        $parsed = $this->validateRows($rows);
        $validCount = collect($parsed)->where('valid', true)->count();
        $invalidCount = count($parsed) - $validCount;

        return view('admin.employees.bulk-import.preview', compact('parsed', 'validCount', 'invalidCount', 'tempPath'));
    }

    public function import(Request $request)
    {
        $request->validate([
            'temp_path' => ['required', 'string'],
        ]);

        $fullPath = Storage::path($request->temp_path);
        if (!file_exists($fullPath)) {
            return redirect()->route('admin.employees.bulk-import.index')
                ->withErrors(['file' => 'Import session expired. Please upload the CSV again.']);
        }

        [$rows, $error] = $this->parseCsv($fullPath);
        if ($error) {
            return redirect()->route('admin.employees.bulk-import.index')->withErrors(['file' => $error]);
        }

        $parsed = $this->validateRows($rows);
        $imported = 0;
        $skipped = 0;

        DB::transaction(function () use ($parsed, &$imported, &$skipped) {
            foreach ($parsed as $item) {
                if (!$item['valid']) {
                    $skipped++;
                    continue;
                }

                $data = $item['data'];
                $user = User::create([
                    'organization_id' => $this->orgId(),
                    'name' => $data['name'],
                    'email' => $data['email'],
                    'password' => Hash::make($data['password'] ?: self::DEFAULT_PASSWORD),
                    'role' => $data['portal_role'],
                    'custom_role_id' => $data['custom_role_id'],
                    'department_id' => $data['department_id'],
                    'phone' => $data['phone'],
                    'employee_id' => $data['employee_code'],
                    'job_title' => $data['job_title'],
                    'status' => in_array($data['employment_status'], ['resigned', 'terminated'], true) ? 'inactive' : 'active',
                ]);

                EmployeeProfile::create([
                    'organization_id' => $this->orgId(),
                    'user_id' => $user->id,
                    'reporting_manager_id' => $data['reporting_manager_id'],
                    'facility_id' => $data['facility_id'],
                    'location_id' => $data['location_id'],
                    'employee_code' => $data['employee_code'],
                    'joining_date' => $data['joining_date'],
                    'employment_type' => $data['employment_type'],
                    'employment_status' => $data['employment_status'],
                    'personal_email' => $data['personal_email'],
                    'emergency_contact_name' => $data['emergency_contact_name'],
                    'emergency_contact_phone' => $data['emergency_contact_phone'],
                    'address' => $data['address'],
                ]);

                $imported++;
            }
        });

        Storage::delete($request->temp_path);

        return redirect()->route('admin.employees.index')
            ->with('success', "{$imported} employee" . ($imported !== 1 ? 's' : '') . " imported" . ($skipped ? ", {$skipped} skipped." : '.'));
    }

    private function validateRows(array $rows): array
    {
        $departments = Department::where('organization_id', $this->orgId())
            ->pluck('id', 'name')
            ->mapWithKeys(fn($id, $name) => [strtolower(trim($name)) => $id]);

        $facilities = Facility::where('organization_id', $this->orgId())
            ->pluck('id', 'name')
            ->mapWithKeys(fn($id, $name) => [strtolower(trim($name)) => $id]);

        $locations = Location::where('organization_id', $this->orgId())
            ->get()
            ->mapWithKeys(fn($location) => [strtolower(trim($location->name)) => $location]);

        $roles = OrganizationRole::where('organization_id', $this->orgId())
            ->where('status', 'active')
            ->get()
            ->mapWithKeys(fn($role) => [strtolower(trim($role->name)) => $role]);

        $managers = User::where('organization_id', $this->orgId())
            ->whereIn('role', ['admin', 'staff'])
            ->pluck('id', 'email')
            ->mapWithKeys(fn($id, $email) => [strtolower(trim($email)) => $id]);

        $existingEmails = User::pluck('email')->map(fn($email) => strtolower($email))->all();
        $existingCodes = EmployeeProfile::where('organization_id', $this->orgId())->pluck('employee_code')->filter()->map(fn($code) => strtolower($code))->all();
        $seenEmails = [];
        $seenCodes = [];
        $parsed = [];

        foreach ($rows as $index => $row) {
            $errors = [];
            $employmentType = strtolower(trim($row['employment_type'] ?? 'full_time')) ?: 'full_time';
            $employmentStatus = strtolower(trim($row['employment_status'] ?? 'active')) ?: 'active';
            $portalRole = strtolower(trim($row['portal_role'] ?? 'staff')) ?: 'staff';
            $employeeCode = trim($row['employee_code'] ?? '') ?: null;

            $data = [
                'name' => trim($row['name'] ?? ''),
                'email' => strtolower(trim($row['email'] ?? '')),
                'password' => trim($row['password'] ?? ''),
                'employee_code' => $employeeCode,
                'portal_role' => $portalRole,
                'custom_role_id' => null,
                'department_id' => null,
                'job_title' => trim($row['job_title'] ?? '') ?: null,
                'phone' => trim($row['phone'] ?? '') ?: null,
                'joining_date' => $this->normalizeDate($row['joining_date'] ?? ''),
                'employment_type' => $employmentType,
                'employment_status' => $employmentStatus,
                'reporting_manager_id' => null,
                'facility_id' => null,
                'location_id' => null,
                'personal_email' => trim($row['personal_email'] ?? '') ?: null,
                'emergency_contact_name' => trim($row['emergency_contact_name'] ?? '') ?: null,
                'emergency_contact_phone' => trim($row['emergency_contact_phone'] ?? '') ?: null,
                'address' => trim($row['address'] ?? '') ?: null,
            ];

            if ($data['name'] === '') {
                $errors[] = 'Name is required.';
            }

            if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'Valid email is required.';
            } elseif (in_array($data['email'], $existingEmails, true)) {
                $errors[] = 'Email already exists.';
            } elseif (in_array($data['email'], $seenEmails, true)) {
                $errors[] = 'Duplicate email in CSV.';
            }
            $seenEmails[] = $data['email'];

            if ($employeeCode && in_array(strtolower($employeeCode), $existingCodes, true)) {
                $errors[] = 'Employee code already exists.';
            } elseif ($employeeCode && in_array(strtolower($employeeCode), $seenCodes, true)) {
                $errors[] = 'Duplicate employee code in CSV.';
            }
            if ($employeeCode) {
                $seenCodes[] = strtolower($employeeCode);
            }

            if (!in_array($portalRole, ['staff', 'admin'], true)) {
                $errors[] = 'Portal role must be staff or admin.';
                $data['portal_role'] = 'staff';
            }

            if ($data['password'] !== '' && strlen($data['password']) < 8) {
                $errors[] = 'Password must be at least 8 characters when provided.';
            }

            if (!in_array($employmentType, ['full_time', 'part_time', 'contract', 'intern', 'consultant'], true)) {
                $errors[] = 'Employment type must be full_time, part_time, contract, intern, or consultant.';
                $data['employment_type'] = 'full_time';
            }

            if (!in_array($employmentStatus, ['active', 'probation', 'notice', 'resigned', 'terminated'], true)) {
                $errors[] = 'Employment status must be active, probation, notice, resigned, or terminated.';
                $data['employment_status'] = 'active';
            }

            if (trim($row['joining_date'] ?? '') !== '' && !$data['joining_date']) {
                $errors[] = 'Joining date must be DD-MM-YYYY or YYYY-MM-DD.';
            }

            $departmentName = strtolower(trim($row['department'] ?? ''));
            if ($departmentName !== '') {
                if (isset($departments[$departmentName])) {
                    $data['department_id'] = $departments[$departmentName];
                    $data['_department_name'] = trim($row['department']);
                } else {
                    $errors[] = "Department '{$row['department']}' not found.";
                }
            }

            $roleName = strtolower(trim($row['permission_role'] ?? ''));
            if ($roleName !== '') {
                if (!isset($roles[$roleName])) {
                    $errors[] = "Permission role '{$row['permission_role']}' not found.";
                } elseif ($roles[$roleName]->portal_role !== $data['portal_role']) {
                    $errors[] = "Permission role '{$row['permission_role']}' is for {$roles[$roleName]->portal_role} users, not {$data['portal_role']}.";
                } else {
                    $data['custom_role_id'] = $roles[$roleName]->id;
                    $data['_permission_role_name'] = $roles[$roleName]->name;
                }
            }

            $managerEmail = strtolower(trim($row['reporting_manager_email'] ?? ''));
            if ($managerEmail !== '') {
                if (isset($managers[$managerEmail])) {
                    $data['reporting_manager_id'] = $managers[$managerEmail];
                    $data['_manager_email'] = $managerEmail;
                } else {
                    $errors[] = "Reporting manager '{$row['reporting_manager_email']}' not found.";
                }
            }

            $facilityName = strtolower(trim($row['facility'] ?? ''));
            if ($facilityName !== '') {
                if (isset($facilities[$facilityName])) {
                    $data['facility_id'] = $facilities[$facilityName];
                    $data['_facility_name'] = trim($row['facility']);
                } else {
                    $errors[] = "Facility '{$row['facility']}' not found.";
                }
            }

            $locationName = strtolower(trim($row['work_location'] ?? ''));
            if ($locationName !== '') {
                if (isset($locations[$locationName])) {
                    $location = $locations[$locationName];
                    $data['location_id'] = $location->id;
                    $data['_location_name'] = $location->name;
                    if ($data['facility_id'] && $location->facility_id && $location->facility_id !== $data['facility_id']) {
                        $errors[] = "Work location '{$row['work_location']}' does not belong to the selected facility.";
                    }
                } else {
                    $errors[] = "Work location '{$row['work_location']}' not found.";
                }
            }

            $parsed[] = [
                'row' => $index + 2,
                'data' => $data,
                'raw' => $row,
                'errors' => $errors,
                'valid' => empty($errors),
            ];
        }

        return $parsed;
    }

    private function parseCsv(string $path): array
    {
        if (!file_exists($path)) {
            return [[], 'File not found.'];
        }

        $handle = fopen($path, 'r');
        if (!$handle) {
            return [[], 'Cannot read file.'];
        }

        $headers = fgetcsv($handle);
        if (!$headers) {
            fclose($handle);
            return [[], 'CSV file is empty.'];
        }

        $headers = array_map(fn($header) => $this->normalizeHeader((string) $header), $headers);
        if (!in_array('name', $headers, true) || !in_array('email', $headers, true)) {
            fclose($handle);
            return [[], "Required columns 'name' and 'email' were not found."];
        }

        $rows = [];
        while (($values = fgetcsv($handle)) !== false) {
            if (count(array_filter($values, fn($value) => trim((string) $value) !== '')) === 0) {
                continue;
            }

            $row = [];
            foreach ($headers as $index => $header) {
                $row[$header] = trim($values[$index] ?? '');
            }
            $rows[] = $row;
        }
        fclose($handle);

        if (empty($rows)) {
            return [[], 'CSV contains no data rows.'];
        }

        return [$rows, null];
    }

    private function normalizeHeader(string $value): string
    {
        $value = strtolower(trim($value));
        $value = preg_replace('/[^a-z0-9]+/', '_', $value);
        return trim((string) $value, '_');
    }

    private function normalizeDate(string $value): ?string
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }

        foreach (['d-m-Y', 'Y-m-d', 'd/m/Y', 'Y/m/d'] as $format) {
            $date = \DateTime::createFromFormat($format, $value);
            if ($date && $date->format($format) === $value) {
                return $date->format('Y-m-d');
            }
        }

        return null;
    }
}
