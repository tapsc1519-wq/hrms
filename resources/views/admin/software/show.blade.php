@extends('layouts.app')
@section('title', $software->name)

@section('content')
<div class="page-header d-flex align-items-start justify-content-between flex-wrap gap-2">
    <div>
        <a href="{{ route('admin.software.index') }}" class="back-link">
            <i class="bi bi-arrow-left"></i> Software Catalog
        </a>
        <div class="d-flex align-items-center gap-3">
            @if($software->icon)
                <img src="{{ Storage::url($software->icon) }}" alt="{{ $software->name }}"
                     style="width:52px;height:52px;border-radius:13px;object-fit:cover">
            @else
                <div style="width:52px;height:52px;border-radius:13px;background:linear-gradient(135deg,#3b82f6,#6366f1);
                            display:flex;align-items:center;justify-content:center;color:#fff;font-size:1.5rem;flex-shrink:0">
                    <i class="bi {{ $software->category_icon }}"></i>
                </div>
            @endif
            <div>
                <h4 class="mb-0">{{ $software->name }}</h4>
                <p class="mb-0">
                    @if($software->vendor) {{ $software->vendor }} · @endif
                    <span class="badge rounded-pill" style="background:#eff6ff;color:#2563eb;font-size:.72rem">
                        {{ $software->category_label }}
                    </span>
                    <span class="badge bg-{{ $software->criticality_badge }} ms-1">{{ ucfirst($software->criticality) }}</span>
                    <span class="badge bg-{{ $software->license_required ? 'success' : 'secondary' }} ms-1">
                        {{ $software->license_required ? 'License Required' : 'No License Required' }}
                    </span>
                    @if($software->version)
                        <span class="ms-1 text-muted" style="font-size:.8rem">v{{ $software->version }}</span>
                    @endif
                    @if($software->edition)
                        <span class="ms-1 text-muted" style="font-size:.8rem">{{ $software->edition }}</span>
                    @endif
                </p>
            </div>
        </div>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.software-licenses.create', ['software_id' => $software->id]) }}" class="btn btn-primary btn-sm">
            <i class="bi bi-plus-lg me-1"></i>Add License
        </a>
        <a href="{{ route('admin.software.edit', $software) }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-pencil me-1"></i>Edit
        </a>
    </div>
</div>

{{-- Stats --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="card stat-card-gradient grad-blue h-100">
            <div class="card-body">
                <div class="d-flex align-items-center gap-3">
                    <div class="stat-icon"><i class="bi bi-key-fill"></i></div>
                    <div>
                        <div class="stat-number">{{ $stats['total_licenses'] }}</div>
                        <div class="stat-label">Total Licenses</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card stat-card-gradient grad-green h-100">
            <div class="card-body">
                <div class="d-flex align-items-center gap-3">
                    <div class="stat-icon"><i class="bi bi-hdd-fill"></i></div>
                    <div>
                        <div class="stat-number">{{ $stats['total_seats'] }}</div>
                        <div class="stat-label">Total Seats</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card stat-card-gradient grad-purple h-100">
            <div class="card-body">
                <div class="d-flex align-items-center gap-3">
                    <div class="stat-icon"><i class="bi bi-person-check-fill"></i></div>
                    <div>
                        <div class="stat-number">{{ $stats['used_seats'] }}</div>
                        <div class="stat-label">Used Seats</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card stat-card-gradient {{ $stats['expiring_soon'] > 0 ? 'grad-orange' : 'grad-teal' }} h-100">
            <div class="card-body">
                <div class="d-flex align-items-center gap-3">
                    <div class="stat-icon"><i class="bi bi-clock-history"></i></div>
                    <div>
                        <div class="stat-number">{{ $stats['available_seats'] }}</div>
                        <div class="stat-label">Available Seats</div>
                        @if($stats['expiring_soon'] > 0)
                            <div class="stat-sub">{{ $stats['expiring_soon'] }} expiring soon</div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Description --}}
@if($software->description)
<div class="table-card mb-4 p-3" style="color:#475569;font-size:.875rem;line-height:1.7">
    {{ $software->description }}
    @if($software->publisher_website)
        <br><a href="{{ $software->publisher_website }}" target="_blank" class="text-primary" style="font-size:.8rem">
            <i class="bi bi-box-arrow-up-right me-1"></i>{{ $software->publisher_website }}
        </a>
    @endif
</div>
@endif

