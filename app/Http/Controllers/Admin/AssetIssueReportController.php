<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AssetDisposal;
use App\Models\AssetIssueReport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AssetIssueReportController extends Controller
{
    public function index(Request $request)
    {
        $query = AssetIssueReport::where('organization_id', $this->orgId())
            ->with(['asset.category', 'reportedBy.department', 'reviewedBy', 'disposal'])
            ->latest('reported_date')
            ->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        } else {
            $query->whereIn('status', ['open', 'under_review']);
        }

        if ($request->filled('issue_type')) {
            $query->where('issue_type', $request->issue_type);
        }

        if ($request->filled('search')) {
            $query->where(function ($searchQuery) use ($request) {
                $searchQuery->whereHas('asset', function ($assetQuery) use ($request) {
                    $assetQuery->where('name', 'like', '%' . $request->search . '%')
                        ->orWhere('asset_tag', 'like', '%' . $request->search . '%')
                        ->orWhere('serial_number', 'like', '%' . $request->search . '%');
                })->orWhereHas('reportedBy', function ($userQuery) use ($request) {
                    $userQuery->where('name', 'like', '%' . $request->search . '%')
                        ->orWhere('email', 'like', '%' . $request->search . '%');
                });
            });
        }

        $issues = $query->paginate(20)->withQueryString();

        $stats = [
            'open' => AssetIssueReport::where('organization_id', $this->orgId())->where('status', 'open')->count(),
            'under_review' => AssetIssueReport::where('organization_id', $this->orgId())->where('status', 'under_review')->count(),
            'converted' => AssetIssueReport::where('organization_id', $this->orgId())->where('status', 'converted_to_disposal')->count(),
            'resolved' => AssetIssueReport::where('organization_id', $this->orgId())->where('status', 'resolved')->count(),
        ];

        return view('admin.asset-issues.index', compact('issues', 'stats'));
    }

    public function show(AssetIssueReport $assetIssue)
    {
        $this->authorizeIssue($assetIssue);
        $assetIssue->load([
            'asset.category',
            'asset.location',
            'asset.activeAssignment.user',
            'assignment',
            'reportedBy.department',
            'reviewedBy',
            'disposal',
        ]);

        return view('admin.asset-issues.show', compact('assetIssue'));
    }

    public function review(Request $request, AssetIssueReport $assetIssue)
    {
        $this->authorizeIssue($assetIssue);
        abort_if(!in_array($assetIssue->status, ['open', 'under_review'], true), 422, 'This issue report cannot be updated.');

        $data = $request->validate([
            'status' => ['required', 'in:under_review,resolved,rejected'],
            'review_notes' => ['nullable', 'required_if:status,rejected', 'string', 'max:2000'],
        ]);

        $assetIssue->update([
            'status' => $data['status'],
            'review_notes' => $data['review_notes'] ?? null,
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);

        return back()->with('success', 'Asset issue report updated.');
    }

    public function createDisposal(Request $request, AssetIssueReport $assetIssue)
    {
        $this->authorizeIssue($assetIssue);
        abort_if(!in_array($assetIssue->status, ['open', 'under_review'], true), 422, 'This issue report cannot be converted.');

        $data = $request->validate([
            'method' => ['required', 'in:scrap,sell,donate,recycle,return_to_supplier,destroy,lost,stolen'],
            'requested_date' => ['required', 'date'],
            'expected_value' => ['nullable', 'numeric', 'min:0'],
            'recipient_name' => ['nullable', 'string', 'max:255'],
            'reason' => ['required', 'string', 'max:2000'],
        ]);

        $assetIssue->load('asset.activeDisposal');
        abort_if(in_array($assetIssue->asset->status, ['disposed', 'lost'], true), 422, 'This asset cannot be disposed.');
        abort_if($assetIssue->asset->activeDisposal, 422, 'This asset already has an open disposal request.');

        $disposal = DB::transaction(function () use ($assetIssue, $data) {
            $disposal = AssetDisposal::create([
                ...$data,
                'organization_id' => $this->orgId(),
                'asset_id' => $assetIssue->asset_id,
                'requested_by' => auth()->id(),
                'status' => 'pending',
                'reason' => trim($data['reason'] . PHP_EOL . PHP_EOL . 'Employee report: ' . $assetIssue->description),
            ]);

            $assetIssue->update([
                'status' => 'converted_to_disposal',
                'asset_disposal_id' => $disposal->id,
                'review_notes' => $data['reason'],
                'reviewed_by' => auth()->id(),
                'reviewed_at' => now(),
            ]);

            return $disposal;
        });

        return redirect()->route('admin.disposals.show', $disposal)->with('success', 'Disposal request created from employee issue report.');
    }

    private function authorizeIssue(AssetIssueReport $assetIssue): void
    {
        abort_if($assetIssue->organization_id !== $this->orgId(), 403);
    }
}
