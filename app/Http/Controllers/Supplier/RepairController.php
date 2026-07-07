<?php

namespace App\Http\Controllers\Supplier;

use App\Http\Controllers\Controller;
use App\Models\AssetRepair;
use App\Models\AssetRepairAttachment;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class RepairController extends Controller
{
    private function vendorOrFail(): Supplier
    {
        return Supplier::where('user_id', auth()->id())
            ->whereIn('partner_type', ['vendor', 'both'])
            ->firstOrFail();
    }

    public function index(Request $request)
    {
        $vendor = $this->vendorOrFail();

        $query = AssetRepair::where('vendor_id', $vendor->id)
            ->with(['asset.category', 'asset.organization', 'amcContract'])
            ->latest('requested_date')
            ->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $query->where(function ($searchQuery) use ($request) {
                $searchQuery->where('repair_number', 'like', '%' . $request->search . '%')
                    ->orWhereHas('asset', function ($assetQuery) use ($request) {
                        $assetQuery->where('name', 'like', '%' . $request->search . '%')
                            ->orWhere('asset_tag', 'like', '%' . $request->search . '%')
                            ->orWhere('serial_number', 'like', '%' . $request->search . '%');
                    });
            });
        }

        $repairs = $query->paginate(15)->withQueryString();

        return view('supplier-portal.repairs.index', compact('vendor', 'repairs'));
    }

    public function show(AssetRepair $repair)
    {
        $vendor = $this->vendorOrFail();
        $this->authorizeRepair($repair, $vendor);

        $repair->load([
            'asset.category',
            'asset.organization',
            'asset.location',
            'assignment.user',
            'amcContract',
            'parts',
            'attachments.uploadedBy',
        ]);

        return view('supplier-portal.repairs.show', compact('vendor', 'repair'));
    }

    public function update(Request $request, AssetRepair $repair)
    {
        $vendor = $this->vendorOrFail();
        $this->authorizeRepair($repair, $vendor);

        $data = $request->validate([
            'status' => ['required', 'in:diagnosis_pending,estimate_received,repair_in_progress,repaired'],
            'diagnosis' => ['nullable', 'required_if:status,estimate_received,repair_in_progress,repaired', 'string', 'max:2000'],
            'work_performed' => ['nullable', 'required_if:status,repaired', 'string', 'max:2000'],
            'expected_return_date' => ['nullable', 'date'],
            'completed_date' => ['nullable', 'date'],
            'service_cost' => ['nullable', 'numeric', 'min:0'],
            'tax_amount' => ['nullable', 'numeric', 'min:0'],
            'discount_amount' => ['nullable', 'numeric', 'min:0'],
            'invoice_number' => ['nullable', 'string', 'max:100'],
            'parts' => ['array'],
            'parts.*.part_name' => ['nullable', 'string', 'max:255'],
            'parts.*.part_number' => ['nullable', 'string', 'max:255'],
            'parts.*.quantity' => ['nullable', 'integer', 'min:1'],
            'parts.*.unit_cost' => ['nullable', 'numeric', 'min:0'],
            'parts.*.notes' => ['nullable', 'string', 'max:1000'],
        ]);

        DB::transaction(function () use ($repair, $data, $request) {
            unset($data['parts']);

            foreach (['service_cost', 'tax_amount', 'discount_amount'] as $moneyField) {
                $data[$moneyField] = $data[$moneyField] ?? 0;
            }

            if ($data['status'] === 'repaired' && empty($data['completed_date'])) {
                $data['completed_date'] = today();
            }

            $repair->update($data);
            $this->syncParts($repair, $request);
            $this->refreshCosts($repair);
        });

        return back()->with('success', 'Repair job updated for Admin/IT review.');
    }

    public function storeAttachment(Request $request, AssetRepair $repair)
    {
        $vendor = $this->vendorOrFail();
        $this->authorizeRepair($repair, $vendor);

        $data = $request->validate([
            'type' => ['required', 'in:invoice,estimate,repair_photo,supporting_document'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'files' => ['required', 'array', 'max:5'],
            'files.*' => ['file', 'max:10240', 'mimes:pdf,jpg,jpeg,png,doc,docx,xls,xlsx,txt,zip'],
        ]);

        foreach ($request->file('files', []) as $file) {
            $repair->attachments()->create([
                'organization_id' => $repair->organization_id,
                'uploaded_by' => auth()->id(),
                'type' => $data['type'],
                'visibility' => 'internal',
                'file_path' => $file->store('asset-repairs/' . $repair->id, 'public'),
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getClientMimeType(),
                'file_size' => $file->getSize(),
                'notes' => $data['notes'] ?? null,
            ]);
        }

        return back()->with('success', 'Repair document uploaded.');
    }

    public function destroyAttachment(AssetRepair $repair, AssetRepairAttachment $attachment)
    {
        $vendor = $this->vendorOrFail();
        $this->authorizeRepair($repair, $vendor);
        abort_if($attachment->asset_repair_id !== $repair->id || $attachment->uploaded_by !== auth()->id(), 403);

        Storage::disk('public')->delete($attachment->file_path);
        $attachment->delete();

        return back()->with('success', 'Repair document removed.');
    }

    private function syncParts(AssetRepair $repair, Request $request): void
    {
        $repair->parts()->delete();

        foreach ($request->input('parts', []) as $part) {
            if (blank($part['part_name'] ?? null)) {
                continue;
            }

            $quantity = max(1, (int) ($part['quantity'] ?? 1));
            $unitCost = (float) ($part['unit_cost'] ?? 0);

            $repair->parts()->create([
                'part_name' => $part['part_name'],
                'part_number' => $part['part_number'] ?? null,
                'quantity' => $quantity,
                'unit_cost' => $unitCost,
                'total_cost' => $quantity * $unitCost,
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

    private function authorizeRepair(AssetRepair $repair, Supplier $vendor): void
    {
        abort_if($repair->vendor_id !== $vendor->id || $repair->organization_id !== $vendor->organization_id, 403);
    }
}
