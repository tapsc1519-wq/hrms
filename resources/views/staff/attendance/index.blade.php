@extends('layouts.app')

@section('title', 'Attendance')

@section('content')
@php
    $todaySessions = $today?->sessions ?? collect();
    $activeSession = $todaySessions->firstWhere('sign_out_at', null);
    $isSignedIn = (bool) $activeSession;
    $sessionCount = $todaySessions->count();
@endphp
<div class="page-header">
    <h4>My Attendance</h4>
    <p>Sign in and sign out across multiple work sessions, and review your attendance history.</p>
</div>

@if(!$employee)
<div class="form-card">
    <div class="form-card-body text-center py-5">
        <div class="mb-3">
            <span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-warning-subtle text-warning" style="width:64px;height:64px">
                <i class="bi bi-person-vcard fs-2"></i>
            </span>
        </div>
        <h5 class="fw-bold mb-2">Employee Profile Not Linked</h5>
        <p class="text-muted mb-0">
            Your staff login is active, but it is not connected to an employee profile yet. Please ask HR/Admin to open Employees and link this user account to your employee record.
        </p>
    </div>
</div>
@else
<div class="row g-4 mb-4">
    <div class="col-lg-5">
        <div class="stat-card-gradient {{ $isSignedIn ? 'grad-green' : 'grad-blue' }}">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="stat-label">Today</div>
                        <div class="stat-number" style="font-size:1.4rem">{{ now()->format('d-m-Y') }}</div>
                        <div class="stat-sub">
                            @if(!$today)
                                {{ $employee->shift?->name ? 'Shift: '.$employee->shift->name.' ('.$employee->shift->time_range.')' : 'Not signed in yet' }}
                            @elseif($isSignedIn)
                                Working since {{ $activeSession->sign_in_at->format('h:i A') }}
                            @elseif($today->sign_out_at)
                                Last signed out at {{ $today->sign_out_at->format('h:i A') }} &middot; {{ $sessionCount }} session{{ $sessionCount === 1 ? '' : 's' }}
                            @else
                                Ready to sign in
                            @endif
                        </div>
                    </div>
                    <div class="stat-icon"><i class="bi bi-clock"></i></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="form-card h-100">
            <div class="form-card-body d-flex align-items-center justify-content-between gap-3 flex-wrap">
                <div>
                    <h6 class="fw-bold mb-1">Daily Attendance</h6>
                    <div class="text-muted small">You can sign in again after signing out. Each pair is saved as a separate work session.</div>
                </div>
                <div class="d-flex gap-2">
                    <form method="POST" action="{{ route('staff.attendance.sign-in') }}">
                        @csrf
                        <button class="btn btn-success" @disabled($isSignedIn)>
                            <i class="bi bi-box-arrow-in-right me-1"></i> {{ $sessionCount > 0 ? 'Sign In Again' : 'Sign In' }}
                        </button>
                    </form>
                    <form method="POST" action="{{ route('staff.attendance.sign-out') }}">
                        @csrf
                        @method('PATCH')
                        <button class="btn btn-danger" @disabled(!$isSignedIn)>
                            <i class="bi bi-box-arrow-right me-1"></i> Sign Out
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@if($todaySessions->isNotEmpty())
<div class="form-card mb-4">
    <div class="form-card-header d-flex justify-content-between align-items-center">
        <div>
            <h5 class="mb-1">Today's Sessions</h5>
            <p class="text-muted small mb-0">Every sign-in/sign-out pair is tracked separately and added to today's total duration.</p>
        </div>
        <span class="badge bg-primary">{{ $todaySessions->count() }} session{{ $todaySessions->count() === 1 ? '' : 's' }}</span>
    </div>
    <div class="form-card-body">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead style="background:#f8fafc">
                    <tr>
                        <th>#</th>
                        <th>Sign In</th>
                        <th>Sign Out</th>
                        <th>Duration</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($todaySessions as $session)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $session->sign_in_at?->format('h:i A') }}</td>
                            <td>{{ $session->sign_out_at?->format('h:i A') ?? '--' }}</td>
                            <td>{{ $session->sign_out_at ? $session->duration : 'Running' }}</td>
                            <td>
                                <span class="badge bg-{{ $session->sign_out_at ? 'success' : 'primary' }}">
                                    {{ $session->sign_out_at ? 'Completed' : 'Active' }}
                                </span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endif

