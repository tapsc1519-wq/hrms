<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\AssetAmcContract;
use App\Models\AssetRepair;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AssetRepairController extends Controller
{
    public function index(Request $request)
    {
        $query = AssetRepair::where('organization_id', $this->orgId())
            ->with(['asset.category', 'asset.activeAssignment.user', 'requestedBy', 'vendor'])
            ->latest('requested_date')
            ->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        } else {
            $query->whereIn('status', AssetRepair::OPEN_STATUSES);
        }

        if ($request->filled('repair_type')) {
            $query->where('repair_type', $request->repair_type);
        }

        if ($request->filled('search')) {
            $query->where(function ($searchQuery) use ($request) {
                $searchQuery->where('repair_number', 'like', '%' . $request->search . '%')
                    ->orWhereHas('asset', function ($assetQuery) use ($request) {
                        $assetQuery->where('name', 'like', '%' . $request->search . '%')
                            ->orWhere('asset_tag', 'like', '%' . $request->search . '%')
                            ->orWhere('serial_number', 'like', '%' . $request->search . '%');
                    })
                    ->orWhereHas('requestedBy', function ($userQuery) use ($request) {
                        $userQuery->where('name', 'like', '%' . $request->search . '%')
                            ->orWhere('email', 'like', '%' . $request->search . '%');
                    });
            });
        }

        $repairs = $query->paginate(20)->withQueryString();
        $stats = [
            'open' => AssetRepair::where('organization_id', $this->orgId())->whereIn('status', AssetRepair::OPEN_STATUSES)->count(),
            'qc_pending' => AssetRepair::where('organization_id', $this->orgId())->where('status', 'qc_pending')->count(),
            'ready_to_return' => AssetRepair::where('organization_id', $this->orgId())->where('status', 'ready_to_return')->count(),
            'closed' => AssetRepair::where('organization_id', $this->orgId())->where('status', 'closed')->count(),
        ];

        return view('admin.asset-repairs.index', compact('repairs', 'stats'));
    }

    public function create(Request $request)
    {
        $assets = Asset::where('organization_id', $this->orgId())
            ->whereNotIn('status', ['disposed', 'lost'])
            ->with(['category', 'activeAssignment.user', 'activeRepair'])
            ->orderBy('name')
            ->get();

        $suppliers = Supplier::where('organization_id', $this->orgId())->whereIn('partner_type', ['vendor', 'both'])->where('status', 'active')->orderBy('name')->get();
        $amcContracts = AssetAmcContract::where('organization_id', $this->orgId())->where('status', 'active')->orderBy('title')->get();
        $itUsers = User::where('organization_id', $this->orgId())->whereIn('role', ['admin', 'staff'])->where('status', 'active')->orderBy('name')->get();
        $selectedAsset = $request->integer('asset_id');

        return view('admin.asset-repairs.create', compact('assets', 'suppliers', 'amcContracts', 'itUsers', 'selectedAsset'));
    }

    public function store(Request $request)
    {
        $data = $this->validatedRepairData($request, true);
        $asset = Asset::where('organization_id', $this->orgId())->findOrFail($data['asset_id']);

        abort_if($asset->activeRepair()->exists(), 422, 'This asset already has an open repair job.');

        $repair = DB::transaction(function () use ($asset, $data, $request) {
            $repairData = $this->repairRecordData($data);

            $repair = AssetRepair::create([
                ...$repairData,
                'organization_id' => $this->orgId(),
                'asset_assignment_id' => $asset->activeAssignment?->id,
                'requested_by' => $data['requested_by'] ?? auth()->id(),
                'approved_by' => auth()->id(),
                'repair_number' => $this->nextRepairNumber(),
                'source' => 'admin',
                'requested_date' => $data['requested_date'] ?? today(),
            ]);

            $this->syncParts($repair, $request);
            $this->refreshCosts($repair);
            $asset->update(['status' => 'repair']);

            return $repair;
        });

        return redirect()->route('admin.asset-repairs.show', $repair)->with('success', 'Repair job created.');
    }

    public function show(AssetRepair $assetRepair)
    {
        $this->authorizeRepair($assetRepair);
        $assetRepair->load([
            'asset.category',
            'asset.location',
            'asset.activeAssignment.user',
            'assignment.user',
            'issueReport',
            'requestedBy',
            'approvedBy',
            'assignedTo',
            'qcBy',
            'vendor',
            'amcContract',
            'parts',
        ]);

        $suppliers = Supplier::where('organization_id', $this->orgId())->whereIn('partner_type', ['vendor', 'both'])->where('status', 'active')->orderBy('name')->get();
        $amcContracts = AssetAmcContract::where('organization_id', $this->orgId())->where('status', 'active')->orderBy('title')->get();
        $itUsers = User::where('organization_id', $this->orgId())->whereIn('role', ['admin', 'staff'])->where('status', 'active')->orderBy('name')->get();

        return view('admin.asset-repairs.show', compact('assetRepair', 'suppliers', 'amcContracts', 'itUsers'));
    }

    public function update(Request $request, AssetRepair $assetRepair)
    {
        $this->authorizeRepair($assetRepair);
        $data = $this->validatedRepairData($request, false);

        DB::transaction(function () use ($assetRepair, $data, $request) {
            $repairData = $this->repairRecordData($data);

            if (in_array($data['status'], ['approved', 'assigned_to_it', 'assigned_to_vendor', 'sent_for_repair'], true) && !$assetRepair->approved_by) {
                $repairData['approved_by'] = auth()->id();
            }

            if (in_array($data['status'], ['repaired', 'qc_pending', 'ready_to_return', 'closed'], true) && empty($data['completed_date'])) {
                $repairData['completed_date'] = today();
            }

            $assetRepair->update($repairData);
            $this->syncParts($assetRepair, $request);
            $this->refreshCosts($assetRepair);

            if (in_array($assetRepair->status, AssetRepair::OPEN_STATUSES, true)) {
                $assetRepair->asset->update(['status' => 'repair']);
            }
        });

        return back()->with('success', 'Repair job updated.');
    }

    public function qualityCheck(Request $request, AssetRepair $assetRepair)
    {
        $this->authorizeRepair($assetRepair);
        $data = $request->validate([
            'qc_status' => ['required', 'in:passed,failed'],
            'qc_checks' => ['array'],
            'qc_checks.*' => ['string', 'max:100'],
            'qc_notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $assetRepair->update([
            'qc_status' => $data['qc_status'],
            'qc_checks' => $data['qc_checks'] ?? [],
            'qc_notes' => $data['qc_notes'] ?? null,
            'qc_by' => auth()->id(),
            'qc_at' => now(),
            'status' => $data['qc_status'] === 'passed' ? 'ready_to_return' : 'qc_failed',
        ]);

        return back()->with('success', 'Quality check updated.');
    }

    public function close(Request $request, AssetRepair $assetRepair)
    {
        $this->authorizeRepair($assetRepair);
        abort_if(!in_array($assetRepair->status, ['ready_to_return', 'not_repairable'], true), 422, 'Complete QC or mark the asset not repairable before closing.');

        $data = $request->validate([
            'return_to' => ['required', 'in:employee,stock,not_repairable'],
            'returned_date' => ['required', 'date'],
            'admin_notes' => ['nullable', 'string', 'max:2000'],
        ]);

        DB::transaction(function () use ($assetRepair, $data) {
            $assetStatus = match ($data['return_to']) {
                'employee' => $assetRepair->assignment && $assetRepair->assignment->status === 'active' ? 'assigned' : 'available',
                'not_repairable' => 'retired',
                default => 'available',
            };

            $assetRepair->update([
                'status' => $data['return_to'] === 'not_repairable' ? 'not_repairable' : 'closed',
                'returned_date' => $data['returned_date'],
                'admin_notes' => trim(($assetRepair->admin_notes ? $assetRepair->admin_notes . PHP_EOL . PHP_EOL : '') . ($data['admin_notes'] ?? '')),
            ]);

            $assetRepair->asset->update(['status' => $assetStatus]);
        });

        return redirect()->route('admin.asset-repairs.index')->with('success', 'Repair job closed and asset status updated.');
    }

    private function validatedRepairData(Request $request, bool $creating): array
    {
        return $request->validate([
            'asset_id' => [$creating ? 'required' : 'sometimes', 'exists:assets,id'],
            'amc_contract_id' => ['nullable', 'required_if:repair_type,amc', 'exists:asset_amc_contracts,id'],
            'vendor_id' => ['nullable', 'required_if:repair_type,vendor', 'exists:suppliers,id'],
            'assigned_to' => ['nullable', 'exists:users,id'],
            'repair_type' => ['required', 'in:internal,amc,vendor,market,warranty'],
            'priority' => ['required', 'in:low,medium,high,critical'],
            'status' => ['required', 'in:request_raised,under_review,approved,assigned_to_it,assigned_to_vendor,sent_for_repair,diagnosis_pending,estimate_received,estimate_approved,repair_in_progress,repaired,qc_pending,qc_failed,ready_to_return,rejected,not_repairable,closed'],
            'market_vendor_name' => ['nullable', 'required_if:repair_type,market', 'string', 'max:255'],
            'market_vendor_contact' => ['nullable', 'string', 'max:255'],
            'market_vendor_phone' => ['nullable', 'string', 'max:100'],
            'market_vendor_address' => ['nullable', 'string', 'max:1000'],
            'warranty_provider_type' => ['nullable', 'required_if:repair_type,warranty', 'in:original_supplier,manufacturer_service_center,other'],
            'warranty_provider_name' => ['nullable', 'required_if:warranty_provider_type,other', 'string', 'max:255'],
            'warranty_provider_phone' => ['nullable', 'string', 'max:100'],
            'warranty_claim_number' => ['nullable', 'string', 'max:150'],
            'issue_summary' => ['required', 'string', 'max:2000'],
            'diagnosis' => ['nullable', 'string', 'max:2000'],
            'work_performed' => ['nullable', 'string', 'max:2000'],
            'requested_date' => ['nullable', 'date'],
            'sent_date' => ['nullable', 'date'],
            'expected_return_date' => ['nullable', 'date'],
            'completed_date' => ['nullable', 'date'],
            'service_cost' => ['nullable', 'numeric', 'min:0'],
            'tax_amount' => ['nullable', 'numeric', 'min:0'],
            'discount_amount' => ['nullable', 'numeric', 'min:0'],
            'invoice_number' => ['nullable', 'string', 'max:100'],
            'admin_notes' => ['nullable', 'string', 'max:2000'],
            'parts' => ['array'],
            'parts.*.part_name' => ['nullable', 'string', 'max:255'],
            'parts.*.part_number' => ['nullable', 'string', 'max:255'],
            'parts.*.quantity' => ['nullable', 'integer', 'min:1'],
            'parts.*.unit_cost' => ['nullable', 'numeric', 'min:0'],
            'parts.*.notes' => ['nullable', 'string', 'max:1000'],
        ]);
    }

    private function repairRecordData(array $data): array
    {
        unset($data['parts']);

        foreach (['service_cost', 'tax_amount', 'discount_amount'] as $moneyField) {
            $data[$moneyField] = $data[$moneyField] ?? 0;
        }

        return $this->normalizeRepairTypeFields($data);
    }

    private function normalizeRepairTypeFields(array $data): array
    {
        $repairType = $data['repair_type'] ?? 'internal';

        if ($repairType !== 'amc') {
            $data['amc_contract_id'] = null;
        }

        if ($repairType !== 'vendor') {
            $data['vendor_id'] = null;
        }

        if ($repairType === 'amc' && !empty($data['amc_contract_id'])) {
            $contract = AssetAmcContract::where('organization_id', $this->orgId())->find($data['amc_contract_id']);
            $data['vendor_id'] = $contract?->vendor_id;
        }

        if ($repairType !== 'market') {
            $data['market_vendor_name'] = null;
            $data['market_vendor_contact'] = null;
            $data['market_vendor_phone'] = null;
            $data['market_vendor_address'] = null;
        }

        if ($repairType !== 'warranty') {
            $data['warranty_provider_type'] = null;
            $data['warranty_provider_name'] = null;
            $data['warranty_provider_phone'] = null;
            $data['warranty_claim_number'] = null;
        }

        if (($data['warranty_provider_type'] ?? null) !== 'other') {
            $data['warranty_provider_name'] = null;
        }

        return $data;
    }

    private function syncParts(AssetRepair $repair, Request $request): void
    {
        $repair->parts()->delete();

        foreach ($request->input('parts', []) as $part) {
            if (blank($part['part_name'] ?? null)) {
                continue;
            }

            $quantity = (int) ($part['quantity'] ?? 1);
            $unitCost = (float) ($part['unit_cost'] ?? 0);

            $repair->parts()->create([
                'part_name' => $part['part_name'],
                'part_number' => $part['part_number'] ?? null,
                'quantity' => max(1, $quantity),
                'unit_cost' => $unitCost,
                'total_cost' => max(1, $quantity) * $unitCost,
                'notes' => $part['notes'] ?? null,
            ]);
        }
    }

    private function refreshCosts(AssetRepair $repair): void
    {
        $partsCost = (float) $repair->parts()->sum('total_cost');
        $serviceCost = (float) $repair->service_cost;
        $taxAmount = (float) $repair->tax_amount;
        $discountAmount = (float) $repair->discount_amount;

        $repair->forceFill([
            'parts_cost' => $partsCost,
            'total_cost' => max(0, $partsCost + $serviceCost + $taxAmount - $discountAmount),
        ])->save();
    }

    private function nextRepairNumber(): string
    {
        return 'REP-' . now()->format('Ymd') . '-' . str_pad((string) (AssetRepair::whereDate('created_at', today())->count() + 1), 4, '0', STR_PAD_LEFT);
    }

    private function authorizeRepair(AssetRepair $assetRepair): void
    {
        abort_if($assetRepair->organization_id !== $this->orgId(), 403);
    }
}
