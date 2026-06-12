@extends('layouts.app')
@section('title', 'Facilities')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="page-title mb-0">Facilities</h4>
        <p class="page-subtitle mb-0">{{ $facilities->total() }} facilit{{ $facilities->total() == 1 ? 'y' : 'ies' }} across all states</p>
    </div>
    <a href="{{ route('admin.facilities.create') }}" class="btn btn-primary">
        <i class="bi bi-building-add me-1"></i>Add Facility
    </a>
</div>

@if($facilities->isEmpty())
<div class="table-card p-5 text-center text-muted">
    <i class="bi bi-building fs-1 d-block mb-3 text-secondary"></i>
    <h5>No facilities yet</h5>
    <p class="mb-3">Add your first facility to start organising work locations.</p>
    <a href="{{ route('admin.facilities.create') }}" class="btn btn-primary">
        <i class="bi bi-building-add me-1"></i>Add Facility
    </a>
</div>
@else
<div class="row g-3">
    @foreach($facilities as $facility)
    <div class="col-md-6 col-xl-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex align-items-start justify-content-between mb-3">
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0
                                    {{ $facility->status === 'active' ? 'bg-primary bg-opacity-10' : 'bg-secondary bg-opacity-10' }}"
                             style="width:44px;height:44px;font-size:1.3rem;">
                            <i class="bi bi-building {{ $facility->status === 'active' ? 'text-primary' : 'text-secondary' }}"></i>
                        </div>
                        <div>
                            <h6 class="mb-0 fw-semibold">{{ $facility->name }}</h6>
                            @if($facility->code)
                                <small class="text-muted">{{ $facility->code }}</small>
                            @endif
                        </div>
                    </div>
                    <span class="badge bg-{{ $facility->status === 'active' ? 'success' : 'secondary' }}">
                        {{ ucfirst($facility->status) }}
                    </span>
                </div>

                <ul class="list-unstyled small text-muted mb-3">
                    @if($facility->state || $facility->city)
                    <li class="mb-1">
                        <i class="bi bi-geo-alt me-2"></i>
                        {{ collect([$facility->city, $facility->state, $facility->country])->filter()->implode(', ') }}
                    </li>
                    @endif
                    @if($facility->phone)
                    <li class="mb-1"><i class="bi bi-telephone me-2"></i>{{ $facility->phone }}</li>
                    @endif
                    @if($facility->email)
                    <li class="mb-1"><i class="bi bi-envelope me-2"></i>{{ $facility->email }}</li>
                    @endif
                    <li>
                        <i class="bi bi-pin-map me-2"></i>
                        <strong>{{ $facility->locations_count }}</strong>
                        work location{{ $facility->locations_count == 1 ? '' : 's' }}
                    </li>
                </ul>
            </div>
            <div class="card-footer bg-transparent border-top d-flex gap-2">
                <a href="{{ route('admin.facilities.show', $facility) }}"
                   class="btn btn-sm btn-outline-primary flex-grow-1">
                    <i class="bi bi-eye me-1"></i>Manage
                </a>
                <a href="{{ route('admin.facilities.edit', $facility) }}"
                   class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-pencil"></i>
                </a>
                <form action="{{ route('admin.facilities.destroy', $facility) }}" method="POST"
                      onsubmit="return confirm('Delete {{ $facility->name }}? Work locations will be unlinked.')">
                    @csrf @method('DELETE')
                    <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                </form>
            </div>
        </div>
    </div>
    @endforeach
</div>

<div class="mt-3">{{ $facilities->links() }}</div>
@endif
@endsection
