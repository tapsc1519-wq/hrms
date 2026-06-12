<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AssetBrand;
use App\Models\AssetCategory;
use App\Models\AssetModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CatalogController extends Controller
{
    // ─── INDEX ────────────────────────────────────────────────────────────────

    public function index(Request $request)
    {
        $orgId = $this->orgId();

        $categories = AssetCategory::where('organization_id', $orgId)
            ->withCount('assets')
            ->orderBy('name')->get();

        $brands = AssetBrand::where('organization_id', $orgId)
            ->withCount(['models', 'assets'])
            ->orderBy('name')->get();

        $models = AssetModel::where('organization_id', $orgId)
            ->with(['brand', 'category'])
            ->withCount('assets')
            ->orderBy('name')->get();

        $activeTab = $request->get('tab', 'categories');

        return view('admin.catalog.index', compact('categories', 'brands', 'models', 'activeTab'));
    }

    // ─── AJAX: category spec template ─────────────────────────────────────────

    public function categorySpecs(AssetCategory $category)
    {
        abort_if($category->organization_id !== $this->orgId(), 403);
        return response()->json([
            'fields' => $category->spec_template ?? [],
        ]);
    }

    // ─── AJAX: model default specs ─────────────────────────────────────────────

    public function modelSpecs(AssetModel $assetModel)
    {
        abort_if($assetModel->organization_id !== $this->orgId(), 403);
        return response()->json([
            'specs'       => $assetModel->default_specs ?? [],
            'brand_id'    => $assetModel->brand_id,
            'category_id' => $assetModel->category_id,
        ]);
    }

    // ─── CATEGORIES ───────────────────────────────────────────────────────────

    public function storeCategory(Request $request)
    {
        $data = $request->validate([
            'name'               => 'required|string|max:100',
            'icon'               => 'nullable|string|max:60',
            'description'        => 'nullable|string|max:255',
            'depreciation_years' => 'nullable|integer|min:1|max:50',
            'spec_fields'        => 'nullable|string',   // comma-separated field labels
        ]);
        $data['organization_id']  = $this->orgId();
        $data['slug']             = Str::slug($data['name']);
        $data['icon']             = $data['icon'] ?? 'bi-box';
        $data['depreciation_years'] = $data['depreciation_years'] ?? 3;
        $data['spec_template']    = $this->parseSpecFields($request->input('spec_fields', ''));
        unset($data['spec_fields']);

        AssetCategory::create($data);
        return back()->with('success', 'Category created.')->with('tab', 'categories');
    }

    public function updateCategory(Request $request, AssetCategory $category)
    {
        abort_if($category->organization_id !== $this->orgId(), 403);
        $data = $request->validate([
            'name'               => 'required|string|max:100',
            'icon'               => 'nullable|string|max:60',
            'description'        => 'nullable|string|max:255',
            'depreciation_years' => 'nullable|integer|min:1|max:50',
            'spec_fields'        => 'nullable|string',
        ]);
        $data['slug']          = Str::slug($data['name']);
        $data['spec_template'] = $this->parseSpecFields($request->input('spec_fields', ''));
        unset($data['spec_fields']);
        $category->update($data);
        return back()->with('success', 'Category updated.')->with('tab', 'categories');
    }

    public function destroyCategory(AssetCategory $category)
    {
        abort_if($category->organization_id !== $this->orgId(), 403);
        $category->delete();
        return back()->with('success', 'Category deleted.')->with('tab', 'categories');
    }

    // ─── BRANDS ───────────────────────────────────────────────────────────────

    public function storeBrand(Request $request)
    {
        $data = $request->validate([
            'name'      => 'required|string|max:100',
            'website'   => 'nullable|url|max:255',
            'logo'      => 'nullable|image|mimes:png,jpg,jpeg,svg,webp|max:1024',
            'is_active' => 'nullable|boolean',
        ]);
        $data['organization_id'] = $this->orgId();
        $data['is_active']       = $request->boolean('is_active', true);

        if ($request->hasFile('logo')) {
            $data['logo'] = $request->file('logo')->store('brands', 'public');
        }

        AssetBrand::create($data);
        return back()->with('success', 'Brand created.')->with('tab', 'brands');
    }

    public function updateBrand(Request $request, AssetBrand $brand)
    {
        abort_if($brand->organization_id !== $this->orgId(), 403);
        $data = $request->validate([
            'name'      => 'required|string|max:100',
            'website'   => 'nullable|url|max:255',
            'logo'      => 'nullable|image|mimes:png,jpg,jpeg,svg,webp|max:1024',
            'is_active' => 'nullable|boolean',
        ]);
        $data['is_active'] = $request->boolean('is_active', true);

        if ($request->hasFile('logo')) {
            if ($brand->logo) Storage::disk('public')->delete($brand->logo);
            $data['logo'] = $request->file('logo')->store('brands', 'public');
        }

        $brand->update($data);
        return back()->with('success', 'Brand updated.')->with('tab', 'brands');
    }

    public function destroyBrand(AssetBrand $brand)
    {
        abort_if($brand->organization_id !== $this->orgId(), 403);
        if ($brand->logo) Storage::disk('public')->delete($brand->logo);
        $brand->delete();
        return back()->with('success', 'Brand deleted.')->with('tab', 'brands');
    }

    // ─── MODELS ───────────────────────────────────────────────────────────────

    public function storeModel(Request $request)
    {
        $data = $request->validate([
            'name'        => 'required|string|max:150',
            'brand_id'    => 'nullable|exists:asset_brands,id',
            'category_id' => 'nullable|exists:asset_categories,id',
            'is_active'   => 'nullable|boolean',
        ]);
        $data['organization_id'] = $this->orgId();
        $data['is_active']       = $request->boolean('is_active', true);
        $data['default_specs']   = $this->extractDynamicSpecs($request);

        AssetModel::create($data);
        return back()->with('success', 'Model created.')->with('tab', 'models');
    }

    public function updateModel(Request $request, AssetModel $assetModel)
    {
        abort_if($assetModel->organization_id !== $this->orgId(), 403);
        $data = $request->validate([
            'name'        => 'required|string|max:150',
            'brand_id'    => 'nullable|exists:asset_brands,id',
            'category_id' => 'nullable|exists:asset_categories,id',
            'is_active'   => 'nullable|boolean',
        ]);
        $data['is_active']     = $request->boolean('is_active', true);
        $data['default_specs'] = $this->extractDynamicSpecs($request);

        $assetModel->update($data);
        return back()->with('success', 'Model updated.')->with('tab', 'models');
    }

    public function destroyModel(AssetModel $assetModel)
    {
        abort_if($assetModel->organization_id !== $this->orgId(), 403);
        $assetModel->delete();
        return back()->with('success', 'Model deleted.')->with('tab', 'models');
    }

    // ─── HELPERS ──────────────────────────────────────────────────────────────

    /** Parse comma/newline separated field labels into clean array */
    private function parseSpecFields(string $raw): array
    {
        $fields = preg_split('/[\n,]+/', $raw);
        return array_values(array_filter(array_map('trim', $fields)));
    }

    /** Extract dynamic spec_* fields from request into [label => value] array */
    private function extractDynamicSpecs(Request $request): array
    {
        $specs = [];
        foreach ($request->all() as $key => $value) {
            if (str_starts_with($key, 'spec_') && !empty($value)) {
                $specs[$key] = $value;
            }
        }
        return $specs;
    }
}
