<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\PurchaseOrder;
use App\Models\Supplier;

class DashboardController extends Controller
{
    public function index()
    {
        $vendor = Supplier::where('user_id', auth()->id())->firstOrFail();

        $stats = [
            'total_pos'     => PurchaseOrder::where('vendor_id', $vendor->id)->count(),
            'pending_pos'   => PurchaseOrder::where('vendor_id', $vendor->id)->whereIn('status', ['sent', 'confirmed'])->count(),
            'pending_invoices' => Invoice::where('vendor_id', $vendor->id)->whereIn('status', ['pending', 'overdue'])->count(),
            'total_revenue' => Invoice::where('vendor_id', $vendor->id)->where('status', 'paid')->sum('total_amount'),
        ];

        $recentOrders = PurchaseOrder::where('vendor_id', $vendor->id)
            ->with('organization')
            ->latest()->take(5)->get();

        return view('vendor-portal.dashboard', compact('vendor', 'stats', 'recentOrders'));
    }
}
