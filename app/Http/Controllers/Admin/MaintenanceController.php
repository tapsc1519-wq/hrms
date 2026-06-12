<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\MaintenanceRecord;
use App\Models\Supplier;
use Illuminate\Http\Request;

class MaintenanceController extends Controller
{


    public function index(Request $request)
    {
        $query = MaintenanceRecord::whereHas('asset', fn($q) => $q->where('organization_id', $this->orgId()))
            ->with(['asset', 'supplier']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('search')) {
            $query->whereHas('asset', fn($q) => $q->where('name', 'like', '%' . $request->search . '%'));
        }

        $records = $query->latest()->paginate(20)->withQueryString();

        return view('admin.maintenance.index', compact('records'));
    }

    public function create()
    {
        $assets  = Asset::where('organization_id', $this->orgId())->orderBy('name')->get();
        $suppliers = Supplier::where('organization_id', $this->orgId())->where('status', 'active')->orderBy('name')->get();

        return view('admin.maintenance.create', compact('assets', 'suppliers'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'asset_id'             => 'required|exists:assets,id',
            'vendor_id'            => 'nullable|exists:suppliers,id',
            'type'                 => 'required|in:preventive,corrective,predictive,emergency',
            'scheduled_date'       => 'nullable|date',
            'description'          => 'required|string',
            'technician_name'      => 'nullable|string|max:255',
            'technician_company'   => 'nullable|string|max:255',
            'labor_cost'           => 'nullable|numeric|min:0',
            'parts_cost'           => 'nullable|numeric|min:0',
            'status'               => 'required|in:scheduled,in_progress,completed,cancelled',
            'next_maintenance_date'=> 'nullable|date',
            'notes'                => 'nullable|string',
        ]);

        $asset = Asset::findOrFail($validated['asset_id']);
        abort_if($asset->organization_id !== $this->orgId(), 403);

        $validated['total_cost'] = ($validated['labor_cost'] ?? 0) + ($validated['parts_cost'] ?? 0);

        $record = MaintenanceRecord::create($validated);

        if (in_array($validated['status'], ['scheduled', 'in_progress'])) {
            $asset->update(['status' => 'maintenance']);
        }

        return redirect()->route('admin.maintenance.index')
            ->with('success', 'Maintenance record created.');
    }

    public function show(MaintenanceRecord $maintenanceRecord)
    {
        $maintenanceRecord->load(['asset', 'supplier']);
        $this->authorizeMaintenanceRecord($maintenanceRecord);
        return view('admin.maintenance.show', compact('maintenanceRecord'));
    }

    public function edit(MaintenanceRecord $maintenanceRecord)
    {
        $this->authorizeMaintenanceRecord($maintenanceRecord);
        $assets  = Asset::where('organization_id', $this->orgId())->orderBy('name')->get();
        $suppliers = Supplier::where('organization_id', $this->orgId())->where('status', 'active')->orderBy('name')->get();
        return view('admin.maintenance.edit', compact('maintenanceRecord', 'assets', 'suppliers'));
    }

    public function update(Request $request, MaintenanceRecord $maintenanceRecord)
    {
        $this->authorizeMaintenanceRecord($maintenanceRecord);

        $validated = $request->validate([
            'type'                 => 'required|in:preventive,corrective,predictive,emergency',
            'scheduled_date'       => 'nullable|date',
            'completed_date'       => 'nullable|date',
            'description'          => 'required|string',
            'diagnosis'            => 'nullable|string',
            'work_performed'       => 'nullable|string',
            'parts_replaced'       => 'nullable|string',
            'technician_name'      => 'nullable|string|max:255',
            'technician_company'   => 'nullable|string|max:255',
            'labor_cost'           => 'nullable|numeric|min:0',
            'parts_cost'           => 'nullable|numeric|min:0',
            'status'               => 'required|in:scheduled,in_progress,completed,cancelled',
            'next_maintenance_date'=> 'nullable|date',
            'invoice_number'       => 'nullable|string|max:100',
            'notes'                => 'nullable|string',
        ]);

        $validated['total_cost'] = ($validated['labor_cost'] ?? 0) + ($validated['parts_cost'] ?? 0);
        $maintenanceRecord->update($validated);

        if ($validated['status'] === 'completed') {
            $maintenanceRecord->asset->update(['status' => 'available']);
        }

        return redirect()->route('admin.maintenance.show', $maintenanceRecord)
            ->with('success', 'Maintenance record updated.');
    }

    private function authorizeMaintenanceRecord(MaintenanceRecord $maintenanceRecord): void
    {
        $maintenanceRecord->loadMissing('asset');

        abort_if(
            !$maintenanceRecord->asset || $maintenanceRecord->asset->organization_id !== $this->orgId(),
            404,
            'Maintenance record asset was not found.'
        );
    }
}
