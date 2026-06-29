@extends('layouts.app')
@section('title', 'Software Licenses')

@section('content')
<div class="page-header d-flex align-items-center justify-content-between flex-wrap gap-2">
    <div>
        <a href="{{ route('admin.software.index') }}" class="back-link">
            <i class="bi bi-arrow-left"></i> Software Catalog
        </a>
        <h4><i class="bi bi-key me-2 text-primary"></i>All Licenses</h4>
        <p>License compliance overview across all software titles.</p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <a href="{{ route('admin.software-licenses.index', ['evidence' => 'missing']) }}" class="btn btn-outline-danger btn-sm">
            <i class="bi bi-file-earmark-check me-1"></i>Evidence Gaps
        </a>
        <a href="{{ route('admin.software-licenses.renewals', ['window' => 60, 'plan_status' => 'unplanned']) }}" class="btn btn-outline-primary btn-sm">
            <i class="bi bi-calendar2-check me-1"></i>Renewals
        </a>
        <a href="{{ route('admin.software-licenses.create') }}" class="btn btn-primary btn-sm">
            <i class="bi bi-plus-lg me-1"></i>Add License
        </a>
    </div>
</div>

{{-- Compliance Summary --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="card stat-card-gradient grad-blue h-100">
            <div class="card-body">
                <div class="d-flex align-items-center gap-3">
                    <div class="stat-icon"><i class="bi bi-hdd-stack-fill"></i></div>
                    <div>
                        <div class="stat-number">{{ number_format($compliance['total_seats']) }}</div>
                        <div class="stat-label">Total Seats</div>
                        <div class="stat-sub">{{ number_format($compliance['used_seats']) }} in use</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card stat-card-gradient {{ $compliance['over'] > 0 ? 'grad-red' : 'grad-green' }} h-100">
            <div class="card-body">
                <div class="d-flex align-items-center gap-3">
                    <div class="stat-icon"><i class="bi bi-shield-exclamation"></i></div>
                    <div>
                        <div class="stat-number">{{ $compliance['over'] }}</div>
                        <div class="stat-label">Over-Licensed</div>
                        <div class="stat-sub">{{ $compliance['over'] > 0 ? 'Needs attention' : 'All compliant' }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card stat-card-gradient {{ $compliance['expiring'] > 0 ? 'grad-orange' : 'grad-teal' }} h-100">
            <div class="card-body">
                <div class="d-flex align-items-center gap-3">
                    <div class="stat-icon"><i class="bi bi-clock-history"></i></div>
                    <div>
                        <div class="stat-number">{{ $compliance['expiring'] }}</div>
                        <div class="stat-label">Expiring Soon</div>
                        <div class="stat-sub">
                            <a href="{{ route('admin.software-licenses.renewals', ['window' => 30]) }}" class="text-white text-decoration-none">Open renewal queue</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        @php $utilisation = $compliance['total_seats'] > 0 ? round($compliance['used_seats'] / $compliance['total_seats'] * 100) : 0; @endphp
        <div class="card stat-card-gradient grad-purple h-100">
            <div class="card-body">
                <div class="d-flex align-items-center gap-3">
                    <div class="stat-icon"><i class="bi bi-pie-chart-fill"></i></div>
                    <div>
                        <div class="stat-number">{{ $utilisation }}%</div>
                        <div class="stat-label">Utilisation</div>
                        <div class="stat-sub">Seats in use</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card stat-card-gradient {{ $compliance['evidence_gaps'] > 0 ? 'grad-red' : 'grad-green' }} h-100">
            <div class="card-body">
                <div class="d-flex align-items-center gap-3">
                    <div class="stat-icon"><i class="bi bi-file-earmark-check-fill"></i></div>
                    <div>
                        <div class="stat-number">{{ $compliance['evidence_score'] }}%</div>
                        <div class="stat-label">Evidence Quality</div>
                        <div class="stat-sub">{{ $compliance['evidence_gaps'] }} with missing proof</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Filters --}}
<div class="table-card mb-3">
    <div class="card-body py-2">
        <form method="GET" class="d-flex flex-wrap gap-2 align-items-center">
            <select name="software_id" class="form-select" style="width:auto;min-width:180px">
                <option value="">All Software</option>
                @foreach($softwareList as $sw)
                    <option value="{{ $sw->id }}" @selected(request('software_id') == $sw->id)>{{ $sw->name }}</option>
                @endforeach
            </select>
            <select name="status" class="form-select" style="width:auto;min-width:130px">
                <option value="">All Statuses</option>
                <option value="active"    @selected(request('status') === 'active')>Active</option>
                <option value="expired"   @selected(request('status') === 'expired')>Expired</option>
                <option value="cancelled" @selected(request('status') === 'cancelled')>Cancelled</option>
            </select>
            <select name="evidence" class="form-select" style="width:auto;min-width:155px">
                <option value="">All Evidence</option>
                <option value="missing" @selected(request('evidence') === 'missing')>Missing Evidence</option>
                <option value="complete" @selected(request('evidence') === 'complete')>Complete Evidence</option>
            </select>
            <select name="per_page" class="form-select" style="width:auto;min-width:95px">
                @foreach([25,50,100] as $size)
                    <option value="{{ $size }}" @selected($perPage === $size)>{{ $size }} rows</option>
                @endforeach
            </select>
            <button class="btn btn-primary btn-sm">Filter</button>
            @if(request()->hasAny(['software_id','status','evidence','per_page']))
                <a href="{{ route('admin.software-licenses.index') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-x-lg me-1"></i>Clear
                </a>
            @endif
        </form>
    </div>
</div>

<div class="table-card">
    <div class="table-responsive">
        @if($licenses->isEmpty())
            <div class="text-center py-5">
                <i class="bi bi-key" style="font-size:2rem;color:#cbd5e1"></i>
                <div class="mt-2 text-muted">No licenses found.</div>
                <a href="{{ route('admin.software-licenses.create') }}" class="btn btn-primary btn-sm mt-3">
                    <i class="bi bi-plus-lg me-1"></i>Add License
                </a>
            </div>
        @else
            <table class="table table-hover mb-0" style="font-size:.855rem">
                <thead style="background:#f8fafc;border-bottom:2px solid #e9ecef">
                    <tr>
                        <th class="px-4 py-3" style="font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.6px;color:#64748b">Software</th>
                        <th class="py-3" style="font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.6px;color:#64748b">Type</th>
                        <th class="py-3" style="font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.6px;color:#64748b">Seats</th>
                        <th class="py-3" style="font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.6px;color:#64748b">Compliance</th>
                        <th class="py-3" style="font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.6px;color:#64748b">Supplier</th>
                        <th class="py-3" style="font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.6px;color:#64748b">Renewal</th>
                        <th class="py-3" style="font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.6px;color:#64748b">Recommendation</th>
                        <th class="py-3" style="font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.6px;color:#64748b">Evidence</th>
                        <th class="py-3" style="font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.6px;color:#64748b">Status</th>
                        <th class="py-3"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($licenses as $lic)
                    <tr style="border-bottom:1px solid #f1f5f9">
                        <td class="px-4 py-3">
                            <div class="fw-600" style="color:#1e293b">{{ optional($lic->software)->name }}</div>
                            @if($lic->license_key)
                                <code style="font-size:.68rem;color:#64748b">{{ Str::limit($lic->license_key, 18) }}</code>
                            @endif
                        </td>
                        <td class="py-3">{{ $lic->license_type_label }}</td>
                        <td class="py-3">
                            <span class="fw-600">{{ $lic->used_seats }}</span>
                            <span class="text-muted">/ {{ $lic->seats }}</span>
                            <div class="progress mt-1" style="height:4px;width:70px;border-radius:99px">
                                @php $pct = $lic->seats > 0 ? min(100, round($lic->used_seats/$lic->seats*100)) : 0; @endphp
                                <div class="progress-bar {{ $pct >= 100 ? 'bg-danger' : ($pct >= 80 ? 'bg-warning' : 'bg-success') }}"
                                     style="width:{{ $pct }}%"></div>
                            </div>
                        </td>
                        <td class="py-3">
                            <span class="badge bg-{{ $lic->compliance_badge }}">{{ ucfirst($lic->compliance_status) }}</span>
                        </td>
                        <td class="py-3">{{ optional($lic->supplier)->name ?? '—' }}</td>
                        <td class="py-3">
                            @if($lic->renewal_date || $lic->expiry_date)
                                <span class="{{ $lic->is_expired ? 'text-danger fw-600' : ($lic->is_expiring_soon ? 'text-warning fw-600' : 'text-muted') }}" style="font-size:.82rem">
                                    {{ ($lic->renewal_date ?? $lic->expiry_date)->format('d-m-Y') }}
                                </span>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td class="py-3">
                            <span class="badge bg-{{ $lic->renewal_recommendation === 'renew' ? 'success' : ($lic->renewal_recommendation === 'reduce' ? 'info' : 'warning') }}">
                                {{ $lic->renewal_recommendation_label }}
                            </span>
                        </td>
                        <td class="py-3">
                            <span class="badge bg-{{ $lic->evidence_badge }}">{{ $lic->evidence_score }}%</span>
                            @if(count($lic->evidence_issues))
                                <div class="text-muted small mt-1" title="{{ implode(', ', $lic->evidence_issues) }}">{{ Str::limit(implode(', ', $lic->evidence_issues), 42) }}</div>
                            @else
                                <div class="text-muted small mt-1">Complete</div>
                            @endif
                        </td>
                        <td class="py-3">
                            <span class="badge bg-{{ $lic->status_badge }}">{{ $lic->status_label }}</span>
                        </td>
                        <td class="py-3 pe-3">
                            <a href="{{ route('admin.software-licenses.show', $lic) }}" class="btn btn-sm btn-outline-primary" style="font-size:.75rem">
                                <i class="bi bi-eye"></i>
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>

            {{-- Pagination --}}
            @if($licenses->hasPages())
                <div class="px-4 py-3 border-top" style="background:#f8fafc">
                    {{ $licenses->links() }}
                </div>
            @endif
        @endif
    </div>
</div>
@endsection
