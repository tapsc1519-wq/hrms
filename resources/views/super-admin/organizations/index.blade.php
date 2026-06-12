@extends('layouts.app')

@section('title', 'Organizations')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="page-title mb-0">Organizations</h4>
        <p class="page-subtitle mb-0">{{ $organizations->total() }} tenants registered</p>
    </div>
    <a href="{{ route('super-admin.organizations.create') }}" class="btn btn-primary">
        <i class="bi bi-building-add me-1"></i>Add Organization
    </a>
</div>

<div class="table-card mb-3">
    <div class="card-body py-2">
        <form method="GET" class="row g-2 align-items-center">
            <div class="col-md-4">
                <input type="text" name="search" class="form-control form-control-sm" placeholder="Search by name or email..." value="{{ request('search') }}">
            </div>
            <div class="col-md-2">
                <select name="status" class="form-select form-select-sm">
                    <option value="">All Accounts</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                    <option value="suspended" {{ request('status') === 'suspended' ? 'selected' : '' }}>Suspended</option>
                </select>
            </div>
            <div class="col-md-2">
                <select name="billing_status" class="form-select form-select-sm">
                    <option value="">All Billing</option>
                    @foreach(['trial' => 'Trial', 'active' => 'Active', 'overdue' => 'Overdue', 'suspended' => 'Suspended', 'cancelled' => 'Cancelled'] as $value => $label)
                        <option value="{{ $value }}" {{ request('billing_status') === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <select name="trial" class="form-select form-select-sm">
                    <option value="">All Trials</option>
                    <option value="ending_soon" {{ request('trial') === 'ending_soon' ? 'selected' : '' }}>Ending Soon</option>
                    <option value="expired" {{ request('trial') === 'expired' ? 'selected' : '' }}>Expired</option>
                </select>
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-primary btn-sm">Filter</button>
                @if(request()->hasAny(['search', 'status', 'billing_status', 'trial']))
                    <a href="{{ route('super-admin.organizations.index') }}" class="btn btn-outline-secondary btn-sm">Clear</a>
                @endif
            </div>
        </form>
    </div>
</div>

<div class="table-card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" style="font-size:.84rem">
            <thead class="table-light">
                <tr>
                    <th>Organization</th>
                    <th>Contact</th>
                    <th>Location</th>
                    <th>Users</th>
                    <th>Modules</th>
                    <th>Monthly</th>
                    <th>Trial Ends</th>
                    <th>Billing</th>
                    <th>Account</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($organizations as $org)
                <tr>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            @if($org->logo)
                                <img src="{{ asset('storage/' . $org->logo) }}" class="rounded" width="36" height="36" style="object-fit:cover">
                            @else
                                <div class="rounded bg-primary text-white d-flex align-items-center justify-content-center fw-bold flex-shrink-0" style="width:36px;height:36px;font-size:.82rem">
                                    {{ strtoupper(substr($org->name, 0, 1)) }}
                                </div>
                            @endif
                            <div>
                                <a href="{{ route('super-admin.organizations.show', $org) }}" class="text-decoration-none fw-semibold">{{ $org->name }}</a>
                                <br><small class="text-muted">{{ $org->slug }}</small>
                            </div>
                        </div>
                    </td>
                    <td>
                        <small>{{ $org->email ?? '-' }}</small>
                        @if($org->phone)<br><small class="text-muted">{{ $org->phone }}</small>@endif
                    </td>
                    <td><small>{{ $org->city ?? '-' }}</small></td>
                    <td><span class="badge bg-light text-dark">{{ $org->users_count }}</span></td>
                    <td><span class="badge bg-primary">{{ $org->modules->where('is_enabled', true)->count() }}</span></td>
                    <td><strong>&#8377;{{ number_format((float) $org->monthly_amount, 0) }}</strong></td>
                    <td>
                        @php($trialDays = $org->trialDaysRemaining())
                        <small class="{{ $org->isTrialExpired() ? 'text-danger fw-semibold' : (!is_null($trialDays) && $trialDays <= 7 ? 'text-warning fw-semibold' : 'text-muted') }}">
                            {{ $org->trial_ends_at?->format('d-m-Y') ?? '-' }}
                        </small>
                        @if($org->isTrialExpired())
                            <br><span class="badge bg-danger" style="font-size:.66rem">Expired</span>
                        @elseif(!is_null($trialDays) && $trialDays <= 7)
                            <br><span class="badge bg-warning text-dark" style="font-size:.66rem">{{ $trialDays <= 0 ? 'Ends today' : $trialDays . ' days left' }}</span>
                        @endif
                    </td>
                    <td>
                        <span class="badge bg-{{ $org->billing_status_badge }}">
                            {{ ucfirst($org->billing_status ?? 'trial') }}
                        </span>
                    </td>
                    <td>
                        <span class="badge bg-{{ $org->status === 'active' ? 'success' : ($org->status === 'suspended' ? 'danger' : 'secondary') }}">
                            {{ ucfirst($org->status) }}
                        </span>
                    </td>
                    <td>
                        <div class="d-flex gap-1">
                            <a href="{{ route('super-admin.organizations.show', $org) }}" class="btn btn-sm btn-outline-secondary" title="View"><i class="bi bi-eye"></i></a>
                            <a href="{{ route('super-admin.organizations.edit', $org) }}" class="btn btn-sm btn-outline-primary" title="Edit"><i class="bi bi-pencil"></i></a>
                            <form action="{{ route('super-admin.organizations.destroy', $org) }}" method="POST"
                                  onsubmit="return confirm('Delete {{ $org->name }}? This will remove all related data.')">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger" title="Delete"><i class="bi bi-trash"></i></button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="10" class="text-center py-5 text-muted">
                        <i class="bi bi-building d-block mb-2" style="font-size:1.45rem"></i>
                        No organizations yet. <a href="{{ route('super-admin.organizations.create') }}">Add one</a>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($organizations->hasPages())
    <div class="card-footer bg-white border-top-0">{{ $organizations->links() }}</div>
    @endif
</div>
@endsection
