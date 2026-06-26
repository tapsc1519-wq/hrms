<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\AssetCategory;
use App\Models\Facility;
use App\Models\GoodsReceipt;
use App\Models\GoodsReceiptItem;
use App\Models\Location;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
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
        $purchaseOrder->load([
            'supplier',
            'items.category',
            'createdBy',
            'approvedBy',
            'invoice',
            'goodsReceipts.receivedBy',
            'goodsReceipts.items',
        ]);
        return view('admin.purchase-orders.show', compact('purchaseOrder'));
    }

    public function receive(PurchaseOrder $purchaseOrder)
    {
        abort_if($purchaseOrder->organization_id !== $this->orgId(), 403);
        abort_if(!in_array($purchaseOrder->status, ['sent', 'confirmed', 'partially_received'], true), 422, 'This purchase order cannot receive items.');

        $purchaseOrder->load(['supplier', 'items.category']);
        abort_if($purchaseOrder->items->every(fn($item) => $item->pending_quantity === 0), 422, 'All purchase order items have already been received.');

        $facilities = Facility::where('organization_id', $this->orgId())
            ->where('status', 'active')
            ->with('activeLocations')
            ->orderBy('name')
            ->get();

        return view('admin.purchase-orders.receive', compact('purchaseOrder', 'facilities'));
    }

    public function storeReceipt(Request $request, PurchaseOrder $purchaseOrder)
    {
        abort_if($purchaseOrder->organization_id !== $this->orgId(), 403);
        abort_if(!in_array($purchaseOrder->status, ['sent', 'confirmed', 'partially_received'], true), 422, 'This purchase order cannot receive items.');

        $validated = $request->validate([
            'received_date' => ['required', 'date'],
            'invoice_number' => ['nullable', 'string', 'max:255'],
            'invoice_date' => ['nullable', 'date'],
            'delivery_note_number' => ['nullable', 'string', 'max:255'],
            'location_id' => ['nullable', 'exists:locations,id'],
            'condition' => ['required', 'in:excellent,good,fair,poor'],
            'warranty_expiry_date' => ['nullable', 'date'],
            'warranty_terms' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array'],
            'items.*.quantity' => ['nullable', 'integer', 'min:0'],
            'items.*.rejected_quantity' => ['nullable', 'integer', 'min:0'],
            'items.*.serial_numbers' => ['nullable', 'string'],
            'items.*.asset_tags' => ['nullable', 'string'],
            'items.*.notes' => ['nullable', 'string'],
        ]);

        if (!empty($validated['location_id']) && !Location::where('organization_id', $this->orgId())->whereKey($validated['location_id'])->exists()) {
            throw ValidationException::withMessages(['location_id' => 'The selected stock location does not belong to your organization.']);
        }

        $receipt = DB::transaction(function () use ($validated, $purchaseOrder) {
            $lockedOrder = PurchaseOrder::whereKey($purchaseOrder->id)
                ->where('organization_id', $this->orgId())
                ->lockForUpdate()
                ->firstOrFail();

            $items = PurchaseOrderItem::where('purchase_order_id', $lockedOrder->id)
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $receiptLines = [];
            $hasReceivedItems = false;
            $submittedSerials = [];
            $submittedTags = [];

            foreach ($validated['items'] as $itemId => $line) {
                $item = $items->get((int) $itemId);
                if (!$item) {
                    throw ValidationException::withMessages(['items' => 'An invalid purchase order item was submitted.']);
                }

                $quantity = (int) ($line['quantity'] ?? 0);
                $rejected = (int) ($line['rejected_quantity'] ?? 0);
                $pending = max(0, $item->quantity - $item->received_quantity);

                if (($quantity + $rejected) > $pending) {
                    throw ValidationException::withMessages([
                        "items.{$itemId}.quantity" => "Received and rejected quantity cannot exceed {$pending} pending units for {$item->item_name}.",
                    ]);
                }

                if ($quantity === 0 && $rejected === 0) {
                    continue;
                }

                $serials = $this->lines($line['serial_numbers'] ?? '');
                $tags = $this->lines($line['asset_tags'] ?? '');

                if ($quantity > 0 && count($serials) !== $quantity) {
                    throw ValidationException::withMessages([
                        "items.{$itemId}.serial_numbers" => "Enter exactly {$quantity} serial number(s), one per received unit.",
                    ]);
                }

                if ($tags !== [] && count($tags) !== $quantity) {
                    throw ValidationException::withMessages([
                        "items.{$itemId}.asset_tags" => "Enter exactly {$quantity} asset tag(s), or leave the field blank for automatic tags.",
                    ]);
                }

                if (count($serials) !== count(array_unique(array_map('strtolower', $serials)))) {
                    throw ValidationException::withMessages([
                        "items.{$itemId}.serial_numbers" => "Duplicate serial numbers were entered for {$item->item_name}.",
                    ]);
                }

                foreach ($serials as $serial) {
                    $normalized = strtolower($serial);
                    if (isset($submittedSerials[$normalized])) {
                        throw ValidationException::withMessages([
                            "items.{$itemId}.serial_numbers" => "Serial number {$serial} appears more than once in this receipt.",
                        ]);
                    }
                    if (Asset::where('organization_id', $this->orgId())->whereRaw('LOWER(serial_number) = ?', [$normalized])->exists()) {
                        throw ValidationException::withMessages([
                            "items.{$itemId}.serial_numbers" => "Serial number {$serial} already exists in the asset register.",
                        ]);
                    }
                    $submittedSerials[$normalized] = true;
                }

                foreach ($tags as $tag) {
                    $normalized = strtolower($tag);
                    if (isset($submittedTags[$normalized])) {
                        throw ValidationException::withMessages([
                            "items.{$itemId}.asset_tags" => "Asset tag {$tag} appears more than once in this receipt.",
                        ]);
                    }
                    if (Asset::whereRaw('LOWER(asset_tag) = ?', [$normalized])->exists()) {
                        throw ValidationException::withMessages([
                            "items.{$itemId}.asset_tags" => "Asset tag {$tag} already exists.",
                        ]);
                    }
                    $submittedTags[$normalized] = true;
                }

                $receiptLines[] = compact('item', 'quantity', 'rejected', 'serials', 'tags') + [
                    'notes' => $line['notes'] ?? null,
                ];
                $hasReceivedItems = $hasReceivedItems || $quantity > 0;
            }

            if ($receiptLines === []) {
                throw ValidationException::withMessages(['items' => 'Enter at least one received or rejected quantity.']);
            }

            $receipt = GoodsReceipt::create([
                'organization_id' => $this->orgId(),
                'purchase_order_id' => $lockedOrder->id,
                'received_by' => auth()->id(),
                'receipt_number' => $this->nextReceiptNumber(),
                'received_date' => $validated['received_date'],
                'invoice_number' => $validated['invoice_number'] ?? null,
                'invoice_date' => $validated['invoice_date'] ?? null,
                'delivery_note_number' => $validated['delivery_note_number'] ?? null,
                'notes' => $validated['notes'] ?? null,
            ]);

            foreach ($receiptLines as $line) {
                $item = $line['item'];

                GoodsReceiptItem::create([
                    'goods_receipt_id' => $receipt->id,
                    'purchase_order_item_id' => $item->id,
                    'received_quantity' => $line['quantity'],
                    'rejected_quantity' => $line['rejected'],
                    'notes' => $line['notes'],
                ]);

                for ($index = 0; $index < $line['quantity']; $index++) {
                    Asset::create([
                        'organization_id' => $this->orgId(),
                        'acquisition_source' => 'purchase_order',
                        'purchase_order_id' => $lockedOrder->id,
                        'purchase_order_item_id' => $item->id,
                        'goods_receipt_id' => $receipt->id,
                        'category_id' => $item->category_id,
                        'vendor_id' => $lockedOrder->vendor_id,
                        'location_id' => $validated['location_id'] ?? null,
                        'name' => $item->item_name,
                        'asset_tag' => $line['tags'][$index] ?? $this->uniqueAssetTag(),
                        'serial_number' => $line['serials'][$index],
                        'model' => $item->model,
                        'brand' => $item->brand,
                        'specifications' => $item->specifications,
                        'description' => $item->description,
                        'purchase_date' => $validated['invoice_date'] ?? $validated['received_date'],
                        'purchase_price' => $item->unit_price,
                        'warranty_expiry_date' => $validated['warranty_expiry_date'] ?? null,
                        'warranty_terms' => $validated['warranty_terms'] ?? null,
                        'status' => 'available',
                        'condition' => $validated['condition'],
                        'notes' => 'Created from receipt ' . $receipt->receipt_number,
                    ]);
                }

                $item->increment('received_quantity', $line['quantity']);
            }

            $allReceived = !PurchaseOrderItem::where('purchase_order_id', $lockedOrder->id)
                ->whereColumn('received_quantity', '<', 'quantity')
                ->exists();

            $lockedOrder->update([
                'status' => $allReceived
                    ? 'received'
                    : ($hasReceivedItems ? 'partially_received' : $lockedOrder->status),
                'actual_delivery_date' => $allReceived ? $validated['received_date'] : $lockedOrder->actual_delivery_date,
            ]);

            return $receipt;
        });

        return redirect()->route('admin.purchase-orders.show', $purchaseOrder)
            ->with('success', "Receipt {$receipt->receipt_number} recorded and linked assets created.");
    }

    public function updateStatus(Request $request, PurchaseOrder $purchaseOrder)
    {
        abort_if($purchaseOrder->organization_id !== $this->orgId(), 403);
        $request->validate(['status' => 'required|in:draft,sent,confirmed,cancelled']);

        $allowedTransitions = [
            'draft' => ['sent', 'confirmed', 'cancelled'],
            'sent' => ['confirmed', 'cancelled'],
            'confirmed' => ['cancelled'],
            'partially_received' => [],
            'received' => [],
            'cancelled' => [],
        ];

        abort_unless(
            in_array($request->status, $allowedTransitions[$purchaseOrder->status] ?? [], true),
            422,
            'This purchase order status transition is not allowed.'
        );

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

    private function lines(string $value): array
    {
        return collect(preg_split('/[\r\n,]+/', $value) ?: [])
            ->map(fn($line) => trim($line))
            ->filter()
            ->values()
            ->all();
    }

    private function nextReceiptNumber(): string
    {
        $prefix = 'GRN-' . now()->format('Y') . '-';
        $number = GoodsReceipt::where('organization_id', $this->orgId())
            ->where('receipt_number', 'like', $prefix . '%')
            ->count() + 1;

        do {
            $receiptNumber = $prefix . str_pad((string) $number, 4, '0', STR_PAD_LEFT);
            $number++;
        } while (GoodsReceipt::where('organization_id', $this->orgId())->where('receipt_number', $receiptNumber)->exists());

        return $receiptNumber;
    }

    private function uniqueAssetTag(): string
    {
        do {
            $tag = 'ASSET-' . strtoupper(Str::random(8));
        } while (Asset::where('asset_tag', $tag)->exists());

        return $tag;
    }
}
