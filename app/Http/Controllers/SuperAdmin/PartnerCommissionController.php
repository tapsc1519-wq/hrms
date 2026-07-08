<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\Partner;
use App\Models\PartnerCommission;
use App\Models\Product;
use Illuminate\Http\Request;

class PartnerCommissionController extends Controller
{
    public function index(Request $request)
    {
        $query = PartnerCommission::with(['partner', 'product', 'organization'])
            ->latest('payment_date')
            ->latest();

        if ($request->filled('partner_id')) {
            $query->where('partner_id', $request->partner_id);
        }

        if ($request->filled('product_id')) {
            $query->where('product_id', $request->product_id);
        }

        if ($request->filled('organization_id')) {
            $query->where('organization_id', $request->organization_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('payment_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('payment_date', '<=', $request->date_to);
        }

        $summaryQuery = clone $query;
        $commissions = $query->paginate(20)->withQueryString();
        $partners = Partner::orderBy('name')->get(['id', 'name', 'company_name']);
        $products = Product::orderBy('sort_order')->orderBy('name')->get(['id', 'name']);
        $organizations = Organization::orderBy('name')->get(['id', 'name']);
        $statuses = $this->statuses();

        $pendingAmount = (clone $summaryQuery)->where('status', 'pending')->sum('commission_amount');
        $approvedAmount = (clone $summaryQuery)->where('status', 'approved')->sum('commission_amount');
        $paidAmount = (clone $summaryQuery)->where('status', 'paid')->sum('commission_amount');
        $totalAmount = (clone $summaryQuery)->sum('commission_amount');

        return view('super-admin.partner-commissions.index', compact(
            'commissions',
            'partners',
            'products',
            'organizations',
            'statuses',
            'pendingAmount',
            'approvedAmount',
            'paidAmount',
            'totalAmount'
        ));
    }

    public function approve(PartnerCommission $partnerCommission)
    {
        if ($partnerCommission->status !== 'pending') {
            return back()->with('error', 'Only pending commissions can be approved.');
        }

        $partnerCommission->update([
            'status' => 'approved',
            'approved_at' => now(),
            'approved_by' => auth()->id(),
        ]);

        return back()->with('success', 'Commission approved.');
    }

    public function markPaid(PartnerCommission $partnerCommission)
    {
        if (!in_array($partnerCommission->status, ['pending', 'approved'], true)) {
            return back()->with('error', 'Only pending or approved commissions can be marked as paid.');
        }

        $partnerCommission->update([
            'status' => 'paid',
            'approved_at' => $partnerCommission->approved_at ?: now(),
            'approved_by' => $partnerCommission->approved_by ?: auth()->id(),
            'paid_at' => now(),
            'paid_by' => auth()->id(),
        ]);

        return back()->with('success', 'Commission marked as paid.');
    }

    public function cancel(PartnerCommission $partnerCommission)
    {
        if ($partnerCommission->status === 'paid') {
            return back()->with('error', 'Paid commissions cannot be cancelled.');
        }

        $partnerCommission->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
            'cancelled_by' => auth()->id(),
        ]);

        return back()->with('success', 'Commission cancelled.');
    }

    private function statuses(): array
    {
        return [
            'pending' => 'Pending',
            'approved' => 'Approved',
            'paid' => 'Paid',
            'cancelled' => 'Cancelled',
        ];
    }
}
