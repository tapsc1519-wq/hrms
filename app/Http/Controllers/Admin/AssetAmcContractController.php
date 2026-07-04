<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\AssetAmcContract;
use App\Models\Supplier;
use Illuminate\Http\Request;

class AssetAmcContractController extends Controller
{
    public function index()
    {
        $contracts = AssetAmcContract::where('organization_id', $this->orgId())
            ->with(['vendor', 'assets'])
            ->latest('end_date')
            ->paginate(20);

        return view('admin.asset-amc-contracts.index', compact('contracts'));
    }

    public function create()
    {
        return view('admin.asset-amc-contracts.form', $this->formData(new AssetAmcContract()));
    }

    public function store(Request $request)
    {
        $data = $this->validatedData($request);
        $assetIds = $this->validAssetIds($request->input('asset_ids', []));

        $contract = AssetAmcContract::create([
            ...$data,
            'organization_id' => $this->orgId(),
            'parts_included' => $request->boolean('parts_included'),
            'onsite_support' => $request->boolean('onsite_support'),
        ]);

        $contract->assets()->sync($assetIds);

        return redirect()->route('admin.asset-amc-contracts.index')->with('success', 'AMC contract created.');
    }

    public function edit(AssetAmcContract $assetAmcContract)
    {
        $this->authorizeContract($assetAmcContract);

        return view('admin.asset-amc-contracts.form', $this->formData($assetAmcContract));
    }

    public function update(Request $request, AssetAmcContract $assetAmcContract)
    {
        $this->authorizeContract($assetAmcContract);

        $data = $this->validatedData($request);
        $assetIds = $this->validAssetIds($request->input('asset_ids', []));

        $assetAmcContract->update([
            ...$data,
            'parts_included' => $request->boolean('parts_included'),
            'onsite_support' => $request->boolean('onsite_support'),
        ]);

        $assetAmcContract->assets()->sync($assetIds);

        return redirect()->route('admin.asset-amc-contracts.index')->with('success', 'AMC contract updated.');
    }

    private function formData(AssetAmcContract $contract): array
    {
        $contract->loadMissing('assets');

        return [
            'contract' => $contract,
            'suppliers' => Supplier::where('organization_id', $this->orgId())->where('status', 'active')->orderBy('name')->get(),
            'assets' => Asset::where('organization_id', $this->orgId())->whereNotIn('status', ['disposed', 'lost'])->orderBy('name')->get(),
        ];
    }

    private function validatedData(Request $request): array
    {
        return $request->validate([
            'vendor_id' => ['nullable', 'exists:suppliers,id'],
            'contract_number' => ['nullable', 'string', 'max:255'],
            'title' => ['required', 'string', 'max:255'],
            'coverage_type' => ['required', 'in:service_only,parts_and_service,onsite,carry_in,warranty_hybrid'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'response_sla_hours' => ['nullable', 'integer', 'min:1'],
            'resolution_sla_hours' => ['nullable', 'integer', 'min:1'],
            'status' => ['required', 'in:active,expired,cancelled'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'asset_ids' => ['array'],
            'asset_ids.*' => ['integer', 'exists:assets,id'],
        ]);
    }

    private function validAssetIds(array $assetIds): array
    {
        return Asset::where('organization_id', $this->orgId())
            ->whereIn('id', $assetIds)
            ->pluck('id')
            ->all();
    }

    private function authorizeContract(AssetAmcContract $contract): void
    {
        abort_if($contract->organization_id !== $this->orgId(), 403);
    }
}
