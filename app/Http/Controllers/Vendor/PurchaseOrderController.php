<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\PurchaseOrder;
use App\Models\Supplier;

class PurchaseOrderController extends Controller
{
    private function vendorOrFail(): Vendor
    {
        return Supplier::where('user_id', auth()->id())->firstOrFail();
    }

    public function index()
    {
        $vendor = $this->vendorOrFail();

        $orders = PurchaseOrder::where('vendor_id', $vendor->id)
            ->with('organization')
            ->latest()
            ->paginate(15);

        return view('vendor-portal.purchase-orders.index', compact('orders', 'vendor'));
    }

    public function show(PurchaseOrder $purchaseOrder)
    {
        $vendor = $this->vendorOrFail();
        abort_if($purchaseOrder->vendor_id !== $vendor->id, 403);

        $purchaseOrder->load(['organization', 'items.category', 'createdBy']);

        return view('vendor-portal.purchase-orders.show', compact('purchaseOrder', 'vendor'));
    }
}
