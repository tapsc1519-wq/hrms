<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\OrganizationRole;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class UserBulkImportController extends Controller
{
    private const HEADERS = [
        'name', 'email', 'password', 'role', 'permission_role',
        'department', 'phone', 'employee_id', 'job_title', 'status',
    ];

    private const SAMPLE = [
        'Amit Sharma', 'amit.sharma@example.com', '', 'staff', 'Employee',
        'IT', '9876543210', 'EMP-001', 'Support Executive', 'active',
    ];

    private const DEFAULT_PASSWORD = 'Welcome@123';

    public function index()
    {
        return view('admin.users.bulk-import.index', [
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
            'Content-Disposition' => 'attachment; filename="user_import_template.csv"',
        ]);
    }

    public function preview(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:5120',
        ]);

        $tempPath = $request->file('file')->storeAs('imports/users', Str::uuid() . '.csv');
        [$rows, $error] = $this->parseCsv(Storage::path($tempPath));

        if ($error) {
            Storage::delete($tempPath);
            return back()->withErrors(['file' => $error]);
        }

        $parsed = $this->validateRows($rows);
        $validCount = collect($parsed)->where('valid', true)->count();
        $invalidCount = count($parsed) - $validCount;

        return view('admin.users.bulk-import.preview', compact('parsed', 'validCount', 'invalidCount', 'tempPath'));
    }

    public function import(Request $request)
    {
        $request->validate([
            'temp_path' => 'required|string',
        ]);

        $fullPath = Storage::path($request->temp_path);
        if (!file_exists($fullPath)) {
            return redirect()->route('admin.users.bulk-import.index')
                ->withErrors(['file' => 'Import session expired. Please upload the CSV again.']);
        }

        [$rows, $error] = $this->parseCsv($fullPath);
        if ($error) {
            return redirect()->route('admin.users.bulk-import.index')->withErrors(['file' => $error]);
        }

        $parsed = $this->validateRows($rows);
        $imported = 0;
        $skipped = 0;

        foreach ($parsed as $item) {
            if (!$item['valid']) {
                $skipped++;
                continue;
            }

            $data = $item['data'];
            User::create([
                'organization_id' => $this->orgId(),
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password'] ?: self::DEFAULT_PASSWORD),
                'role' => $data['role'],
                'custom_role_id' => $data['custom_role_id'],
                'department_id' => $data['department_id'],
                'phone' => $data['phone'],
                'employee_id' => $data['employee_id'],
                'job_title' => $data['job_title'],
                'status' => $data['status'],
            ]);
            $imported++;
        }

        Storage::delete($request->temp_path);

        return redirect()->route('admin.users.index')
            ->with('success', "{$imported} user" . ($imported !== 1 ? 's' : '') . " imported" . ($skipped ? ", {$skipped} skipped." : '.'));
    }

    private function validateRows(array $rows): array
    {
        $departments = Department::where('organization_id', $this->orgId())
            ->pluck('id', 'name')
            ->mapWithKeys(fn($id, $name) => [strtolower(trim($name)) => $id]);

        $roles = OrganizationRole::where('organization_id', $this->orgId())
            ->where('status', 'active')
            ->get()
            ->mapWithKeys(fn($role) => [strtolower(trim($role->name)) => $role]);

        $existingEmails = User::pluck('email')->map(fn($email) => strtolower($email))->all();
        $seenEmails = [];
        $parsed = [];

        foreach ($rows as $index => $row) {
            $errors = [];
            $data = [
                'name' => trim($row['name'] ?? ''),
                'email' => strtolower(trim($row['email'] ?? '')),
                'password' => trim($row['password'] ?? ''),
                'role' => strtolower(trim($row['role'] ?? 'staff')),
                'custom_role_id' => null,
                'department_id' => null,
                'phone' => trim($row['phone'] ?? '') ?: null,
                'employee_id' => trim($row['employee_id'] ?? '') ?: null,
                'job_title' => trim($row['job_title'] ?? '') ?: null,
                'status' => strtolower(trim($row['status'] ?? 'active')),
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

            if (!in_array($data['role'], ['admin', 'staff', 'supplier'], true)) {
                $errors[] = 'Role must be admin, staff, or supplier.';
                $data['role'] = 'staff';
            }

            if ($data['password'] !== '' && strlen($data['password']) < 8) {
                $errors[] = 'Password must be at least 8 characters when provided.';
            }

            if (!in_array($data['status'], ['active', 'inactive'], true)) {
                $errors[] = 'Status must be active or inactive.';
                $data['status'] = 'active';
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
                } elseif ($roles[$roleName]->portal_role !== $data['role']) {
                    $errors[] = "Permission role '{$row['permission_role']}' is for {$roles[$roleName]->portal_role} users, not {$data['role']}.";
                } else {
                    $data['custom_role_id'] = $roles[$roleName]->id;
                    $data['_permission_role_name'] = $roles[$roleName]->name;
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
}
