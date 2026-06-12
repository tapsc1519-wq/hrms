<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\OrganizationPayment;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function index(Request $request)
    {
        $query = $this->filteredPayments($request)->latest('payment_date');

        $summaryQuery = clone $query;
        $totalCollected = (float) $summaryQuery->sum('amount');
        $paymentCount = (clone $query)->count();
        $latestPayment = (clone $query)->max('payment_date');
        $payments = $query->paginate(20)->withQueryString();

        $organizations = Organization::orderBy('name')->get(['id', 'name']);
        $methods = $this->paymentMethods();

        return view('super-admin.payments.index', compact(
            'payments',
            'organizations',
            'methods',
            'totalCollected',
            'paymentCount',
            'latestPayment'
        ));
    }

    public function export(Request $request)
    {
        $methods = $this->paymentMethods();
        $filename = 'payment-ledger-' . now()->format('Y-m-d-His') . '.csv';

        return response()->streamDownload(function () use ($request, $methods) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, [
                'Receipt No',
                'Payment Date',
                'Organization',
                'Amount',
                'Period Start',
                'Period End',
                'Payment Method',
                'Reference No',
                'Recorded By',
                'Notes',
            ]);

            $this->filteredPayments($request)
                ->latest('payment_date')
                ->chunk(200, function ($payments) use ($handle, $methods) {
                    foreach ($payments as $payment) {
                        fputcsv($handle, [
                            'PAY-' . str_pad((string) $payment->id, 6, '0', STR_PAD_LEFT),
                            optional($payment->payment_date)->format('d-m-Y'),
                            $payment->organization?->name,
                            number_format((float) $payment->amount, 2, '.', ''),
                            optional($payment->period_start)->format('d-m-Y'),
                            optional($payment->period_end)->format('d-m-Y'),
                            $methods[$payment->payment_method] ?? $payment->payment_method,
                            $payment->reference_no,
                            $payment->recorder?->name,
                            $payment->notes,
                        ]);
                    }
                });

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

    public function show(OrganizationPayment $payment)
    {
        $payment->load(['organization', 'recorder']);
        $methods = $this->paymentMethods();

        return view('super-admin.payments.show', compact('payment', 'methods'));
    }

    private function filteredPayments(Request $request)
    {
        $query = OrganizationPayment::with(['organization', 'recorder']);

        if ($request->filled('organization_id')) {
            $query->where('organization_id', $request->organization_id);
        }

        if ($request->filled('payment_method')) {
            $query->where('payment_method', $request->payment_method);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('payment_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('payment_date', '<=', $request->date_to);
        }

        return $query;
    }

    private function paymentMethods(): array
    {
        return [
            'bank_transfer' => 'Bank Transfer',
            'upi' => 'UPI',
            'cheque' => 'Cheque',
            'cash' => 'Cash',
            'card' => 'Card',
            'other' => 'Other',
        ];
    }
}
