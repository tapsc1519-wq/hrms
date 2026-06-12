<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\AssetCategory;
use App\Models\Location;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class BulkImportController extends Controller
{
    private const BASE_HEADERS = [
        'name', 'asset_tag', 'serial_number', 'brand', 'model',
        'status', 'condition', 'purchase_date', 'purchase_price',
        'warranty_expiry_date', 'warranty_terms', 'supplier', 'location',
        'description', 'notes',
    ];

    private const BASE_SAMPLE = [
        'Dell Latitude 5530', 'ASSET-001', 'SN123456', 'Dell', 'Latitude 5530',
        'available', 'good', '15-01-2024', '75000',
        '15-01-2027', '3-year onsite', 'Dell Technologies', 'Server Room A',
        'Finance dept laptop', '',
    ];

    private const VALID_STATUSES   = ['available','assigned','maintenance','repair','retired','disposed','lost'];
    private const VALID_CONDITIONS = ['excellent','good','fair','poor'];

    // ─── INDEX ─────────────────────────────────────────────────────────────────

    public function index(Request $request)
    {
        $categories = AssetCategory::where('organization_id', $this->orgId())
            ->withCount('assets')
            ->orderBy('name')
            ->get();

        // Pre-selected category from URL (persists after navigating away)
        $preselected = $this->resolveCategory($request->get('category_id'));
        $specFields = $this->categorySpecFields($preselected);

        return view('admin.bulk-import.index', compact('categories', 'preselected', 'specFields'));
    }

    // ─── TEMPLATE DOWNLOAD ────────────────────────────────────────────────────

    public function downloadTemplate(Request $request)
    {
        $category   = $this->resolveCategory($request->get('category_id'));
        if (!$category) {
            abort(422, 'Please select an asset category before downloading the CSV template.');
        }

        $specFields = $this->categorySpecFields($category);

        // Build headers: base + spec field labels
        $headers = self::BASE_HEADERS;
        foreach ($specFields as $label => $_key) {
            $headers[] = $label;
        }

        // Build sample row: base values + category-specific optional spec examples
        $sample = self::BASE_SAMPLE;
        foreach ($specFields as $label => $_key) {
            $sample[] = $this->sampleSpecValue($label);
        }

        $output = fopen('php://temp', 'r+');
        fputcsv($output, $headers);
        fputcsv($output, $sample);
        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        $filename = $category
            ? 'asset_import_' . Str::slug($category->name) . '_template.csv'
            : 'asset_import_template.csv';

        return response($csv, 200, [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    // ─── PREVIEW ──────────────────────────────────────────────────────────────

    public function preview(Request $request)
    {
        $request->validate([
            'file'        => 'required|file|mimes:csv,txt|max:5120',
            'category_id' => 'required|exists:asset_categories,id',
        ]);

        $category   = $this->resolveCategory($request->category_id);
        if (!$category) {
            return back()->withErrors(['category_id' => 'Please select a valid asset category before uploading the CSV.'])->withInput();
        }

        $specFields = $this->categorySpecFields($category);   // [label => spec_key]

        // Save temp file
        $tempPath = $request->file('file')->storeAs('imports', Str::uuid() . '.csv');
        $fullPath = Storage::path($tempPath);

        [$rows, $error] = $this->parseCsv($fullPath, $specFields);
        if ($error) {
            Storage::delete($tempPath);
            return back()->withErrors(['file' => $error])->withInput();
        }

        // Resolve lookup maps
        $suppliers = Supplier::where('organization_id', $this->orgId())
            ->pluck('id', 'name')->mapWithKeys(fn($id, $n) => [strtolower(trim($n)) => $id]);
        $locations = Location::whereHas('facility', fn($q) => $q->where('organization_id', $this->orgId()))
            ->pluck('id', 'name')->mapWithKeys(fn($id, $n) => [strtolower(trim($n)) => $id]);
        $parsed = [];
        foreach ($rows as $i => $row) {
            $errors = [];
            $data   = [];

            // Name (required)
            if (empty($row['name'])) {
                $errors[] = 'Name is required';
            } else {
                $data['name'] = trim($row['name']);
            }

            // Asset tag
            $data['asset_tag'] = !empty($row['asset_tag'])
                ? trim($row['asset_tag'])
                : 'ASSET-' . strtoupper(Str::random(8));

            // Text fields
            foreach (['serial_number','brand','model','description','notes','warranty_terms'] as $f) {
                $data[$f] = !empty($row[$f]) ? trim($row[$f]) : null;
            }

            // Status
            $status = strtolower(trim($row['status'] ?? 'available'));
            if (!in_array($status, self::VALID_STATUSES)) {
                $errors[] = "Invalid status '{$row['status']}' — must be: " . implode(', ', self::VALID_STATUSES);
                $status   = 'available';
            }
            $data['status'] = $status;

            // Condition
            $condition = strtolower(trim($row['condition'] ?? 'good'));
            if (!in_array($condition, self::VALID_CONDITIONS)) {
                $errors[] = "Invalid condition — must be: excellent, good, fair, poor";
                $condition = 'good';
            }
            $data['condition'] = $condition;

            // Dates
            foreach (['purchase_date','warranty_expiry_date'] as $df) {
                if (!empty($row[$df])) {
                    $d = $this->parseDate($row[$df]);
                    if (!$d) {
                        $errors[] = "Invalid date '{$row[$df]}' for {$df} — use DD-MM-YYYY or YYYY-MM-DD";
                    } else {
                        $data[$df] = $d;
                    }
                }
            }

            // Price
            if (!empty($row['purchase_price'])) {
                $price = preg_replace('/[^\d.]/', '', $row['purchase_price']);
                if (!is_numeric($price)) {
                    $errors[] = "Invalid purchase price: '{$row['purchase_price']}'";
                } else {
                    $data['purchase_price'] = (float) $price;
                }
            }

            // Category is selected before upload, so every row imports into that category.
            $data['category_id']     = $category->id;
            $data['_category_name']  = $category->name;

            // Supplier
            if (!empty($row['supplier'])) {
                $key = strtolower(trim($row['supplier']));
                if (isset($suppliers[$key])) {
                    $data['vendor_id']       = $suppliers[$key];
                    $data['_supplier_name']  = trim($row['supplier']);
                } else {
                    $errors[] = "Supplier '{$row['supplier']}' not found";
                }
            }

            // Location
            if (!empty($row['location'])) {
                $key = strtolower(trim($row['location']));
                if (isset($locations[$key])) {
                    $data['location_id'] = $locations[$key];
                }
            }

            // Spec fields from category template
            $specs = [];
            foreach ($specFields as $label => $specKey) {
                if (!empty($row[$label])) {
                    $specs[$specKey] = trim($row[$label]);
                }
            }
            if (!empty($specs)) {
                $data['specs']        = $specs;
                $data['_spec_count']  = count($specs);
            }

            $parsed[] = [
                'row'    => $i + 2,
                'data'   => $data,
                'errors' => $errors,
                'valid'  => empty($errors),
                'raw'    => $row,
            ];
        }

        $validCount   = collect($parsed)->where('valid', true)->count();
        $invalidCount = count($parsed) - $validCount;

        return view('admin.bulk-import.preview', compact(
            'parsed', 'validCount', 'invalidCount', 'tempPath',
            'category', 'specFields'
        ));
    }

    // ─── IMPORT ───────────────────────────────────────────────────────────────

    public function import(Request $request)
    {
        $request->validate([
            'temp_path'   => 'required|string',
            'category_id' => 'required|exists:asset_categories,id',
        ]);

        $fullPath = Storage::path($request->temp_path);
        if (!file_exists($fullPath)) {
            return redirect()->route('admin.bulk-import.index')
                ->withErrors(['file' => 'Import session expired. Please upload again.']);
        }

        $category   = $this->resolveCategory($request->category_id);
        if (!$category) {
            return redirect()->route('admin.bulk-import.index')
                ->withErrors(['category_id' => 'Please select a valid asset category before importing.']);
        }

        $specFields = $this->categorySpecFields($category);

        $suppliers  = Supplier::where('organization_id', $this->orgId())
            ->pluck('id', 'name')->mapWithKeys(fn($id, $n) => [strtolower(trim($n)) => $id]);
        $locations  = Location::whereHas('facility', fn($q) => $q->where('organization_id', $this->orgId()))
            ->pluck('id', 'name')->mapWithKeys(fn($id, $n) => [strtolower(trim($n)) => $id]);
        [$rows, $error] = $this->parseCsv($fullPath, $specFields);
        if ($error) {
            return redirect()->route('admin.bulk-import.index')->withErrors(['file' => $error]);
        }

        $imported = 0;
        $skipped  = 0;

        foreach ($rows as $row) {
            if (empty($row['name'])) { $skipped++; continue; }

            $status    = strtolower(trim($row['status'] ?? 'available'));
            $condition = strtolower(trim($row['condition'] ?? 'good'));
            if (!in_array($status, self::VALID_STATUSES))    { $skipped++; continue; }
            if (!in_array($condition, self::VALID_CONDITIONS)) { $skipped++; continue; }

            $data = [
                'organization_id' => $this->orgId(),
                'name'            => trim($row['name']),
                'asset_tag'       => !empty($row['asset_tag']) ? trim($row['asset_tag']) : 'ASSET-' . strtoupper(Str::random(8)),
                'serial_number'   => $row['serial_number'] ?? null,
                'brand'           => $row['brand'] ?? null,
                'model'           => $row['model'] ?? null,
                'description'     => $row['description'] ?? null,
                'notes'           => $row['notes'] ?? null,
                'warranty_terms'  => $row['warranty_terms'] ?? null,
                'status'          => $status,
                'condition'       => $condition,
            ];

            // Dates & price
            foreach (['purchase_date','warranty_expiry_date'] as $df) {
                if (!empty($row[$df])) {
                    $d = $this->parseDate($row[$df]);
                    if ($d) $data[$df] = $d;
                }
            }
            if (!empty($row['purchase_price'])) {
                $p = preg_replace('/[^\d.]/', '', $row['purchase_price']);
                if (is_numeric($p)) $data['purchase_price'] = (float) $p;
            }

            // Category is selected before upload, so every row imports into that category.
            $data['category_id'] = $category->id;

            // Supplier & location
            if (!empty($row['supplier'])) {
                $sid = $suppliers[strtolower(trim($row['supplier']))] ?? null;
                if (!$sid) { $skipped++; continue; }
                $data['vendor_id'] = $sid;
            }
            if (!empty($row['location'])) {
                $lid = $locations[strtolower(trim($row['location']))] ?? null;
                if ($lid) $data['location_id'] = $lid;
            }

            // Specs
            $specs = [];
            foreach ($specFields as $label => $specKey) {
                if (!empty($row[$label])) $specs[$specKey] = trim($row[$label]);
            }
            if (!empty($specs)) $data['specs'] = $specs;

            Asset::create($data);
            $imported++;
        }

        Storage::delete($request->temp_path);

        return redirect()->route('admin.assets.index')
            ->with('success', "Bulk import complete — {$imported} asset" . ($imported !== 1 ? 's' : '') . " imported" . ($skipped ? ", {$skipped} skipped." : '.'));
    }

    // ─── HELPERS ──────────────────────────────────────────────────────────────

    /** Resolve category by id, scoped to this org */
    private function resolveCategory(?string $id): ?AssetCategory
    {
        if (!$id) return null;
        $cat = AssetCategory::find($id);
        return ($cat && $cat->organization_id === $this->orgId()) ? $cat : null;
    }

    /** Return [label => spec_key] map from category's spec_template */
    private function categorySpecFields(?AssetCategory $category): array
    {
        if (!$category || empty($category->spec_template)) return [];
        $map = [];
        foreach ($category->spec_template as $label) {
            $key = 'spec_' . $this->normalizeHeader((string) $label);
            $map[$label] = $key;
        }
        return $map;
    }

    private function normalizeHeader(string $value): string
    {
        $value = strtolower(trim($value));
        $value = preg_replace('/[^a-z0-9]+/', '_', $value);
        return trim((string) $value, '_');
    }

    private function sampleSpecValue(string $label): string
    {
        $key = $this->normalizeHeader($label);

        return match (true) {
            str_contains($key, 'processor'), str_contains($key, 'cpu') => 'Intel Core i5',
            str_contains($key, 'ram'), str_contains($key, 'memory') => '16 GB',
            str_contains($key, 'storage'), str_contains($key, 'disk'), str_contains($key, 'ssd') => '512 GB SSD',
            str_contains($key, 'os'), str_contains($key, 'operating_system') => 'Windows 11 Pro',
            str_contains($key, 'screen'), str_contains($key, 'display') => '14 inch',
            str_contains($key, 'capacity') => '1 TB',
            str_contains($key, 'speed') => '30 ppm',
            str_contains($key, 'type') => 'Laser',
            default => '',
        };
    }

    /** Parse a date string in DD-MM-YYYY or YYYY-MM-DD format */
    private function parseDate(string $raw): ?string
    {
        $raw = trim($raw);
        // DD-MM-YYYY
        if (preg_match('/^(\d{2})-(\d{2})-(\d{4})$/', $raw, $m)) {
            return "{$m[3]}-{$m[2]}-{$m[1]}";
        }
        // YYYY-MM-DD
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $raw)) {
            return $raw;
        }
        // Try generic parse
        $d = date_create($raw);
        return $d ? date_format($d, 'Y-m-d') : null;
    }

    /** Parse CSV, mapping spec label columns in addition to base columns */
    private function parseCsv(string $path, array $specFields): array
    {
        if (!file_exists($path)) return [[], 'File not found.'];

        $handle = fopen($path, 'r');
        if (!$handle) return [[], 'Cannot read file.'];

        $rawHeaders = fgetcsv($handle);
        if (!$rawHeaders) { fclose($handle); return [[], 'CSV file is empty or has no header row.']; }

        // Normalize headers so "RAM", "Ram", "RAM (GB)", etc. can be matched reliably.
        $normalized = array_map(fn($h) => $this->normalizeHeader((string) $h), $rawHeaders);

        // Build a mapping: column index → field key to use in the row array
        // Base columns: normalize the header and use as-is (covers name, asset_tag, etc.)
        // Spec columns: match by label (normalized) → store under the original label
        $specLabelNorm = [];
        foreach ($specFields as $label => $_key) {
            $specLabelNorm[$this->normalizeHeader((string) $label)] = $label;
        }

        $indexMap = [];
        foreach ($normalized as $i => $norm) {
            if (isset($specLabelNorm[$norm])) {
                $indexMap[$i] = $specLabelNorm[$norm];  // store under original label
            } else {
                $indexMap[$i] = $norm;   // store under normalized base name
            }
        }

        if (!in_array('name', $indexMap)) {
            fclose($handle);
            return [[], "Required column 'name' not found. Headers found: " . implode(', ', $rawHeaders)];
        }

        $rows = [];
        while (($values = fgetcsv($handle)) !== false) {
            if (count(array_filter($values)) === 0) continue;
            $row = [];
            foreach ($indexMap as $i => $field) {
                $row[$field] = trim($values[$i] ?? '');
            }
            $rows[] = $row;
        }
        fclose($handle);

        if (empty($rows)) return [[], 'CSV contains no data rows (only a header).'];

        return [$rows, null];
    }
}
