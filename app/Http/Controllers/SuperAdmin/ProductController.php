<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\OrganizationModule;
use App\Models\Product;
use App\Support\ModuleRegistry;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index()
    {
        $moduleCounts = OrganizationModule::selectRaw('module_key, COUNT(*) as count')
            ->where('is_enabled', true)
            ->groupBy('module_key')
            ->pluck('count', 'module_key');

        $products = Product::orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map(function (Product $product) use ($moduleCounts) {
                $modules = collect(ModuleRegistry::forProduct($product->slug));
                $product->setAttribute('registered_modules_count', $modules->count());
                $product->setAttribute('enabled_modules_count', $modules->reduce(
                    fn (int $total, array $module, string $key) => $total + (int) ($moduleCounts[$key] ?? 0),
                    0
                ));

                return $product;
            });

        return view('super-admin.products.index', compact('products'));
    }

    public function edit(Product $product)
    {
        $modules = collect(ModuleRegistry::forProduct($product->slug));

        return view('super-admin.products.edit', compact('product', 'modules'));
    }

    public function update(Request $request, Product $product)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'short_name' => ['nullable', 'string', 'max:80'],
            'domain' => ['nullable', 'string', 'max:255'],
            'app_path' => ['nullable', 'string', 'max:255'],
            'icon' => ['required', 'string', 'max:80'],
            'color' => ['required', 'string', 'max:50'],
            'description' => ['nullable', 'string', 'max:2000'],
            'status' => ['required', 'in:active,inactive,coming_soon'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:9999'],
        ]);

        $product->update($data);

        return redirect()->route('super-admin.products.index')->with('success', 'Product updated successfully.');
    }
}
