<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AssetCategory;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PurchaseOrderController extends Controller
{


    public function index(Request $request)
    {
        $query = PurchaseOrder::where('organization_id', $this->orgId())
            ->with(['supplier', 'createdBy']);

        if ($request->filled('search')) {
            $query->where('po_number', 'like', '%' . $request->search . '%');
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('vendor_id')) {
            $query->where('vendor_id', $request->vendor_id);
        }

        $orders = $query->latest()->paginate(20)->withQueryString();
        $suppliers = Supplier::where('organization_id', $this->orgId())->where('status', 'active')->orderBy('name')->get();

        return view('admin.purchase-orders.index', compact('orders', 'suppliers'));
    }

    public function create()
    {
        $suppliers  = Supplier::where('organization_id', $this->orgId())->where('status', 'active')->orderBy('name')->get();
        $categories = AssetCategory::where('organization_id', $this->orgId())->orderBy('name')->get();
        $poNumber   = 'PO-' . date('Y') . '-' . str_pad(PurchaseOrder::where('organization_id', $this->orgId())->count() + 1, 4, '0', STR_PAD_LEFT);

        return view('admin.purchase-orders.create', compact('suppliers', 'categories', 'poNumber'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'vendor_id'              => 'required|exists:suppliers,id',
            'po_number'              => 'required|string|unique:purchase_orders,po_number',
            'order_date'             => 'required|date',
            'expected_delivery_date' => 'nullable|date',
            'status'                 => 'required|in:draft,sent,confirmed',
            'tax_amount'             => 'nullable|numeric|min:0',
            'discount_amount'        => 'nullable|numeric|min:0',
            'shipping_address'       => 'nullable|string',
            'terms_conditions'       => 'nullable|string',
            'notes'                  => 'nullable|string',
            'items'                  => 'required|array|min:1',
            'items.*.item_name'      => 'required|string|max:255',
            'items.*.quantity'       => 'required|integer|min:1',
            'items.*.unit_price'     => 'required|numeric|min:0',
            'items.*.tax_rate'       => 'nullable|numeric|min:0|max:100',
            'items.*.category_id'    => 'nullable|exists:asset_categories,id',
        ]);

        $subtotal = 0;
        foreach ($request->items as $item) {
            $subtotal += $item['quantity'] * $item['unit_price'];
        }

        $po = PurchaseOrder::create([
            'organization_id'        => $this->orgId(),
            'vendor_id'              => $validated['vendor_id'],
            'created_by'             => auth()->id(),
            'po_number'              => $validated['po_number'],
            'order_date'             => $validated['order_date'],
            'expected_delivery_date' => $validated['expected_delivery_date'] ?? null,
            'status'                 => $validated['status'],
            'subtotal'               => $subtotal,
            'tax_amount'             => $validated['tax_amount'] ?? 0,
            'discount_amount'        => $validated['discount_amount'] ?? 0,
            'total_amount'           => $subtotal + ($validated['tax_amount'] ?? 0) - ($validated['discount_amount'] ?? 0),
            'shipping_address'       => $validated['shipping_address'] ?? null,
            'terms_conditions'       => $validated['terms_conditions'] ?? null,
            'notes'                  => $validated['notes'] ?? null,
        ]);

        foreach ($request->items as $item) {
            PurchaseOrderItem::create([
                'purchase_order_id' => $po->id,
                'category_id'       => $item['category_id'] ?? null,
                'item_name'         => $item['item_name'],
                'brand'             => $item['brand'] ?? null,
                'model'             => $item['model'] ?? null,
                'description'       => $item['description'] ?? null,
                'quantity'          => $item['quantity'],
                'unit_price'        => $item['unit_price'],
                'tax_rate'          => $item['tax_rate'] ?? 0,
                'total_price'       => $item['quantity'] * $item['unit_price'],
            ]);
        }

        return redirect()->route('admin.purchase-orders.show', $po)
            ->with('success', 'Purchase order created successfully.');
    }

    public function show(PurchaseOrder $purchaseOrder)
    {
        abort_if($purchaseOrder->organization_id !== $this->orgId(), 403);
        $purchaseOrder->load(['supplier', 'items.category', 'createdBy', 'approvedBy', 'invoice']);
        return view('admin.purchase-orders.show', compact('purchaseOrder'));
    }

    public function updateStatus(Request $request, PurchaseOrder $purchaseOrder)
    {
        abort_if($purchaseOrder->organization_id !== $this->orgId(), 403);
        $request->validate(['status' => 'required|in:draft,sent,confirmed,partially_received,received,cancelled']);
        $purchaseOrder->update(['status' => $request->status]);
        return back()->with('success', 'Purchase order status updated.');
    }

    public function destroy(PurchaseOrder $purchaseOrder)
    {
        abort_if($purchaseOrder->organization_id !== $this->orgId(), 403);
        abort_if(!in_array($purchaseOrder->status, ['draft', 'cancelled']), 403, 'Only draft or cancelled POs can be deleted.');
        $purchaseOrder->delete();
        return redirect()->route('admin.purchase-orders.index')
            ->with('success', 'Purchase order deleted.');
    }
}
