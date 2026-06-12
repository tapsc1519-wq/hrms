@extends('layouts.app')

@section('title', 'Payslip')

@php
    $earnings = $item->components->where('type', 'earning');
    $deductions = $item->components->where('type', 'deduction');
@endphp

@section('content')
<div class="page-header d-flex flex-wrap justify-content-between align-items-start gap-3">
    <div>
        <a href="{{ route('admin.payroll.runs.show', $run) }}" class="back-link"><i class="bi bi-arrow-left"></i> Payroll Run</a>
        <h4>Payslip - {{ $item->employee?->user?->name }}</h4>
        <p>{{ \Carbon\Carbon::createFromFormat('Y-m', $run->month)->format('F Y') }} salary statement.</p>
    </div>
    <button onclick="window.print()" class="btn btn-primary"><i class="bi bi-printer me-1"></i> Print</button>
</div>

@include('admin.payroll._payslip_body', ['item' => $item, 'run' => $run, 'earnings' => $earnings, 'deductions' => $deductions])
@endsection
