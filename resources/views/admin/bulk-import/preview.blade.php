@extends('layouts.app')
@section('title', 'Import Preview')

@push('styles')
<style>
.step-bar{display:flex;align-items:center;gap:0;background:#fff;border:1px solid #e2e8f0;border-radius:14px;padding:.4rem;margin-bottom:1.5rem;width:fit-content;}
.step-item{display:flex;align-items:center;gap:.55rem;padding:.55rem 1.2rem;border-radius:10px;font-size:.82rem;font-weight:600;color:#94a3b8;}
.step-item.active{background:#3b82f6;color:#fff;box-shadow:0 4px 12px rgba(59,130,246,.3);}
.step-item.done{background:#f0fdf4;color:#16a34a;}
.step-item .step-num{width:22px;height:22px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:.72rem;font-weight:800;background:rgba(255,255,255,.25);flex-shrink:0;}
.step-item:not(.active):not(.done) .step-num{background:#f1f5f9;color:#94a3b8;}
.step-arrow{color:#e2e8f0;font-size:.8rem;padding:0 .2rem;}

.preview-table{font-size:.8rem;width:100%;border-collapse:collapse;}
.preview-table th{background:#f8fafc;font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:#64748b;padding:.65rem .85rem;border-bottom:1px solid #f1f5f9;white-space:nowrap;}
.preview-table td{padding:.6rem .85rem;border-bottom:1px solid #f8fafc;vertical-align:top;}
.preview-table tr.valid-row:hover td{background:#f0fdf4;}
.preview-table tr.invalid-row td{background:#fff5f5;}
.preview-table tr.invalid-row:hover td{background:#fef2f2;}

.row-num{font-size:.7rem;font-weight:700;color:#94a3b8;background:#f8fafc;border-radius:5px;padding:.1rem .4rem;font-family:monospace;}
.error-badge{display:inline-flex;align-items:center;gap:.3rem;background:#fef2f2;border:1px solid #fecaca;color:#b91c1c;border-radius:6px;padding:.2rem .55rem;font-size:.7rem;font-weight:600;margin:.15rem .15rem .15rem 0;}

.summary-card{border-radius:14px;padding:1.1rem 1.4rem;display:flex;align-items:center;gap:1rem;}
.summary-num{font-size:2rem;font-weight:800;line-height:1;}
</style>
@endpush

@section('content')

<div class="page-header d-flex justify-content-between align-items-start">
    <div>
        <h4><i class="bi bi-search me-2 text-primary"></i>Import Preview</h4>
        <p>Review the parsed rows before importing. Only valid rows will be imported.</p>
    </div>
    <a href="{{ route('admin.bulk-import.index', $category ? ['category_id' => $category->id] : []) }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i>Upload Different File
    </a>
</div>

{{-- Step bar --}}
<div class="step-bar">
    <div class="step-item done"><div class="step-num"><i class="bi bi-check-lg" style="font-size:.7rem"></i></div> Upload CSV</div>
    <div class="step-arrow"><i class="bi bi-chevron-right"></i></div>
    <div class="step-item active"><div class="step-num">2</div> Preview & Validate</div>
    <div class="step-arrow"><i class="bi bi-chevron-right"></i></div>
    <div class="step-item"><div class="step-num">3</div> Import</div>
</div>

{{-- Summary cards --}}
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="summary-card" style="background:#f0fdf4;border:1px solid #bbf7d0">
            <div class="summary-num" style="color:#16a34a">{{ $validCount }}</div>
            <div>
                <div style="font-weight:700;color:#15803d;font-size:.9rem">Ready to Import</div>
                <div style="font-size:.75rem;color:#4ade80">These rows passed all validations</div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="summary-card" style="background:{{ $invalidCount > 0 ? '#fef2f2;border:1px solid #fecaca' : '#f8fafc;border:1px solid #e2e8f0' }}">
            <div class="summary-num" style="color:{{ $invalidCount > 0 ? '#dc2626' : '#94a3b8' }}">{{ $invalidCount }}</div>
            <div>
                <div style="font-weight:700;color:{{ $invalidCount > 0 ? '#b91c1c' : '#64748b' }};font-size:.9rem">
                    {{ $invalidCount > 0 ? 'Have Errors' : 'No Errors' }}
                </div>
                <div style="font-size:.75rem;color:{{ $invalidCount > 0 ? '#f87171' : '#94a3b8' }}">
                    {{ $invalidCount > 0 ? 'Fix and re-upload to include these' : 'All rows are valid' }}
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="summary-card" style="background:#f8fafc;border:1px solid #e2e8f0">
            <div class="summary-num" style="color:#334155">{{ count($parsed) }}</div>
            <div>
                <div style="font-weight:700;color:#334155;font-size:.9rem">Total Rows</div>
                <div style="font-size:.75rem;color:#94a3b8">Found in your CSV file</div>
            </div>
        </div>
    </div>
</div>

{{-- Category + specs info --}}
@if($category)
<div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:14px;padding:.9rem 1.2rem;margin-bottom:1rem;display:flex;align-items:flex-start;gap:.85rem">
    <i class="bi bi-tag-fill" style="color:#16a34a;font-size:1.1rem;flex-shrink:0;margin-top:2px"></i>
    <div>
        <div style="font-size:.82rem;font-weight:700;color:#15803d;margin-bottom:.3rem">
            All rows will be assigned to category: <strong>{{ $category->name }}</strong>
        </div>
        @if(!empty($specFields))
        <div style="font-size:.72rem;color:#4ade80;margin-bottom:.4rem">Spec fields imported:</div>
        <div style="display:flex;flex-wrap:wrap;gap:.3rem">
            @foreach($specFields as $label => $_)
            <span style="background:#dcfce7;color:#166534;border:1px solid #bbf7d0;border-radius:5px;padding:.15rem .5rem;font-size:.7rem;font-weight:600">{{ $label }}</span>
            @endforeach
        </div>
        @else
        <div style="font-size:.72rem;color:#4ade80">No spec fields defined for this category.</div>
        @endif
    </div>
</div>
@endif

@if($validCount === 0)
<div class="alert alert-danger rounded-3 mb-4">
    <i class="bi bi-exclamation-triangle-fill me-2"></i>
    <strong>No valid rows found.</strong> Please fix the errors below and re-upload your CSV file.
</div>
@endif

{{-- Confirm form --}}
@if($validCount > 0)
<form action="{{ route('admin.bulk-import.confirm') }}" method="POST" id="confirmForm">
    @csrf
    <input type="hidden" name="temp_path" value="{{ $tempPath }}">
    <input type="hidden" name="category_id" value="{{ $category?->id }}">

    <div class="d-flex align-items-center gap-2 mb-3">
        <button type="submit" class="btn btn-primary" id="importBtn" style="border-radius:10px;padding:.65rem 1.5rem">
            <i class="bi bi-cloud-upload-fill me-2"></i>
            Import {{ $validCount }} Valid Asset{{ $validCount !== 1 ? 's' : '' }}
        </button>
        <a href="{{ route('admin.bulk-import.index', $category ? ['category_id' => $category->id] : []) }}" class="btn btn-outline-secondary" style="border-radius:10px">
            <i class="bi bi-x-lg me-1"></i>Cancel
        </a>
        @if($invalidCount > 0)
        <span style="font-size:.78rem;color:#f59e0b;font-weight:600">
            <i class="bi bi-info-circle me-1"></i>{{ $invalidCount }} row{{ $invalidCount !== 1 ? 's' : '' }} with errors will be skipped
        </span>
        @endif
    </div>
</form>
@endif

{{-- Preview table --}}
<div style="background:#fff;border:1px solid #e2e8f0;border-radius:16px;overflow:hidden">
    <div style="padding:1rem 1.4rem;border-bottom:1px solid #f8fafc;display:flex;align-items:center;gap:.75rem">
        <span style="font-size:.85rem;font-weight:700;color:#0f172a">All Rows</span>
        <span style="font-size:.72rem;color:#94a3b8">
            <span style="color:#16a34a;font-weight:700">●</span> Valid row
            &nbsp;
            <span style="color:#ef4444;font-weight:700">●</span> Has errors (will be skipped)
        </span>

        {{-- Filter toggle --}}
        <div class="ms-auto d-flex gap-1">
            <button type="button" class="btn btn-sm btn-outline-success active" id="showValid" onclick="filterRows('valid')"
                    style="border-radius:8px;font-size:.72rem">✅ Valid ({{ $validCount }})</button>
            <button type="button" class="btn btn-sm btn-outline-danger" id="showInvalid" onclick="filterRows('invalid')"
                    style="border-radius:8px;font-size:.72rem">❌ Errors ({{ $invalidCount }})</button>
            <button type="button" class="btn btn-sm btn-outline-secondary active" id="showAll" onclick="filterRows('all')"
                    style="border-radius:8px;font-size:.72rem">All</button>
        </div>
    </div>

    <div class="table-responsive">
        <table class="preview-table">
            <thead>
                <tr>
                    <th>Row</th>
                    <th>Status</th>
                    <th>Name</th>
                    <th>Asset Tag</th>
                    <th>Brand / Model</th>
                    <th>Category</th>
                    <th>Status / Cond.</th>
                    <th>Price</th>
                    @if(!empty($specFields))<th>Specs</th>@endif
                    <th>Errors</th>
                </tr>
            </thead>
            <tbody>
            @foreach($parsed as $item)
            <tr class="{{ $item['valid'] ? 'valid-row' : 'invalid-row' }}" data-valid="{{ $item['valid'] ? '1' : '0' }}">
                <td><span class="row-num">{{ $item['row'] }}</span></td>
                <td>
                    @if($item['valid'])
                        <span style="color:#16a34a;font-size:1rem">✅</span>
                    @else
                        <span style="color:#ef4444;font-size:1rem">❌</span>
                    @endif
                </td>
                <td>
                    <div style="font-weight:600;color:#1e293b">{{ $item['data']['name'] ?? '—' }}</div>
                    @if(!empty($item['data']['serial_number']))
                    <div style="font-size:.68rem;color:#94a3b8">S/N: {{ $item['data']['serial_number'] }}</div>
                    @endif
                </td>
                <td>
                    <code style="background:#f1f5f9;color:#3b82f6;padding:.1rem .4rem;border-radius:4px;font-size:.72rem">
                        {{ $item['data']['asset_tag'] ?? '—' }}
                    </code>
                </td>
                <td>
                    <div>{{ $item['data']['brand'] ?? '' }} {{ $item['data']['model'] ?? '' }}</div>
                </td>
                <td>
                    @if(!empty($item['data']['_category_name']))
                        <span style="background:#eff6ff;color:#2563eb;border-radius:5px;padding:.1rem .45rem;font-size:.72rem;font-weight:600">
                            {{ $item['data']['_category_name'] }}
                        </span>
                    @elseif(!empty($item['raw']['category']))
                        <span style="background:#fef2f2;color:#dc2626;border-radius:5px;padding:.1rem .45rem;font-size:.72rem">
                            {{ $item['raw']['category'] }} (not found)
                        </span>
                    @else
                        <span style="color:#cbd5e1">—</span>
                    @endif
                </td>
                <td>
                    @php
                        $statusColors = ['available'=>'#16a34a','assigned'=>'#2563eb','maintenance'=>'#d97706','retired'=>'#64748b','disposed'=>'#374151','lost'=>'#dc2626'];
                        $sc = $statusColors[$item['data']['status'] ?? 'available'] ?? '#64748b';
                    @endphp
                    <span style="color:{{ $sc }};font-weight:600;font-size:.75rem">{{ ucfirst($item['data']['status'] ?? 'available') }}</span>
                    <br>
                    <span style="color:#94a3b8;font-size:.72rem">{{ ucfirst($item['data']['condition'] ?? 'good') }}</span>
                </td>
                <td>
                    @if(!empty($item['data']['purchase_price']))
                        <span style="font-weight:600;color:#334155">&#8377;{{ number_format($item['data']['purchase_price'], 0) }}</span>
                    @else
                        <span style="color:#cbd5e1">—</span>
                    @endif
                </td>
                @if(!empty($specFields))
                <td>
                    @if(!empty($item['data']['_spec_count']))
                        <span style="background:#eef2ff;color:#4338ca;border-radius:6px;padding:.15rem .5rem;font-size:.72rem;font-weight:700">
                            <i class="bi bi-cpu me-1"></i>{{ $item['data']['_spec_count'] }} spec{{ $item['data']['_spec_count'] != 1 ? 's' : '' }}
                        </span>
                    @else
                        <span style="color:#cbd5e1;font-size:.72rem">—</span>
                    @endif
                </td>
                @endif
                <td>
                    @if($item['valid'])
                        <span style="color:#16a34a;font-size:.75rem;font-weight:600">
                            <i class="bi bi-check-circle-fill me-1"></i>Valid
                        </span>
                    @else
                        @foreach($item['errors'] as $err)
                        <div class="error-badge"><i class="bi bi-exclamation-circle-fill"></i> {{ $err }}</div>
                        @endforeach
                    @endif
                </td>
            </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>

@if($validCount > 0)
<div class="mt-3 d-flex gap-2">
    <button type="submit" form="confirmForm" class="btn btn-primary" style="border-radius:10px;padding:.65rem 1.5rem">
        <i class="bi bi-cloud-upload-fill me-2"></i>Import {{ $validCount }} Valid Asset{{ $validCount !== 1 ? 's' : '' }}
    </button>
    <a href="{{ route('admin.bulk-import.index', $category ? ['category_id' => $category->id] : []) }}" class="btn btn-outline-secondary" style="border-radius:10px">
        <i class="bi bi-x-lg me-1"></i>Cancel
    </a>
</div>
@endif

@endsection

@push('scripts')
<script>
function filterRows(type) {
    document.querySelectorAll('tbody tr').forEach(tr => {
        const isValid = tr.dataset.valid === '1';
        if (type === 'all') tr.style.display = '';
        else if (type === 'valid') tr.style.display = isValid ? '' : 'none';
        else if (type === 'invalid') tr.style.display = !isValid ? '' : 'none';
    });
    ['showAll','showValid','showInvalid'].forEach(id => document.getElementById(id)?.classList.remove('active'));
    const map = {all:'showAll',valid:'showValid',invalid:'showInvalid'};
    document.getElementById(map[type])?.classList.add('active');
}

document.getElementById('confirmForm')?.addEventListener('submit', function() {
    const btn = document.getElementById('importBtn');
    if (btn) { btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Importing…'; btn.disabled = true; }
});
</script>
@endpush
