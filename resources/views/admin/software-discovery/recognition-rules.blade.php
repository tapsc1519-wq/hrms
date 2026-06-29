@extends('layouts.app')
@section('title', 'Recognition Rules')

@section('content')
<div class="page-header d-flex align-items-start justify-content-between flex-wrap gap-2">
    <div>
        <a href="{{ route('admin.software-normalization.index') }}" class="back-link"><i class="bi bi-arrow-left"></i> Normalization Workbench</a>
        <h4>Recognition Rules</h4>
        <p>Maintain the approved patterns used to auto-map discovered software into the catalogue.</p>
    </div>
</div>

<div class="row g-3 mb-3">
    @foreach([
        ['Rules', $stats['rules'], 'grad-blue', 'Approved recognition patterns'],
        ['Publisher Scoped', $stats['publisher_scoped'], 'grad-green', 'Rules narrowed by publisher'],
        ['Avg Confidence', $stats['avg_confidence'].'%', 'grad-purple', 'Expected auto-map confidence'],
        ['Mapped Titles', $stats['mapped_titles'], 'grad-orange', 'Catalogue records with rules'],
    ] as [$label, $value, $color, $sub])
    <div class="col-sm-6 col-xl-3"><div class="stat-card-gradient {{ $color }}"><div class="card-body"><div class="stat-label">{{ $label }}</div><div class="stat-number">{{ $value }}</div><div class="stat-sub">{{ $sub }}</div></div></div></div>
    @endforeach
</div>

<div class="row g-3">
    <div class="col-xl-8">
        <div class="table-card mb-3">
            <div class="card-body">
                <form method="GET" class="row g-2 align-items-end">
                    <div class="col-lg-5"><label class="form-label">Search</label><input type="search" name="search" value="{{ request('search') }}" class="form-control" placeholder="Pattern, publisher or software"></div>
                    <div class="col-lg-4"><label class="form-label">Software</label><select name="software_id" class="form-select"><option value="">All software</option>@foreach($software as $item)<option value="{{ $item->id }}" @selected(request('software_id') == $item->id)>{{ $item->name }}{{ $item->vendor ? ' - '.$item->vendor : '' }}</option>@endforeach</select></div>
                    <div class="col-sm-6 col-lg-1"><label class="form-label">Rows</label><select name="per_page" class="form-select">@foreach([25,50,100] as $size)<option value="{{ $size }}" @selected($perPage === $size)>{{ $size }}</option>@endforeach</select></div>
                    <div class="col-auto"><button class="btn btn-primary" title="Apply filters"><i class="bi bi-funnel"></i></button></div>
                    @if(request()->hasAny(['search','software_id','per_page']))<div class="col-auto"><a href="{{ route('admin.software-recognition-rules.index') }}" class="btn btn-outline-secondary">Clear</a></div>@endif
                </form>
            </div>
        </div>

        <div class="table-card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <span class="fw-semibold">Rule Register</span>
                <span class="badge bg-light text-dark">{{ $rules->total() }}</span>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead><tr><th class="ps-4">Pattern</th><th>Software</th><th>Confidence</th><th>Approved By</th><th class="text-end pe-4">Action</th></tr></thead>
                    <tbody>
                        @forelse($rules as $rule)
                        <tr>
                            <td class="ps-4"><div class="fw-bold">{{ $rule->raw_name_pattern }}</div><div class="text-muted small">{{ $rule->raw_publisher_pattern ?: 'Any publisher' }}</div></td>
                            <td><div>{{ $rule->software?->name ?? 'Unknown software' }}</div><div class="text-muted small">{{ $rule->software?->vendor ?: 'Publisher not recorded' }}</div></td>
                            <td><span class="badge bg-{{ $rule->confidence_score >= 90 ? 'success' : ($rule->confidence_score >= 70 ? 'warning' : 'secondary') }}">{{ $rule->confidence_score }}%</span></td>
                            <td>{{ $rule->approvedBy?->name ?? 'Unknown' }}<div class="text-muted small">{{ $rule->created_at?->format('d M Y') }}</div></td>
                            <td class="text-end pe-4"><form method="POST" action="{{ route('admin.software-recognition-rules.destroy', $rule) }}" onsubmit="return confirm('Delete this recognition rule? Future imports may stop auto-mapping this pattern.')">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button></form></td>
                        </tr>
                        @empty
                        <tr><td colspan="5" class="text-center text-muted py-5"><i class="bi bi-diagram-3 fs-1 d-block mb-2 opacity-25"></i>No recognition rules match these filters.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($rules->hasPages())<div class="p-3 border-top">{{ $rules->links() }}</div>@endif
        </div>
    </div>

    <div class="col-xl-4">
        <div class="form-card">
            <div class="form-card-header"><div class="icon-wrap icon-blue"><i class="bi bi-plus-lg"></i></div>Create Rule</div>
            <div class="form-card-body">
                <form method="POST" action="{{ route('admin.software-recognition-rules.store') }}">
                    @csrf
                    <div class="mb-3"><label class="form-label">Software <span class="req">*</span></label><select name="software_id" class="form-select @error('software_id') is-invalid @enderror" required><option value="">Select software</option>@foreach($software as $item)<option value="{{ $item->id }}" @selected(old('software_id') == $item->id)>{{ $item->name }}{{ $item->vendor ? ' - '.$item->vendor : '' }}</option>@endforeach</select>@error('software_id')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                    <div class="mb-3"><label class="form-label">Name Pattern <span class="req">*</span></label><input type="text" name="raw_name_pattern" value="{{ old('raw_name_pattern') }}" class="form-control @error('raw_name_pattern') is-invalid @enderror" maxlength="255" required placeholder="Example: Microsoft 365 Apps">@error('raw_name_pattern')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                    <div class="mb-3"><label class="form-label">Publisher Pattern</label><input type="text" name="raw_publisher_pattern" value="{{ old('raw_publisher_pattern') }}" class="form-control @error('raw_publisher_pattern') is-invalid @enderror" maxlength="255" placeholder="Example: Microsoft">@error('raw_publisher_pattern')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                    <div class="mb-3"><label class="form-label">Confidence Score</label><input type="number" name="confidence_score" value="{{ old('confidence_score', 95) }}" class="form-control @error('confidence_score') is-invalid @enderror" min="1" max="100" required>@error('confidence_score')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                    <button class="btn btn-primary w-100"><i class="bi bi-save me-1"></i>Save Rule</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
