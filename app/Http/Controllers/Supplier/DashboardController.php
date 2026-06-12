<?php

namespace App\Http\Controllers\Supplier;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\PurchaseOrder;
use App\Models\Supplier;

class DashboardController extends Controller
{
    public function index()
    {
        $supplier = Supplier::where('user_id', auth()->id())->firstOrFail();

        $stats = [
            'total_pos'        => PurchaseOrder::where('vendor_id', $supplier->id)->count(),
            'pending_pos'      => PurchaseOrder::where('vendor_id', $supplier->id)->whereIn('status', ['sent', 'confirmed'])->count(),
            'pending_invoices' => Invoice::where('vendor_id', $supplier->id)->whereIn('status', ['pending', 'overdue'])->count(),
            'total_revenue'    => Invoice::where('vendor_id', $supplier->id)->where('status', 'paid')->sum('total_amount'),
        ];

        $recentOrders = PurchaseOrder::where('vendor_id', $supplier->id)
            ->with('organization')
            ->latest()->take(5)->get();

        return view('supplier-portal.dashboard', compact('supplier', 'stats', 'recentOrders'));
    }
}
