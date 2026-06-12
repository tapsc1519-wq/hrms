@extends('layouts.app')
@section('title', $facility->name)

@section('content')
{{-- Header --}}
<div class="mb-4">
    <a href="{{ route('admin.facilities.index') }}" class="btn btn-sm btn-outline-secondary mb-3">
        <i class="bi bi-arrow-left me-1"></i>Back to Facilities
    </a>
    <div class="d-flex align-items-center gap-3 flex-wrap">
        <div class="rounded-3 bg-primary bg-opacity-10 d-flex align-items-center justify-content-center flex-shrink-0"
             style="width:48px;height:48px;font-size:1.4rem;">
            <i class="bi bi-building text-primary"></i>
        </div>
        <div class="flex-grow-1">
            <h4 class="page-title mb-0">{{ $facility->name }}</h4>
            <p class="page-subtitle mb-0">
                {{ collect([$facility->city, $facility->state, $facility->country])->filter()->implode(', ') }}
                @if($facility->code) &nbsp;·&nbsp; <code>{{ $facility->code }}</code> @endif
            </p>
        </div>
        <div class="d-flex gap-2">
            <span class="badge bg-{{ $facility->status === 'active' ? 'success' : 'secondary' }} fs-6 px-3 py-2">
                {{ ucfirst($facility->status) }}
            </span>
            <a href="{{ route('admin.facilities.edit', $facility) }}" class="btn btn-sm btn-outline-primary">
                <i class="bi bi-pencil me-1"></i>Edit Facility
            </a>
        </div>
    </div>
</div>

<div class="row g-3">
    {{-- Facility Info --}}
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white fw-semibold">Contact & Details</div>
            <div class="card-body small">
                <div class="row g-2">
                    @if($facility->address)
                    <div class="col-5 text-muted">Address</div>
                    <div class="col-7">{{ $facility->address }}</div>
                    @endif

                    @if($facility->phone)
                    <div class="col-5 text-muted">Phone</div>
                    <div class="col-7">{{ $facility->phone }}</div>
                    @endif

                    @if($facility->email)
                    <div class="col-5 text-muted">Email</div>
                    <div class="col-7">{{ $facility->email }}</div>
                    @endif

                    @if($facility->description)
                    <div class="col-12 mt-2 text-muted fst-italic">{{ $facility->description }}</div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Work Locations --}}
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <span class="fw-semibold">
                    <i class="bi bi-pin-map me-2 text-primary"></i>Work Locations
                    <span class="badge bg-primary rounded-pill ms-1">{{ $facility->locations->count() }}</span>
                </span>
                <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addLocationModal">
                    <i class="bi bi-plus-lg me-1"></i>Add Work Location
                </button>
            </div>

            @if($facility->locations->isEmpty())
            <div class="card-body text-center text-muted py-4">
                <i class="bi bi-pin-map fs-2 d-block mb-2"></i>
                No work locations yet. Add one to start assigning assets.
            </div>
            @else
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" style="font-size:.875rem">
                    <thead class="table-light">
                        <tr>
                            <th>Name</th>
                            <th>Building / Floor / Room</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($facility->locations as $loc)
                        <tr>
                            <td class="fw-semibold">{{ $loc->name }}</td>
                            <td class="text-muted small">
                                {{ collect([$loc->building, $loc->floor, $loc->room])->filter()->implode(' · ') ?: '—' }}
                            </td>
                            <td>
                                <span class="badge bg-{{ $loc->status === 'active' ? 'success' : 'secondary' }}">
                                    {{ ucfirst($loc->status) }}
                                </span>
                            </td>
                            <td class="text-end">
                                <div class="d-flex gap-1 justify-content-end">
                                    <button class="btn btn-sm btn-outline-secondary"
                                            data-bs-toggle="modal"
                                            data-bs-target="#editLocationModal{{ $loc->id }}">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <form action="{{ route('admin.facilities.locations.destroy', [$facility, $loc]) }}"
                                          method="POST"
                                          onsubmit="return confirm('Remove {{ $loc->name }}?')">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif
        </div>
    </div>
</div>

{{-- ── ADD Work Location Modal ─────────────────────────────────────────── --}}
<div class="modal fade" id="addLocationModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('admin.facilities.locations.store', $facility) }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-pin-map-fill me-2 text-primary"></i>Add Work Location</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    @include('admin.facilities._location_fields', ['loc' => null])
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Add Location</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ── EDIT Work Location Modals (one per location) ────────────────────── --}}
@foreach($facility->locations as $loc)
<div class="modal fade" id="editLocationModal{{ $loc->id }}" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('admin.facilities.locations.update', [$facility, $loc]) }}" method="POST">
                @csrf @method('PATCH')
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-pencil me-2"></i>Edit — {{ $loc->name }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    @include('admin.facilities._location_fields', ['loc' => $loc])
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endforeach

@if($errors->any())
@push('scripts')
<script>
    // Re-open the add modal if there are validation errors
    document.addEventListener('DOMContentLoaded', function () {
        new bootstrap.Modal(document.getElementById('addLocationModal')).show();
    });
</script>
@endpush
@endif
@endsection
