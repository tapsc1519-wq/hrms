<?php

namespace App\Http\Controllers\Supplier;

use App\Http\Controllers\Controller;
use App\Models\PurchaseOrder;
use App\Models\Supplier;

class PurchaseOrderController extends Controller
{
    private function supplierOrFail(): Supplier
    {
        return Supplier::where('user_id', auth()->id())->firstOrFail();
    }

    public function index()
    {
        $supplier = $this->supplierOrFail();

        $orders = PurchaseOrder::where('vendor_id', $supplier->id)
            ->with('organization')
            ->latest()
            ->paginate(15);

        return view('supplier-portal.purchase-orders.index', compact('orders', 'supplier'));
    }

    public function show(PurchaseOrder $purchaseOrder)
    {
        $supplier = $this->supplierOrFail();
        abort_if($purchaseOrder->vendor_id !== $supplier->id, 403);

        $purchaseOrder->load(['organization', 'items.category', 'createdBy']);

        return view('supplier-portal.purchase-orders.show', compact('purchaseOrder', 'supplier'));
    }
}
