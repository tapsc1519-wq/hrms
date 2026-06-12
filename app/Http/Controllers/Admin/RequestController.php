<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\AssetAssignment;
use App\Models\AssetRequest;
use Illuminate\Http\Request;

class RequestController extends Controller
{


    public function index(Request $request)
    {
        $query = AssetRequest::where('organization_id', $this->orgId())
            ->with(['requester', 'asset', 'category']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }

        $requests = $query->latest()->paginate(20)->withQueryString();

        return view('admin.requests.index', compact('requests'));
    }

    public function show(AssetRequest $assetRequest)
    {
        abort_if($assetRequest->organization_id !== $this->orgId(), 403);
        $assetRequest->load(['requester', 'asset', 'category', 'reviewedBy']);
        $availableAssets = Asset::where('organization_id', $this->orgId())
            ->where('status', 'available')
            ->when($assetRequest->category_id, fn($q) => $q->where('category_id', $assetRequest->category_id))
            ->orderBy('name')->get();

        return view('admin.requests.show', compact('assetRequest', 'availableAssets'));
    }

    public function approve(Request $request, AssetRequest $assetRequest)
    {
        abort_if($assetRequest->organization_id !== $this->orgId(), 403);

        $request->validate(['review_notes' => 'nullable|string']);

        $assetRequest->update([
            'status'      => 'approved',
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
            'review_notes' => $request->review_notes,
        ]);

        return back()->with('success', 'Request approved.');
    }

    public function reject(Request $request, AssetRequest $assetRequest)
    {
        abort_if($assetRequest->organization_id !== $this->orgId(), 403);

        $request->validate(['review_notes' => 'required|string']);

        $assetRequest->update([
            'status'      => 'rejected',
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
            'review_notes' => $request->review_notes,
        ]);

        return back()->with('success', 'Request rejected.');
    }

    public function fulfill(Request $request, AssetRequest $assetRequest)
    {
        abort_if($assetRequest->organization_id !== $this->orgId(), 403);

        $request->validate([
            'fulfilled_asset_ids' => 'required|array|min:' . max(1, (int) $assetRequest->quantity),
            'fulfilled_asset_ids.*' => 'exists:assets,id',
            'assigned_date'      => 'required|date',
        ]);

        $assets = Asset::whereIn('id', $request->fulfilled_asset_ids)
            ->where('organization_id', $this->orgId())
            ->where('status', 'available')
            ->when($assetRequest->category_id, fn($q) => $q->where('category_id', $assetRequest->category_id))
            ->get();

        if ($assets->count() < max(1, (int) $assetRequest->quantity)) {
            return back()->withErrors(['fulfilled_asset_ids' => 'Please select at least ' . $assetRequest->quantity . ' available asset(s) from the requested category.']);
        }

        foreach ($assets as $asset) {
            AssetAssignment::create([
                'asset_id'       => $asset->id,
                'user_id'        => $assetRequest->requester_id,
                'assigned_by'    => auth()->id(),
                'assigned_date'  => $request->assigned_date,
                'status'         => 'active',
                'condition_out'  => 'good',
                'purpose'        => $assetRequest->reason,
            ]);

            $asset->update(['status' => 'assigned']);
        }

        $assetRequest->update([
            'status'            => 'fulfilled',
            'fulfilled_asset_id'=> $assets->first()->id,
            'fulfilled_at'      => now(),
        ]);

        return back()->with('success', 'Request fulfilled and asset assigned.');
    }
}