<div class="form-card mb-4">
    <div class="form-card-header d-flex justify-content-between align-items-center">
        <div>
            <h5 class="mb-1">Attendance Regularization</h5>
            <p class="text-muted small mb-0">Request correction for missed sign-in, missed sign-out, or incorrect time.</p>
        </div>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#regularizationModal">
            <i class="bi bi-clock-history me-1"></i> Request Correction
        </button>
    </div>
    <div class="form-card-body">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead style="background:#f8fafc">
                    <tr>
                        <th>Date</th>
                        <th>Type</th>
                        <th>Requested Time</th>
                        <th>Status</th>
                        <th>Review</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($regularizations as $request)
                        <tr>
                            <td>{{ $request->attendance_date->format('d-m-Y') }}</td>
                            <td>{{ $request->request_type_label }}</td>
                            <td>
                                <div class="small">
                                    In: {{ $request->requested_sign_in_at?->format('h:i A') ?? '-' }}
                                    <br>
                                    Out: {{ $request->requested_sign_out_at?->format('h:i A') ?? '-' }}
                                </div>
                            </td>
                            <td><span class="badge bg-{{ $request->status_badge }}">{{ ucfirst($request->status) }}</span></td>
                            <td class="small text-muted">{{ $request->review_notes ?: '-' }}</td>
                            <td class="text-end">
                                @if($request->status === 'pending')
                                    <form method="POST" action="{{ route('staff.attendance.regularizations.cancel', $request) }}" onsubmit="return confirm('Cancel this request?')">
                                        @csrf
                                        @method('PATCH')
                                        <button class="btn btn-sm btn-outline-danger">Cancel</button>
                                    </form>
                                @else
                                    <span class="text-muted small">Closed</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted py-4">No regularization requests yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="table-card">
    <div class="card-header">My Attendance History</div>
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead style="background:#f8fafc">
                <tr>
                    <th class="ps-4">Date</th>
                    <th>Day Type</th>
                    <th>Sign In</th>
                    <th>Sign Out</th>
                    <th>Sessions</th>
                    <th>Duration</th>
                    <th>Shift Metrics</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($records as $record)
                    <tr>
                        <td class="ps-4">{{ $record->attendance_date->format('d-m-Y') }}</td>
                        <td>
                            <span class="badge bg-{{ $record->day_type === 'holiday' ? 'info' : ($record->day_type === 'weekly_off' ? 'secondary' : 'light text-dark border') }}">
                                {{ $record->holiday?->name ?? $record->day_type_label }}
                            </span>
                        </td>
                        <td>{{ $record->sign_in_at?->format('h:i A') ?? '-' }}</td>
                        <td>{{ $record->sign_out_at?->format('h:i A') ?? '-' }}</td>
                        <td>
                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle">
                                {{ $record->sessions->count() }} session{{ $record->sessions->count() === 1 ? '' : 's' }}
                            </span>
                        </td>
                        <td>
                            {{ $record->work_duration }}
                        </td>
                        <td class="small">
                            <div>{{ $record->shift?->name ?? 'No shift' }}</div>
                            @if($record->late_minutes || $record->early_leave_minutes || $record->overtime_minutes)
                                <div class="text-muted">
                                    Late {{ $record->late_minutes }}m / Early {{ $record->early_leave_minutes }}m / OT {{ $record->overtime_minutes }}m
                                </div>
                            @else
                                <div class="text-muted">On schedule</div>
                            @endif
                        </td>
                        <td>
                            <span class="badge bg-{{ $record->status === 'present' ? 'success' : ($record->status === 'half_day' ? 'warning' : 'danger') }}">
                                {{ ucwords(str_replace('_', ' ', $record->status)) }}
                            </span>
                        </td>
                    </tr>
                    @if($record->sessions->isNotEmpty())
                        <tr>
                            <td></td>
                            <td colspan="7" class="bg-light-subtle">
                                <div class="d-flex flex-wrap gap-2 py-1">
                                    @foreach($record->sessions as $session)
                                        <span class="badge rounded-pill bg-white text-dark border fw-semibold px-3 py-2">
                                            Session {{ $loop->iteration }}:
                                            {{ $session->sign_in_at?->format('h:i A') ?? '-' }}
                                            -
                                            {{ $session->sign_out_at?->format('h:i A') ?? 'Running' }}
                                            <span class="text-muted ms-1">({{ $session->sign_out_at ? $session->duration : 'Active' }})</span>
                                        </span>
                                    @endforeach
                                </div>
                            </td>
                        </tr>
                    @endif
                @empty
                    <tr><td colspan="8" class="text-center text-muted py-4">No attendance records yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($records->hasPages())
        <div class="p-3 border-top">{{ $records->links() }}</div>
    @endif
</div>

<div class="modal fade" id="regularizationModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <form method="POST" action="{{ route('staff.attendance.regularizations.store') }}" class="modal-content border-0" style="border-radius:16px">
            @csrf
            <div class="modal-header border-0 pb-0">
                <div>
                    <h5 class="modal-title fw-bold">Request Attendance Correction</h5>
                    <div class="text-muted small">Submit the correct time and reason for HR/Admin review.</div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Attendance Date <span class="req">*</span></label>
                        <input type="date" name="attendance_date" class="form-control" max="{{ now()->toDateString() }}" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Request Type <span class="req">*</span></label>
                        <select name="request_type" class="form-select" required>
                            <option value="missed_sign_in">Missed Sign In</option>
                            <option value="missed_sign_out">Missed Sign Out</option>
                            <option value="time_correction">Time Correction</option>
                            <option value="work_from_home">Work From Home</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Correct Sign In Time</label>
                        <input type="time" name="requested_sign_in_time" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Correct Sign Out Time</label>
                        <input type="time" name="requested_sign_out_time" class="form-control">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Reason <span class="req">*</span></label>
                        <textarea name="reason" class="form-control" rows="4" required placeholder="Explain why correction is required."></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button class="btn btn-primary"><i class="bi bi-send me-1"></i> Submit Request</button>
            </div>
        </form>
    </div>
</div>
@endif
@endsection