<div class="table-card mb-4">
    <div class="card-header"><i class="bi bi-sliders me-2 text-primary"></i>SAM Governance</div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-3">
                <div class="text-muted small fw-bold text-uppercase">Type</div>
                <div class="fw-bold">{{ $software->software_type_label }}</div>
            </div>
            <div class="col-md-3">
                <div class="text-muted small fw-bold text-uppercase">Metric</div>
                <div class="fw-bold">{{ $software->license_metric_label }}</div>
            </div>
            <div class="col-md-3">
                <div class="text-muted small fw-bold text-uppercase">Criticality</div>
                <span class="badge bg-{{ $software->criticality_badge }}">{{ ucfirst($software->criticality) }}</span>
            </div>
            <div class="col-md-3">
                <div class="text-muted small fw-bold text-uppercase">Publisher Trust</div>
                <span class="badge bg-{{ $software->trusted_publisher ? 'success' : 'secondary' }}">
                    {{ $software->trusted_publisher ? 'Trusted' : 'Not marked' }}
                </span>
            </div>
        </div>
    </div>
</div>

{{-- Licenses Table --}}
<div class="table-card">
    <div class="card-header d-flex align-items-center justify-content-between">
        <div><i class="bi bi-key me-2 text-primary"></i>License Records</div>
        <a href="{{ route('admin.software-licenses.create', ['software_id' => $software->id]) }}" class="btn btn-primary btn-sm">
            <i class="bi bi-plus-lg me-1"></i>Add License
        </a>
    </div>
    <div class="table-responsive">
        @if($software->licenses->isEmpty())
            <div class="text-center py-5">
                <i class="bi bi-key" style="font-size:2rem;color:#cbd5e1"></i>
                <div class="mt-2 text-muted">No license records yet.</div>
                <a href="{{ route('admin.software-licenses.create', ['software_id' => $software->id]) }}"
                   class="btn btn-primary btn-sm mt-3">
                    <i class="bi bi-plus-lg me-1"></i>Add First License
                </a>
            </div>
        @else
            <table class="table table-hover mb-0" style="font-size:.855rem">
                <thead style="background:#f8fafc;border-bottom:2px solid #e9ecef">
                    <tr>
                        <th class="px-4 py-3" style="font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.6px;color:#64748b">Type</th>
                        <th class="py-3" style="font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.6px;color:#64748b">Supplier</th>
                        <th class="py-3" style="font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.6px;color:#64748b">Seats</th>
                        <th class="py-3" style="font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.6px;color:#64748b">Compliance</th>
                        <th class="py-3" style="font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.6px;color:#64748b">Renewal</th>
                        <th class="py-3" style="font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.6px;color:#64748b">Status</th>
                        <th class="py-3"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($software->licenses as $lic)
                    <tr style="border-bottom:1px solid #f1f5f9">
                        <td class="px-4 py-3">
                            <span class="fw-600" style="color:#1e293b">{{ $lic->license_type_label }}</span>
                            @if($lic->license_key)
                                <div class="text-muted" style="font-size:.7rem;font-family:monospace">
                                    {{ Str::limit($lic->license_key, 20) }}
                                </div>
                            @endif
                        </td>
                        <td class="py-3">{{ optional($lic->supplier)->name ?? '—' }}</td>
                        <td class="py-3">
                            <span class="fw-600">{{ $lic->used_seats }}</span>
                            <span class="text-muted">/ {{ $lic->seats }}</span>
                        </td>
                        <td class="py-3">
                            <span class="badge bg-{{ $lic->compliance_badge }}">
                                {{ ucfirst($lic->compliance_status) }}
                            </span>
                        </td>
                        <td class="py-3">
                            @if($lic->renewal_date || $lic->expiry_date)
                                <span class="{{ $lic->is_expired ? 'text-danger fw-600' : ($lic->is_expiring_soon ? 'text-warning fw-600' : 'text-muted') }}" style="font-size:.82rem">
                                    {{ ($lic->renewal_date ?? $lic->expiry_date)->format('d-m-Y') }}
                                </span>
                                <div class="text-muted small">{{ $lic->renewal_recommendation_label }}</div>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td class="py-3">
                            <span class="badge bg-{{ $lic->status_badge }}">{{ $lic->status_label }}</span>
                        </td>
                        <td class="py-3 pe-3">
                            <a href="{{ route('admin.software-licenses.show', $lic) }}" class="btn btn-sm btn-outline-primary" style="font-size:.75rem">
                                <i class="bi bi-eye me-1"></i>View
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
</div>
@endsection
