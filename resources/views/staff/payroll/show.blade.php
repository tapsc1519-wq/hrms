@extends('layouts.app')

@section('title', 'Payslip')

@php
    $run = $item->run;
    $earnings = $item->components->where('type', 'earning');
    $deductions = $item->components->where('type', 'deduction');
@endphp

@section('content')
<div class="page-header d-flex flex-wrap justify-content-between align-items-start gap-3">
    <div>
        <a href="{{ route('staff.payslips.index') }}" class="back-link"><i class="bi bi-arrow-left"></i> My Payslips</a>
        <h4>{{ \Carbon\Carbon::createFromFormat('Y-m', $run->month)->format('F Y') }} Payslip</h4>
        <p>Your approved salary statement for this payroll month.</p>
    </div>
    <button onclick="window.print()" class="btn btn-primary"><i class="bi bi-printer me-1"></i> Print</button>
</div>

@include('admin.payroll._payslip_body', ['item' => $item, 'run' => $run, 'earnings' => $earnings, 'deductions' => $deductions])
@endsection
