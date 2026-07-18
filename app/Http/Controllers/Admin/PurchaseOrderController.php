<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\AssetBrand;
use App\Models\AssetCategory;
use App\Models\AssetModel;
use App\Models\Facility;
use App\Models\GoodsReceipt;
use App\Models\GoodsReceiptItem;
use App\Models\Location;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Software;
use App\Models\SoftwareAssignment;
use App\Models\SoftwareLicense;
use App\Models\SoftwareRequest;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;
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

    public function create(Request $request)
    {
        $suppliers  = Supplier::where('organization_id', $this->orgId())->where('status', 'active')->orderBy('name')->get();
        $categories = AssetCategory::where('organization_id', $this->orgId())
            ->orderBy('name')
            ->get()
            ->reject(fn (AssetCategory $category) => str($category->name)->lower()->trim()->is([
                'software',
                'softwares',
                'software license',
                'software licenses',
            ]))
            ->values();
        $brands = AssetBrand::where('organization_id', $this->orgId())->where('is_active', true)->orderBy('name')->get();
        $models = AssetModel::where('organization_id', $this->orgId())->where('is_active', true)->with('brand')->orderBy('name')->get();
        $softwareList = Software::where('organization_id', $this->orgId())->orderBy('name')->get(['id', 'name', 'vendor']);
        $poNumber   = 'PO-' . date('Y') . '-' . str_pad(PurchaseOrder::where('organization_id', $this->orgId())->count() + 1, 4, '0', STR_PAD_LEFT);

        $requestIds = collect($request->input('software_request_ids', old('software_request_ids', [])))
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values();

        $softwareDemand = collect();
        if ($requestIds->isNotEmpty()) {
            $softwareRequests = SoftwareRequest::where('organization_id', $this->orgId())
                ->whereIn('id', $requestIds)
                ->where('status', 'approved')
                ->whereNull('purchase_order_item_id')
                ->with(['software', 'requester'])
                ->get();

            if ($softwareRequests->count() !== $requestIds->count()) {
                throw ValidationException::withMessages([
                    'software_request_ids' => 'One or more selected requests are no longer approved or are already linked to procurement.',
                ]);
            }

            $softwareDemand = $softwareRequests->groupBy('software_id')->map(function ($requests) {
                $software = $requests->first()->software;

                return [
                    'software_id' => $software->id,
                    'item_name' => $software->name . ' license',
                    'brand' => $software->vendor,
                    'quantity' => $requests->count(),
                    'software_request_ids' => $requests->pluck('id')->values()->all(),
                    'employee_names' => $requests->pluck('requester.name')->values()->all(),
                ];
            })->values();
        }

        return view('admin.purchase-orders.create', compact('suppliers', 'categories', 'brands', 'models', 'softwareList', 'softwareDemand', 'poNumber'));
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
            'items.*.item_type'      => 'required|in:asset,software',
            'items.*.item_name'      => 'nullable|string|max:255',
            'items.*.quantity'       => 'required|integer|min:1',
            'items.*.unit_price'     => 'required|numeric|min:0',
            'items.*.tax_rate'       => 'nullable|numeric|min:0|max:100',
            'items.*.category_id'    => ['nullable', Rule::exists('asset_categories', 'id')->where('organization_id', $this->orgId())],
            'items.*.asset_brand_id' => ['nullable', Rule::exists('asset_brands', 'id')->where('organization_id', $this->orgId())],
            'items.*.asset_model_id' => ['nullable', Rule::exists('asset_models', 'id')->where('organization_id', $this->orgId())],
            'items.*.software_id'    => ['nullable', Rule::exists('software', 'id')->where('organization_id', $this->orgId())],
            'items.*.license_type'   => 'nullable|in:perpetual,subscription,concurrent,per_seat,per_device,oem,volume,open_source,freeware',
            'items.*.subscription_period' => 'nullable|in:monthly,quarterly,annual,multi_year,perpetual',
            'items.*.brand'          => 'nullable|string|max:255',
            'items.*.model'          => 'nullable|string|max:255',
            'items.*.description'    => 'nullable|string|max:2000',
            'items.*.software_request_ids' => 'nullable|array',
            'items.*.software_request_ids.*' => 'integer|exists:software_requests,id',
        ]);

        abort_unless(
            Supplier::where('organization_id', $this->orgId())->whereKey($validated['vendor_id'])->exists(),
            403
        );

        $subtotal = 0;
        foreach ($validated['items'] as $item) {
            $subtotal += $item['quantity'] * $item['unit_price'];
        }

        $po = DB::transaction(function () use ($validated, $subtotal) {
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

            foreach ($validated['items'] as $index => $item) {
                $resolvedItem = $this->resolvePurchaseOrderItem($item, $index);

                if ($item['item_type'] === 'software') {
                    if (empty($item['software_id'])) {
                        throw ValidationException::withMessages(["items.{$index}.software_id" => 'Select the software being purchased.']);
                    }

                    abort_unless(
                        Software::where('organization_id', $this->orgId())->whereKey($item['software_id'])->exists(),
                        403
                    );
                } elseif (!empty($item['category_id'])) {
                    $category = AssetCategory::where('organization_id', $this->orgId())->whereKey($item['category_id'])->first();

                    if ($category && str($category->name)->lower()->trim()->is(['software', 'softwares', 'software license', 'software licenses'])) {
                        throw ValidationException::withMessages(["items.{$index}.category_id" => 'Software should be purchased using Item Type = Software, not as a physical asset category.']);
                    }
                }

                $poItem = PurchaseOrderItem::create([
                    'purchase_order_id' => $po->id,
                    'item_type'         => $item['item_type'],
                    'category_id'       => $item['item_type'] === 'asset' ? ($item['category_id'] ?? null) : null,
                    'asset_brand_id'    => $item['item_type'] === 'asset' ? ($item['asset_brand_id'] ?? null) : null,
                    'asset_model_id'    => $item['item_type'] === 'asset' ? ($item['asset_model_id'] ?? null) : null,
                    'software_id'       => $item['item_type'] === 'software' ? $item['software_id'] : null,
                    'license_type'      => $item['item_type'] === 'software' ? ($item['license_type'] ?? 'subscription') : null,
                    'subscription_period' => $item['item_type'] === 'software' ? ($item['subscription_period'] ?? 'annual') : null,
                    'item_name'         => $resolvedItem['item_name'],
                    'brand'             => $resolvedItem['brand'],
                    'model'             => $resolvedItem['model'],
                    'description'       => $item['description'] ?? null,
                    'quantity'          => $item['quantity'],
                    'unit_price'        => $item['unit_price'],
                    'tax_rate'          => $item['tax_rate'] ?? 0,
                    'total_price'       => $item['quantity'] * $item['unit_price'],
                ]);

                $softwareRequestIds = collect($item['software_request_ids'] ?? [])->map(fn ($id) => (int) $id)->unique();
                if ($softwareRequestIds->isNotEmpty()) {
                    abort_unless($item['item_type'] === 'software', 422, 'Software requests can only be linked to software line items.');
                    abort_if($softwareRequestIds->count() > (int) $item['quantity'], 422, 'The ordered quantity cannot be lower than linked employee demand.');

                    $lockedRequests = SoftwareRequest::where('organization_id', $this->orgId())
                        ->whereIn('id', $softwareRequestIds)
                        ->where('software_id', $item['software_id'])
                        ->where('status', 'approved')
                        ->whereNull('purchase_order_item_id')
                        ->lockForUpdate()
                        ->get();

                    if ($lockedRequests->count() !== $softwareRequestIds->count()) {
                        throw ValidationException::withMessages([
                            "items.{$index}.software_request_ids" => 'One or more requests were already processed. Refresh the page and try again.',
                        ]);
                    }

                    SoftwareRequest::whereIn('id', $softwareRequestIds)->update(['purchase_order_item_id' => $poItem->id]);
                }
            }

            return $po;
        });

        return redirect()->route('admin.purchase-orders.show', $po)
            ->with('success', 'Purchase order created successfully.');
    }

    public function show(PurchaseOrder $purchaseOrder)
    {
        abort_if($purchaseOrder->organization_id !== $this->orgId(), 403);
        $purchaseOrder->load([
            'supplier',
            'items.category',
            'items.software',
            'items.softwareRequests.requester.department',
            'items.softwareRequests.assignment',
            'items.softwareRequests.license',
            'items.softwareLicenses',
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

        $purchaseOrder->load(['supplier', 'items.category', 'items.software', 'items.softwareRequests.requester.department', 'items.softwareLicenses']);
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
            'items.*.license_key' => ['nullable', 'string', 'max:500'],
            'items.*.expiry_date' => ['nullable', 'date', 'after_or_equal:received_date'],
            'items.*.renewal_date' => ['nullable', 'date', 'after_or_equal:received_date'],
            'items.*.agreement_number' => ['nullable', 'string', 'max:255'],
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

                $serials = [];
                $tags = [];

                if ($item->item_type === 'asset') {
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
                }

                $receiptLines[] = compact('item', 'quantity', 'rejected', 'serials', 'tags') + [
                    'notes' => $line['notes'] ?? null,
                    'license_key' => $line['license_key'] ?? null,
                    'expiry_date' => $line['expiry_date'] ?? null,
                    'renewal_date' => $line['renewal_date'] ?? null,
                    'agreement_number' => $line['agreement_number'] ?? null,
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

                if ($item->item_type === 'software' && $line['quantity'] > 0) {
                    $license = SoftwareLicense::create([
                        'software_id' => $item->software_id,
                        'organization_id' => $this->orgId(),
                        'vendor_id' => $lockedOrder->vendor_id,
                        'license_type' => $item->license_type ?: 'subscription',
                        'license_key' => $line['license_key'],
                        'purchase_batch' => $lockedOrder->po_number . ' / ' . $receipt->receipt_number,
                        'seats' => $line['quantity'],
                        'purchase_date' => $validated['invoice_date'] ?? $validated['received_date'],
                        'expiry_date' => $line['expiry_date'],
                        'renewal_date' => $line['renewal_date'],
                        'purchase_price' => $item->unit_price * $line['quantity'],
                        'unit_cost' => $item->unit_price,
                        'po_number' => $lockedOrder->po_number,
                        'invoice_number' => $validated['invoice_number'] ?? null,
                        'agreement_number' => $line['agreement_number'],
                        'subscription_period' => $item->subscription_period,
                        'notes' => $line['notes'] ?: 'Created from receipt ' . $receipt->receipt_number,
                        'status' => 'active',
                        'purchase_order_id' => $lockedOrder->id,
                        'purchase_order_item_id' => $item->id,
                        'goods_receipt_id' => $receipt->id,
                    ]);

                    $this->fulfillProcuredSoftwareRequests($item, $license, $line['quantity']);
                } elseif ($item->item_type === 'asset') {
                    for ($index = 0; $index < $line['quantity']; $index++) {
                        Asset::create([
                            'organization_id' => $this->orgId(),
                            'acquisition_source' => 'purchase_order',
                            'purchase_order_id' => $lockedOrder->id,
                            'purchase_order_item_id' => $item->id,
                            'goods_receipt_id' => $receipt->id,
                            'category_id' => $item->category_id,
                            'asset_brand_id' => $item->asset_brand_id,
                            'asset_model_id' => $item->asset_model_id,
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
            ->with('success', "Receipt {$receipt->receipt_number} recorded and linked inventory created.");
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

        DB::transaction(function () use ($purchaseOrder, $request) {
            $purchaseOrder->update(['status' => $request->status]);

            if ($request->status === 'cancelled') {
                $itemIds = $purchaseOrder->items()->pluck('id');
                SoftwareRequest::whereIn('purchase_order_item_id', $itemIds)
                    ->where('status', 'approved')
                    ->update(['purchase_order_item_id' => null]);
            }
        });

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

    private function resolvePurchaseOrderItem(array $item, int $index): array
    {
        if ($item['item_type'] === 'software') {
            $software = Software::where('organization_id', $this->orgId())->find($item['software_id'] ?? null);

            return [
                'item_name' => $software ? $software->name . ' license' : ($item['item_name'] ?? 'Software license'),
                'brand' => $software?->vendor ?? ($item['brand'] ?? null),
                'model' => null,
            ];
        }

        $category = null;
        $brand = null;
        $model = null;

        if (!empty($item['category_id'])) {
            $category = AssetCategory::where('organization_id', $this->orgId())->find($item['category_id']);
        }

        if (!empty($item['asset_brand_id'])) {
            $brand = AssetBrand::where('organization_id', $this->orgId())->find($item['asset_brand_id']);
        }

        if (!empty($item['asset_model_id'])) {
            $model = AssetModel::where('organization_id', $this->orgId())->find($item['asset_model_id']);

            if ($model && $brand && (int) $model->brand_id !== (int) $brand->id) {
                throw ValidationException::withMessages(["items.{$index}.asset_model_id" => 'Selected model does not belong to the selected brand.']);
            }

            if ($model && $category && $model->category_id && (int) $model->category_id !== (int) $category->id) {
                throw ValidationException::withMessages(["items.{$index}.asset_model_id" => 'Selected model does not belong to the selected asset category.']);
            }

            $brand = $brand ?: $model->brand;
        }

        $itemName = trim(implode(' ', array_filter([$brand?->name, $model?->name])));
        if ($itemName === '') {
            $itemName = $item['item_name'] ?? ($category ? $category->name . ' Item' : 'Asset Item');
        }

        return [
            'item_name' => $itemName,
            'brand' => $brand?->name ?? ($item['brand'] ?? null),
            'model' => $model?->name ?? ($item['model'] ?? null),
        ];
    }

    private function fulfillProcuredSoftwareRequests(PurchaseOrderItem $item, SoftwareLicense $license, int $availableSeats): void
    {
        $requests = SoftwareRequest::where('purchase_order_item_id', $item->id)
            ->where('status', 'approved')
            ->orderByRaw('needed_by IS NULL')
            ->orderBy('needed_by')
            ->orderBy('created_at')
            ->lockForUpdate()
            ->get();

        $remainingSeats = $availableSeats;

        foreach ($requests as $softwareRequest) {
            $existingAssignment = SoftwareAssignment::where('user_id', $softwareRequest->requester_id)
                ->where('status', 'active')
                ->whereHas('license', fn ($query) => $query->where('software_id', $item->software_id))
                ->first();

            if ($existingAssignment) {
                $softwareRequest->update([
                    'status' => 'fulfilled',
                    'software_license_id' => $existingAssignment->software_license_id,
                    'software_assignment_id' => $existingAssignment->id,
                    'fulfilled_by' => auth()->id(),
                    'fulfilled_at' => now(),
                ]);
                continue;
            }

            if ($remainingSeats === 0) {
                break;
            }

            $assignment = SoftwareAssignment::create([
                'software_license_id' => $license->id,
                'user_id' => $softwareRequest->requester_id,
                'assigned_by' => auth()->id(),
                'assigned_date' => today(),
                'notes' => 'Automatically allocated from purchase order request #' . $softwareRequest->id,
                'status' => 'active',
            ]);

            $softwareRequest->update([
                'status' => 'fulfilled',
                'software_license_id' => $license->id,
                'software_assignment_id' => $assignment->id,
                'fulfilled_by' => auth()->id(),
                'fulfilled_at' => now(),
            ]);

            $remainingSeats--;
        }
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
