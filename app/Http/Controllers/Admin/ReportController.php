<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\AssetAssignment;
use App\Models\Invoice;
use App\Models\MaintenanceRecord;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class ReportController extends Controller
{


    public function assets(Request $request)
    {
        $query = Asset::where('organization_id', $this->orgId())
            ->with(['category', 'supplier', 'location', 'activeAssignment.user']);

        if ($request->filled('status')) $query->where('status', $request->status);
        if ($request->filled('category_id')) $query->where('category_id', $request->category_id);

        $assets = $query->get();

        if ($request->format === 'pdf') {
            $pdf = Pdf::loadView('admin.reports.assets-pdf', compact('assets'))
                ->setPaper('a4', 'landscape');
            return $pdf->download('assets-report-' . date('Y-m-d') . '.pdf');
        }

        if ($request->format === 'excel') {
            return Excel::download(new \App\Exports\AssetsExport($assets), 'assets-report-' . date('Y-m-d') . '.xlsx');
        }

        $summary = [
            'total'         => $assets->count(),
            'available'     => $assets->where('status', 'available')->count(),
            'assigned'      => $assets->where('status', 'assigned')->count(),
            'maintenance'   => $assets->whereIn('status', ['maintenance', 'repair'])->count(),
            'total_value'   => $assets->sum('purchase_price'),
        ];

        return view('admin.reports.assets', compact('assets', 'summary', 'request'));
    }

    public function vendors(Request $request)
    {
        $suppliers = Supplier::where('organization_id', $this->orgId())
            ->withCount(['assets', 'purchaseOrders'])
            ->withSum('purchaseOrders', 'total_amount')
            ->get();

        if ($request->format === 'pdf') {
            $pdf = Pdf::loadView('admin.reports.vendors-pdf', compact('suppliers'))
                ->setPaper('a4', 'landscape');
            return $pdf->download('vendors-report-' . date('Y-m-d') . '.pdf');
        }

        return view('admin.reports.vendors', compact('suppliers'));
    }

    public function maintenance(Request $request)
    {
        $records = MaintenanceRecord::whereHas('asset', fn($q) => $q->where('organization_id', $this->orgId()))
            ->with(['asset', 'supplier'])
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->get();

        $summary = [
            'total'       => $records->count(),
            'total_cost'  => $records->sum('total_cost'),
            'completed'   => $records->where('status', 'completed')->count(),
            'scheduled'   => $records->where('status', 'scheduled')->count(),
        ];

        if ($request->format === 'pdf') {
            $pdf = Pdf::loadView('admin.reports.maintenance-pdf', compact('records', 'summary'))
                ->setPaper('a4', 'landscape');
            return $pdf->download('maintenance-report-' . date('Y-m-d') . '.pdf');
        }

        return view('admin.reports.maintenance', compact('records', 'summary'));
    }

    public function depreciation()
    {
        $assets = Asset::where('organization_id', $this->orgId())
            ->with(['category', 'depreciationRecord'])
            ->whereHas('depreciationRecord')
            ->get();

        return view('admin.reports.depreciation', compact('assets'));
    }
}
