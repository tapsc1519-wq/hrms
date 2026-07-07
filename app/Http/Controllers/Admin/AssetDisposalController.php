<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\AssetAssignment;
use App\Models\AssetDisposal;
use App\Models\DisposalBuyer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AssetDisposalController extends Controller
{
    public function index(Request $request)
    {
        return redirect()->route('admin.disposals.requests');
    }

    public function requests(Request $request)
    {
        return $this->listDisposals($request, ['pending', 'rejected', 'cancelled'], [
            'mode' => 'requests',
            'pageTitle' => 'Disposal Requests',
            'pageDescription' => 'Requests raised by IT Support or asset teams for disposal approval.',
            'emptyText' => 'No disposal requests found.',
        ]);
    }

    public function approvals(Request $request)
    {
        return $this->listDisposals($request, ['pending', 'approved'], [
            'mode' => 'approvals',
            'pageTitle' => 'Disposal Approvals',
            'pageDescription' => 'Review pending disposal requests and complete approved disposals.',
            'emptyText' => 'No disposal approvals waiting.',
        ]);
    }

    public function history(Request $request)
    {
        return $this->listDisposals($request, ['completed', 'rejected', 'cancelled'], [
            'mode' => 'history',
            'pageTitle' => 'Disposal History',
            'pageDescription' => 'Completed, rejected and cancelled disposal records for audit history.',
            'emptyText' => 'No disposal history found.',
        ]);
    }

    private function listDisposals(Request $request, array $defaultStatuses, array $meta)
    {
        $query = AssetDisposal::where('organization_id', $this->orgId())
            ->with(['asset.category', 'requestedBy', 'approvedBy', 'completedBy', 'disposalBuyer'])
            ->latest('requested_date');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        } else {
            $query->whereIn('status', $defaultStatuses);
        }

        if ($request->filled('method')) {
            $query->where('method', $request->method);
        }

        if ($request->filled('search')) {
            $query->whereHas('asset', function ($assetQuery) use ($request) {
                $assetQuery->where('name', 'like', '%' . $request->search . '%')
                    ->orWhere('asset_tag', 'like', '%' . $request->search . '%')
                    ->orWhere('serial_number', 'like', '%' . $request->search . '%');
            });
        }

        $disposals = $query->paginate(20)->withQueryString();

        $stats = [
            'pending' => AssetDisposal::where('organization_id', $this->orgId())->where('status', 'pending')->count(),
            'approved' => AssetDisposal::where('organization_id', $this->orgId())->where('status', 'approved')->count(),
            'completed' => AssetDisposal::where('organization_id', $this->orgId())->where('status', 'completed')->count(),
            'recovered' => AssetDisposal::where('organization_id', $this->orgId())->where('status', 'completed')->sum('recovered_value'),
        ];
        $mode = $meta['mode'];
        $pageTitle = $meta['pageTitle'];
        $pageDescription = $meta['pageDescription'];
        $emptyText = $meta['emptyText'];
        $defaultStatusOptions = $defaultStatuses;

        return view('admin.disposals.index', compact(
            'disposals',
            'stats',
            'mode',
            'pageTitle',
            'pageDescription',
            'emptyText',
            'defaultStatusOptions'
        ));
    }

    public function create(Request $request)
    {
        $assets = Asset::where('organization_id', $this->orgId())
            ->whereNotIn('status', ['disposed', 'lost'])
            ->with(['category', 'activeAssignment.user'])
            ->orderBy('name')
            ->get();

        $selectedAsset = null;
        if ($request->filled('asset_id')) {
            $selectedAsset = Asset::where('organization_id', $this->orgId())->findOrFail($request->asset_id);
        }

        $buyers = $this->activeBuyers();

        return view('admin.disposals.create', compact('assets', 'selectedAsset', 'buyers'));
    }

    public function bulk()
    {
        $assets = Asset::where('organization_id', $this->orgId())
            ->whereNotIn('status', ['disposed', 'lost'])
            ->with(['category', 'activeAssignment.user', 'activeDisposal'])
            ->orderBy('asset_tag')
            ->get();

        $buyers = $this->activeBuyers();

        return view('admin.disposals.bulk', compact('assets', 'buyers'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'asset_id' => ['required', 'exists:assets,id'],
            'method' => ['required', 'in:scrap,sell,donate,recycle,return_to_supplier,destroy,lost,stolen'],
            'requested_date' => ['required', 'date'],
            'expected_value' => ['nullable', 'numeric', 'min:0'],
            'disposal_buyer_id' => ['nullable', 'integer', 'exists:disposal_buyers,id'],
            'recipient_name' => ['nullable', 'string', 'max:255'],
            'reason' => ['required', 'string', 'max:2000'],
        ]);

        $asset = Asset::where('organization_id', $this->orgId())->findOrFail($data['asset_id']);
        $this->assertBuyerBelongsToOrganization($data['disposal_buyer_id'] ?? null);
        $data = $this->applyBuyerSnapshot($data);
        abort_if(in_array($asset->status, ['disposed', 'lost'], true), 422, 'This asset cannot be disposed.');

        $openDisposal = AssetDisposal::where('asset_id', $asset->id)
            ->whereIn('status', ['pending', 'approved'])
            ->exists();
        abort_if($openDisposal, 422, 'This asset already has an open disposal request.');

        AssetDisposal::create([
            ...$data,
            'organization_id' => $this->orgId(),
            'requested_by' => auth()->id(),
            'status' => 'pending',
        ]);

        return redirect()->route('admin.disposals.requests')->with('success', 'Disposal request created successfully.');
    }

    public function storeBulk(Request $request)
    {
        $data = $request->validate([
            'asset_ids' => ['nullable', 'array'],
            'asset_ids.*' => ['integer', 'exists:assets,id'],
            'asset_identifiers' => ['nullable', 'string', 'max:10000'],
            'method' => ['required', 'in:scrap,sell,donate,recycle,return_to_supplier,destroy,lost,stolen'],
            'requested_date' => ['required', 'date'],
            'expected_value' => ['nullable', 'numeric', 'min:0'],
            'disposal_buyer_id' => ['nullable', 'integer', 'exists:disposal_buyers,id'],
            'recipient_name' => ['nullable', 'string', 'max:255'],
            'reason' => ['required', 'string', 'max:2000'],
        ]);
        $this->assertBuyerBelongsToOrganization($data['disposal_buyer_id'] ?? null);
        $data = $this->applyBuyerSnapshot($data);

        $ids = collect($data['asset_ids'] ?? [])->filter()->map(fn ($id) => (int) $id);
        $identifiers = collect(preg_split('/[\r\n,;]+/', $data['asset_identifiers'] ?? ''))
            ->map(fn ($value) => trim($value))
            ->filter()
            ->unique()
            ->values();

        if ($ids->isEmpty() && $identifiers->isEmpty()) {
            return back()->withInput()->with('error', 'Please select assets or paste/scanned asset barcodes.');
        }

        $assets = Asset::where('organization_id', $this->orgId())
            ->where(function ($query) use ($ids, $identifiers) {
                if ($ids->isNotEmpty()) {
                    $query->whereIn('id', $ids);
                }
                if ($identifiers->isNotEmpty()) {
                    $query->orWhereIn('asset_tag', $identifiers)
                        ->orWhereIn('serial_number', $identifiers);
                }
            })
            ->with('activeDisposal')
            ->get();

        $created = 0;
        $skipped = [];

        DB::transaction(function () use ($assets, $data, &$created, &$skipped) {
            foreach ($assets as $asset) {
                if (in_array($asset->status, ['disposed', 'lost'], true)) {
                    $skipped[] = $asset->asset_tag . ' is already ' . $asset->status;
                    continue;
                }

                if ($asset->activeDisposal) {
                    $skipped[] = $asset->asset_tag . ' already has an open disposal request';
                    continue;
                }

                AssetDisposal::create([
                    'organization_id' => $this->orgId(),
                    'asset_id' => $asset->id,
                    'requested_by' => auth()->id(),
                    'method' => $data['method'],
                    'status' => 'pending',
                    'requested_date' => $data['requested_date'],
                    'expected_value' => $data['expected_value'] ?? null,
                    'disposal_buyer_id' => $data['disposal_buyer_id'] ?? null,
                    'recipient_name' => $data['recipient_name'] ?? null,
                    'reason' => $data['reason'],
                ]);

                $created++;
            }
        });

        $matchedIdentifiers = $assets->flatMap(fn ($asset) => [$asset->asset_tag, $asset->serial_number])->filter()->values();
        $missingIdentifiers = $identifiers->diff($matchedIdentifiers);
        foreach ($missingIdentifiers as $identifier) {
            $skipped[] = $identifier . ' was not found';
        }

        $message = $created . ' disposal request' . ($created === 1 ? '' : 's') . ' created.';
        if (!empty($skipped)) {
            $message .= ' Skipped: ' . implode('; ', array_slice($skipped, 0, 8));
            if (count($skipped) > 8) {
                $message .= '; +' . (count($skipped) - 8) . ' more';
            }
        }

        return redirect()->route('admin.disposals.requests')->with($created > 0 ? 'success' : 'error', $message);
    }

    public function show(AssetDisposal $disposal)
    {
        $this->authorizeDisposal($disposal);
        $disposal->load(['asset.category', 'asset.supplier', 'asset.location', 'asset.activeAssignment.user', 'requestedBy', 'approvedBy', 'completedBy', 'disposalBuyer']);
        $buyers = $this->activeBuyers();

        return view('admin.disposals.show', compact('disposal', 'buyers'));
    }

    public function approve(Request $request, AssetDisposal $disposal)
    {
        $this->authorizeDisposal($disposal);
        abort_if($disposal->status !== 'pending', 422, 'Only pending disposal requests can be approved.');

        $data = $request->validate([
            'approval_notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $disposal->update([
            'status' => 'approved',
            'approved_by' => auth()->id(),
            'approved_date' => now()->toDateString(),
            'approval_notes' => $data['approval_notes'] ?? null,
        ]);

        return back()->with('success', 'Disposal request approved.');
    }

    public function reject(Request $request, AssetDisposal $disposal)
    {
        $this->authorizeDisposal($disposal);
        abort_if($disposal->status !== 'pending', 422, 'Only pending disposal requests can be rejected.');

        $data = $request->validate([
            'approval_notes' => ['required', 'string', 'max:2000'],
        ]);

        $disposal->update([
            'status' => 'rejected',
            'approved_by' => auth()->id(),
            'approved_date' => now()->toDateString(),
            'approval_notes' => $data['approval_notes'],
        ]);

        return back()->with('success', 'Disposal request rejected.');
    }

    public function complete(Request $request, AssetDisposal $disposal)
    {
        $this->authorizeDisposal($disposal);
        abort_if($disposal->status !== 'approved', 422, 'Only approved disposal requests can be completed.');

        $data = $request->validate([
            'disposed_date' => ['required', 'date'],
            'recovered_value' => ['nullable', 'numeric', 'min:0'],
            'disposal_cost' => ['nullable', 'numeric', 'min:0'],
            'disposal_buyer_id' => ['nullable', 'integer', 'exists:disposal_buyers,id'],
            'recipient_name' => ['nullable', 'string', 'max:255'],
            'payment_status' => ['required', 'in:not_required,pending,partial,paid'],
            'certificate_number' => ['nullable', 'string', 'max:255'],
            'handover_reference' => ['nullable', 'string', 'max:255'],
            'completion_notes' => ['nullable', 'string', 'max:2000'],
        ]);
        $this->assertBuyerBelongsToOrganization($data['disposal_buyer_id'] ?? null);
        $data = $this->applyBuyerSnapshot($data);

        DB::transaction(function () use ($disposal, $data) {
            $disposal->update([
                ...$data,
                'status' => 'completed',
                'completed_by' => auth()->id(),
            ]);

            AssetAssignment::where('asset_id', $disposal->asset_id)
                ->where('status', 'active')
                ->update([
                    'status' => 'returned',
                    'actual_return_date' => $data['disposed_date'],
                    'notes' => 'Auto-closed because asset was disposed.',
                ]);

            $assetStatus = in_array($disposal->method, ['lost', 'stolen'], true) ? 'lost' : 'disposed';

            $disposal->asset()->update([
                'status' => $assetStatus,
                'condition' => 'poor',
                'notes' => trim(($disposal->asset->notes ? $disposal->asset->notes . PHP_EOL : '') . 'Disposed on ' . $data['disposed_date'] . ' via ' . $disposal->method_label . '.'),
            ]);
        });

        return redirect()->route('admin.disposals.show', $disposal)->with('success', 'Asset disposal completed.');
    }

    public function cancel(AssetDisposal $disposal)
    {
        $this->authorizeDisposal($disposal);
        abort_if(!in_array($disposal->status, ['pending', 'approved'], true), 422, 'This disposal request cannot be cancelled.');

        $disposal->update(['status' => 'cancelled']);

        return back()->with('success', 'Disposal request cancelled.');
    }

    private function authorizeDisposal(AssetDisposal $disposal): void
    {
        abort_if($disposal->organization_id !== $this->orgId(), 403);
    }

    private function activeBuyers()
    {
        return DisposalBuyer::where('organization_id', $this->orgId())
            ->where('status', 'active')
            ->orderBy('name')
            ->get();
    }

    private function assertBuyerBelongsToOrganization(?int $buyerId): void
    {
        if (!$buyerId) {
            return;
        }

        abort_if(!DisposalBuyer::where('organization_id', $this->orgId())->whereKey($buyerId)->exists(), 403);
    }

    private function applyBuyerSnapshot(array $data): array
    {
        if (!empty($data['disposal_buyer_id']) && empty($data['recipient_name'])) {
            $data['recipient_name'] = DisposalBuyer::where('organization_id', $this->orgId())
                ->whereKey($data['disposal_buyer_id'])
                ->value('name');
        }

        return $data;
    }
}
