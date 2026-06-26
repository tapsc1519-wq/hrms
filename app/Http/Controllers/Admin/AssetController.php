<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\AssetBrand;
use App\Models\AssetCategory;
use App\Models\AssetModel;
use App\Models\DepreciationRecord;
use App\Models\Facility;
use App\Models\Location;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AssetController extends Controller
{

    public function index(Request $request)
    {
        $query = Asset::where('organization_id', $this->orgId())
            ->with(['category', 'supplier', 'location', 'activeAssignment.user', 'activeDisposal']);

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('asset_tag', 'like', '%' . $request->search . '%')
                  ->orWhere('serial_number', 'like', '%' . $request->search . '%')
                  ->orWhere('brand', 'like', '%' . $request->search . '%');
            });
        }

        foreach (['status', 'condition', 'category_id', 'vendor_id', 'location_id'] as $filter) {
            if ($request->filled($filter)) {
                $query->where($filter, $request->$filter);
            }
        }

        $assets = $query->latest()->paginate(20)->withQueryString();

        $categories = AssetCategory::where('organization_id', $this->orgId())->orderBy('name')->get();
        $suppliers  = Supplier::where('organization_id', $this->orgId())->where('status', 'active')->orderBy('name')->get();
        $facilities = Facility::where('organization_id', $this->orgId())->where('status', 'active')
                        ->with(['activeLocations'])->orderBy('name')->get();

        return view('admin.assets.index', compact('assets', 'categories', 'suppliers', 'facilities'));
    }

    private function facilitiesForForm(): \Illuminate\Database\Eloquent\Collection
    {
        return Facility::where('organization_id', $this->orgId())
            ->where('status', 'active')
            ->with(['activeLocations'])
            ->orderBy('name')
            ->get();
    }

    public function create()
    {
        $categories = AssetCategory::where('organization_id', $this->orgId())->orderBy('name')->get();
        $suppliers  = Supplier::where('organization_id', $this->orgId())->where('status', 'active')->orderBy('name')->get();
        $brands     = AssetBrand::where('organization_id', $this->orgId())->where('is_active', true)->orderBy('name')->get();
        $models     = AssetModel::where('organization_id', $this->orgId())->where('is_active', true)->with('brand')->orderBy('name')->get();
        $facilities = $this->facilitiesForForm();

        return view('admin.assets.create', compact('categories', 'suppliers', 'brands', 'models', 'facilities'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'acquisition_source'     => 'required|in:opening_balance,donation,transfer,lease,other',
            'category_id'          => 'nullable|exists:asset_categories,id',
            'vendor_id'            => 'nullable|exists:suppliers,id',
            'location_id'          => 'nullable|exists:locations,id',
            'name'                 => 'required|string|max:255',
            'asset_tag'            => 'nullable|string|max:100|unique:assets,asset_tag',
            'serial_number'        => 'nullable|string|max:255',
            'model'                => 'nullable|string|max:255',
            'brand'                => 'nullable|string|max:255',
            'specifications'       => 'nullable|string',
            'description'          => 'nullable|string',
            'purchase_date'        => 'nullable|date',
            'purchase_price'       => 'nullable|numeric|min:0',
            'warranty_expiry_date' => 'nullable|date',
            'warranty_terms'       => 'nullable|string|max:255',
            'status'               => 'required|in:available,assigned,maintenance,repair,retired,disposed,lost',
            'condition'            => 'required|in:excellent,good,fair,poor',
            'notes'                => 'nullable|string',
            'image'                => 'nullable|image|max:2048',
            'asset_brand_id'       => 'nullable|exists:asset_brands,id',
            'asset_model_id'       => 'nullable|exists:asset_models,id',
            'specs'                => 'nullable|array',
            // Depreciation
            'dep_method'           => 'nullable|in:straight_line,declining_balance,sum_of_years',
            'useful_life_years'    => 'nullable|integer|min:1',
            'salvage_value'        => 'nullable|numeric|min:0',
        ]);

        $validated['organization_id'] = $this->orgId();

        if (!$validated['asset_tag']) {
            $validated['asset_tag'] = 'ASSET-' . strtoupper(Str::random(8));
        }

        if ($request->hasFile('image')) {
            $validated['image'] = $request->file('image')->store('assets', 'public');
        }

        // Collect dynamic spec_* fields into JSON
        $validated['specs'] = $this->collectSpecs($request);

        $asset = Asset::create($validated);

        if ($request->filled('dep_method')) {
            DepreciationRecord::create([
                'asset_id'          => $asset->id,
                'method'            => $validated['dep_method'],
                'useful_life_years' => $validated['useful_life_years'] ?? 3,
                'salvage_value'     => $validated['salvage_value'] ?? 0,
                'start_date'        => $validated['purchase_date'] ?? now(),
            ]);
        }

        return redirect()->route('admin.assets.show', $asset)
            ->with('success', 'Asset registered successfully.');
    }

    public function show(Asset $asset)
    {
        $this->authorizeAsset($asset);
        $asset->load([
            'category',
            'supplier',
            'location',
            'purchaseOrder',
            'purchaseOrderItem',
            'goodsReceipt',
            'assignments.user',
            'maintenanceRecords',
            'depreciationRecord',
            'disposals.requestedBy',
            'activeDisposal',
        ]);
        return view('admin.assets.show', compact('asset'));
    }

    public function edit(Asset $asset)
    {
        $this->authorizeAsset($asset);
        $categories = AssetCategory::where('organization_id', $this->orgId())->orderBy('name')->get();
        $suppliers  = Supplier::where('organization_id', $this->orgId())->where('status', 'active')->orderBy('name')->get();
        $brands     = AssetBrand::where('organization_id', $this->orgId())->where('is_active', true)->orderBy('name')->get();
        $models     = AssetModel::where('organization_id', $this->orgId())->where('is_active', true)->with('brand')->orderBy('name')->get();
        $facilities = $this->facilitiesForForm();
        return view('admin.assets.edit', compact('asset', 'categories', 'suppliers', 'brands', 'models', 'facilities'));
    }

    public function update(Request $request, Asset $asset)
    {
        $this->authorizeAsset($asset);

        $validated = $request->validate([
            'category_id'          => 'nullable|exists:asset_categories,id',
            'vendor_id'            => 'nullable|exists:suppliers,id',
            'location_id'          => 'nullable|exists:locations,id',
            'name'                 => 'required|string|max:255',
            'asset_tag'            => 'nullable|string|max:100|unique:assets,asset_tag,' . $asset->id,
            'serial_number'        => 'nullable|string|max:255',
            'model'                => 'nullable|string|max:255',
            'brand'                => 'nullable|string|max:255',
            'specifications'       => 'nullable|string',
            'description'          => 'nullable|string',
            'purchase_date'        => 'nullable|date',
            'purchase_price'       => 'nullable|numeric|min:0',
            'warranty_expiry_date' => 'nullable|date',
            'warranty_terms'       => 'nullable|string|max:255',
            'status'               => 'required|in:available,assigned,maintenance,repair,retired,disposed,lost',
            'condition'            => 'required|in:excellent,good,fair,poor',
            'notes'                => 'nullable|string',
            'image'                => 'nullable|image|max:2048',
            'asset_brand_id'       => 'nullable|exists:asset_brands,id',
            'asset_model_id'       => 'nullable|exists:asset_models,id',
            'specs'                => 'nullable|array',
        ]);

        if ($request->hasFile('image')) {
            $validated['image'] = $request->file('image')->store('assets', 'public');
        }

        $validated['specs'] = $this->collectSpecs($request);

        $asset->update($validated);

        return redirect()->route('admin.assets.show', $asset)
            ->with('success', 'Asset updated successfully.');
    }

    public function destroy(Asset $asset)
    {
        $this->authorizeAsset($asset);
        $asset->delete();
        return redirect()->route('admin.assets.index')
            ->with('success', 'Asset deleted successfully.');
    }

    private function authorizeAsset(Asset $asset): void
    {
        abort_if($asset->organization_id !== $this->orgId(), 403);
    }

    /** Collect all spec_* fields from request into a clean JSON-ready array */
    private function collectSpecs(Request $request): array
    {
        $specs = [];
        foreach ($request->all() as $key => $value) {
            if (str_starts_with($key, 'spec_') && $value !== null && $value !== '') {
                $specs[$key] = $value;
            }
        }
        return $specs;
    }
}
