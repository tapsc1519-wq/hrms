@extends('layouts.app')

@section('title', 'Edit Partner')

@section('content')
<div class="page-header d-flex justify-content-between align-items-start">
    <div>
        <h4><i class="bi bi-person-workspace me-2 text-primary"></i>Edit Partner</h4>
        <p>Update partner profile, default commission and payout details.</p>
    </div>
    <a href="{{ route('super-admin.partners.index') }}" class="btn btn-outline-primary btn-sm">
        <i class="bi bi-arrow-left me-1"></i>Partners
    </a>
</div>

<div class="row g-3">
    <div class="col-lg-8">
        <form method="POST" action="{{ route('super-admin.partners.update', $partner) }}" class="table-card">
            @include('super-admin.partners._form')
        </form>
    </div>
    <div class="col-lg-4">
        <div class="table-card mb-3">
            <div class="card-header bg-white fw-bold">Commission Summary</div>
            <div class="card-body">
                <div class="d-flex justify-content-between border-bottom pb-2 mb-2">
                    <span class="text-muted">Pending</span>
                    <strong class="text-warning">&#8377;{{ number_format((float) $commissionSummary['pending'], 2) }}</strong>
                </div>
                <div class="d-flex justify-content-between border-bottom pb-2 mb-2">
                    <span class="text-muted">Approved</span>
                    <strong class="text-primary">&#8377;{{ number_format((float) $commissionSummary['approved'], 2) }}</strong>
                </div>
                <div class="d-flex justify-content-between">
                    <span class="text-muted">Paid</span>
                    <strong class="text-success">&#8377;{{ number_format((float) $commissionSummary['paid'], 2) }}</strong>
                </div>
                <a href="{{ route('super-admin.partner-commissions.index', ['partner_id' => $partner->id]) }}" class="btn btn-sm btn-outline-primary w-100 mt-3">
                    <i class="bi bi-cash-coin me-1"></i>View Commission Ledger
                </a>
            </div>
        </div>

        <div class="table-card">
            <div class="card-header bg-white fw-bold">Linked Subscriptions</div>
            <div class="card-body">
                @forelse($partner->subscriptions as $subscription)
                    <div class="border-bottom pb-2 mb-2">
                        <div class="fw-semibold">{{ $subscription->organization?->name ?? 'Organization #' . $subscription->organization_id }}</div>
                        <div class="text-muted small">{{ $subscription->product?->name ?? 'Product #' . $subscription->product_id }}</div>
                        <div class="d-flex justify-content-between align-items-center mt-1">
                            <span class="badge bg-{{ $subscription->status_badge }}">{{ ucfirst($subscription->status) }}</span>
                            <span class="small">&#8377;{{ number_format((float) $subscription->monthly_amount, 2) }} / {{ number_format((float) ($subscription->commission_percent ?? $partner->default_commission_percent), 2) }}%</span>
                        </div>
                    </div>
                @empty
                    <div class="text-center text-muted py-3 small">No product subscriptions linked yet.</div>
                @endforelse
            </div>
            @if(!$partner->subscriptions()->exists())
                <div class="card-footer bg-white">
                    <form method="POST" action="{{ route('super-admin.partners.destroy', $partner) }}" onsubmit="return confirm('Delete this partner?')">
                        @csrf
                        @method('DELETE')
                        <button class="btn btn-outline-danger btn-sm w-100">
                            <i class="bi bi-trash me-1"></i>Delete Partner
                        </button>
                    </form>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
