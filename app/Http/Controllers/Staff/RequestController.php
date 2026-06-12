<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\AssetCategory;
use App\Models\AssetRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class RequestController extends Controller
{
    public function index()
    {
        $requests = AssetRequest::where('requester_id', auth()->id())
            ->with(['asset', 'category', 'reviewedBy'])
            ->latest()->paginate(20);

        return view('staff.requests.index', compact('requests'));
    }

    public function create()
    {
        $orgId = auth()->user()->organization_id;
        $categories = AssetCategory::where('organization_id', $orgId)->orderBy('name')->get();

        return view('staff.requests.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'request_type' => 'required|in:new_asset,replacement,additional,temporary',
            'category_id'   => 'required|exists:asset_categories,id',
            'quantity'      => 'required|integer|min:1|max:100',
            'required_date' => 'nullable|date|after:today',
            'reason'        => 'required|string|max:1000',
            'priority'      => 'required|in:low,normal,high,urgent',
        ]);

        $count = AssetRequest::where('organization_id', auth()->user()->organization_id)->count();

        AssetRequest::create([
            ...$validated,
            'organization_id' => auth()->user()->organization_id,
            'requester_id'    => auth()->id(),
            'request_number'  => 'REQ-' . date('Y') . '-' . str_pad($count + 1, 5, '0', STR_PAD_LEFT),
            'request_date'    => today(),
            'status'          => 'pending',
        ]);

        return redirect()->route('staff.requests.index')
            ->with('success', 'Asset request submitted successfully.');
    }

    public function show(AssetRequest $assetRequest)
    {
        abort_if($assetRequest->requester_id !== auth()->id(), 403);
        return view('staff.requests.show', compact('assetRequest'));
    }

    public function cancel(AssetRequest $assetRequest)
    {
        abort_if($assetRequest->requester_id !== auth()->id(), 403);
        abort_if($assetRequest->status !== 'pending', 403);
        $assetRequest->update(['status' => 'cancelled']);
        return back()->with('success', 'Request cancelled.');
    }
}
