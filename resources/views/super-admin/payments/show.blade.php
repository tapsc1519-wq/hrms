@extends('layouts.app')
@section('title', 'Payment Receipt')

@push('styles')
<style>
    .receipt-page { font-size: .84rem; }
    .receipt-card {
        background: #fff;
        border: 0;
        border-radius: 16px;
        box-shadow: 0 8px 28px rgba(15, 23, 42, .08);
        overflow: hidden;
    }
    .receipt-header {
        background: linear-gradient(135deg, #0f172a, #1d4ed8);
        color: #fff;
        padding: 1.35rem;
    }
    .receipt-title { font-size: 1.15rem; font-weight: 800; margin: 0; }
    .receipt-muted { color: #64748b; font-size: .78rem; line-height: 1.45; }
    .receipt-label {
        color: #64748b;
        font-size: .7rem;
        font-weight: 800;
        letter-spacing: .05em;
        text-transform: uppercase;
    }
    .receipt-value {
        color: #0f172a;
        font-size: .88rem;
        font-weight: 700;
        margin-top: .12rem;
    }
    .receipt-amount {
        color: #0f172a;
        font-size: 1.65rem;
        font-weight: 800;
        line-height: 1.1;
    }
    @media print {
        #sidebar, #topbar, .px-4.pt-3, .receipt-actions { display: none !important; }
        #main-content { margin-left: 0 !important; }
        .content-area { padding: 0 !important; }
        body { background: #fff !important; }
        .receipt-card { box-shadow: none !important; border: 1px solid #e2e8f0; }
    }
</style>
@endpush

@section('content')
<div class="receipt-page">
    <div class="page-header d-flex justify-content-between align-items-start receipt-actions">
        <div>
            <a href="{{ route('super-admin.payments.index') }}" class="back-link"><i class="bi bi-arrow-left"></i> Payments</a>
            <h4>Payment Receipt</h4>
            <p>Printable receipt for organization subscription payment.</p>
        </div>
        <button onclick="window.print()" class="btn btn-primary btn-sm">
            <i class="bi bi-printer me-1"></i>Print / Save PDF
        </button>
    </div>

    <div class="row justify-content-center">
        <div class="col-xl-8">
            <div class="receipt-card">
                <div class="receipt-header d-flex justify-content-between gap-3">
                    <div>
                        <p class="receipt-title">Subscription Payment Receipt</p>
                        <div style="font-size:.78rem;opacity:.82">Receipt #PAY-{{ str_pad($payment->id, 6, '0', STR_PAD_LEFT) }}</div>
                    </div>
                    <div class="text-end">
                        <div style="font-size:.72rem;opacity:.78">Payment Date</div>
                        <div style="font-weight:800">{{ $payment->payment_date->format('d-m-Y') }}</div>
                    </div>
                </div>

                <div class="p-4">
                    <div class="row g-4 mb-4">
                        <div class="col-md-7">
                            <div class="receipt-label">Received From</div>
                            <div class="receipt-value">{{ $payment->organization?->name ?? '-' }}</div>
                            <div class="receipt-muted mt-1">
                                {{ $payment->organization?->email ?? '-' }}<br>
                                {{ $payment->organization?->city ?? '' }} {{ $payment->organization?->country ?? '' }}
                            </div>
                        </div>
                        <div class="col-md-5 text-md-end">
                            <div class="receipt-label">Amount Received</div>
                            <div class="receipt-amount">&#8377;{{ number_format((float) $payment->amount, 2) }}</div>
                            <span class="badge bg-success mt-2">Paid</span>
                        </div>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <div class="border rounded-3 p-3 h-100">
                                <div class="receipt-label">Subscription Period</div>
                                <div class="receipt-value">{{ $payment->period_start->format('d-m-Y') }} to {{ $payment->period_end->format('d-m-Y') }}</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="border rounded-3 p-3 h-100">
                                <div class="receipt-label">Payment Method</div>
                                <div class="receipt-value">{{ $methods[$payment->payment_method] ?? ucwords(str_replace('_', ' ', $payment->payment_method)) }}</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="border rounded-3 p-3 h-100">
                                <div class="receipt-label">Reference Number</div>
                                <div class="receipt-value">{{ $payment->reference_no ?? '-' }}</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="border rounded-3 p-3 h-100">
                                <div class="receipt-label">Recorded By</div>
                                <div class="receipt-value">{{ $payment->recorder?->name ?? '-' }}</div>
                            </div>
                        </div>
                    </div>

                    @if($payment->notes)
                        <div class="border rounded-3 p-3 mb-4">
                            <div class="receipt-label">Notes</div>
                            <div class="receipt-muted mt-1">{{ $payment->notes }}</div>
                        </div>
                    @endif

                    <div class="d-flex justify-content-between align-items-end border-top pt-3">
                        <div class="receipt-muted">
                            This receipt confirms payment recorded in the platform billing ledger.
                        </div>
                        <div class="text-end">
                            <div class="receipt-label">Generated On</div>
                            <div class="receipt-value">{{ now()->format('d-m-Y') }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
