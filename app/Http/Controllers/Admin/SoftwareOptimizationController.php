<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SoftwareAssignment;
use App\Models\SoftwareDiscovery;
use App\Models\SoftwareUsageReview;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class SoftwareOptimizationController extends Controller
{
    public function index(Request $request)
    {
        $threshold = in_array((int) $request->input('days', 60), [30, 60, 90, 120, 180], true)
            ? (int) $request->input('days', 60)
            : 60;
        $cutoff = today()->subDays($threshold)->toDateString();
        $query = $this->assignmentQuery();

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($builder) use ($search) {
                $builder->whereHas('user', fn ($user) => $user
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('employee_id', 'like', "%{$search}%"))
                    ->orWhereHas('license.software', fn ($software) => $software
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('vendor', 'like', "%{$search}%"));
            });
        }

        $view = $request->input('view', 'candidates');
        if ($view === 'recent') {
            $this->whereRecentUsage($query, $cutoff);
        } elseif ($view === 'no_data') {
            $this->whereNoUsageData($query);
        } else {
            $view = 'candidates';
            $this->whereInactiveUsage($query, $cutoff);
        }

        $assignments = $query->paginate(25)->withQueryString();
        $openReviewAssignmentIds = SoftwareUsageReview::where('organization_id', $this->orgId())
            ->where('status', 'pending_user')
            ->pluck('software_assignment_id');

        $assignments->getCollection()->each(function ($assignment) {
            $lastUsed = $assignment->last_used_date ? Carbon::parse($assignment->last_used_date) : null;
            $assignment->setAttribute('inactivity_days', $lastUsed
                ? $lastUsed->diffInDays(today())
                : $assignment->assigned_date->diffInDays(today()));
            $assignment->setAttribute('estimated_annual_savings', $this->annualSeatCost($assignment));
        });

        $stats = [
            'candidates' => $this->countFor(fn ($q) => $this->whereInactiveUsage($q, $cutoff)),
            'recent' => $this->countFor(fn ($q) => $this->whereRecentUsage($q, $cutoff)),
            'no_data' => $this->countFor(fn ($q) => $this->whereNoUsageData($q)),
            'open_reviews' => $openReviewAssignmentIds->count(),
        ];

        $reviews = SoftwareUsageReview::where('organization_id', $this->orgId())
            ->with(['assignment.user', 'assignment.license.software', 'owner', 'decidedBy'])
            ->latest()
            ->paginate(15, ['*'], 'reviews_page')
            ->withQueryString();
        $owners = User::where('organization_id', $this->orgId())
            ->whereIn('role', ['admin', 'staff'])->where('status', 'active')
            ->orderBy('name')->get(['id', 'name']);

        return view('admin.software-optimization.index', compact(
            'assignments', 'reviews', 'stats', 'threshold', 'view',
            'openReviewAssignmentIds', 'owners'
        ));
    }

    public function startReview(Request $request, SoftwareAssignment $assignment)
    {
        $this->authorizeAssignment($assignment);
        abort_unless($assignment->status === 'active', 422, 'Only active assignments can be reviewed.');

        $validated = $request->validate([
            'due_date' => 'nullable|date|after_or_equal:today',
            'owner_id' => 'nullable|integer|exists:users,id',
            'notes' => 'nullable|string|max:1000',
        ]);
        if (! empty($validated['owner_id'])) {
            abort_unless(User::where('organization_id', $this->orgId())->whereKey($validated['owner_id'])->exists(), 403);
        }

        $alreadyOpen = SoftwareUsageReview::where('organization_id', $this->orgId())
            ->where('software_assignment_id', $assignment->id)
            ->where('status', 'pending_user')->exists();
        if ($alreadyOpen) {
            return back()->with('error', 'An open usage review already exists for this allocation.');
        }

        $discovery = $this->latestDiscovery($assignment);
        $lastUsed = $discovery?->last_used_date;
        $inactivityDays = ($lastUsed ?: $assignment->assigned_date)->diffInDays(today());

        SoftwareUsageReview::create([
            'organization_id' => $this->orgId(),
            'software_assignment_id' => $assignment->id,
            'software_discovery_id' => $discovery?->id,
            'status' => 'pending_user',
            'inactivity_days' => $inactivityDays,
            'last_used_date' => $lastUsed,
            'estimated_annual_savings' => $this->annualSeatCost($assignment),
            'due_date' => $validated['due_date'] ?? today()->addDays(7),
            'owner_id' => $validated['owner_id'] ?? auth()->id(),
            'created_by' => auth()->id(),
            'notes' => $validated['notes'] ?? 'Please confirm whether this software is still required for your work.',
        ]);

        return back()->with('success', 'Usage review sent to the employee.');
    }

    public function retain(Request $request, SoftwareUsageReview $review)
    {
        $this->authorizeReview($review);
        $validated = $request->validate(['decision_notes' => 'required|string|min:5|max:1000']);
        abort_unless($review->status === 'pending_user', 422, 'This review is already closed.');

        $review->update([
            'status' => 'retained', 'decision_notes' => $validated['decision_notes'],
            'decided_by' => auth()->id(), 'decided_at' => now(),
        ]);

        return back()->with('success', 'The allocation was retained with an audit note.');
    }

    public function reclaim(Request $request, SoftwareUsageReview $review)
    {
        $this->authorizeReview($review);
        $validated = $request->validate(['decision_notes' => 'required|string|min:5|max:1000']);
        $this->completeReclaim($review, $validated['decision_notes']);

        return back()->with('success', 'License reclaimed and the seat is now available.');
    }

    private function assignmentQuery(): Builder
    {
        $organizationId = $this->orgId();
        $latestDate = SoftwareDiscovery::select('last_used_date')
            ->whereColumn('user_id', 'software_assignments.user_id')
            ->whereColumn('software_id', 'optimization_licenses.software_id')
            ->where('organization_id', $organizationId)->where('status', 'mapped')
            ->orderByRaw('last_used_date IS NULL')->orderByDesc('last_used_date')->limit(1);
        $latestId = SoftwareDiscovery::select('id')
            ->whereColumn('user_id', 'software_assignments.user_id')
            ->whereColumn('software_id', 'optimization_licenses.software_id')
            ->where('organization_id', $organizationId)->where('status', 'mapped')
            ->orderByRaw('last_used_date IS NULL')->orderByDesc('last_used_date')->latest('id')->limit(1);

        return SoftwareAssignment::query()
            ->join('software_licenses as optimization_licenses', 'optimization_licenses.id', '=', 'software_assignments.software_license_id')
            ->where('optimization_licenses.organization_id', $organizationId)
            ->where('software_assignments.status', 'active')
            ->select('software_assignments.*')
            ->addSelect(['last_used_date' => $latestDate, 'latest_discovery_id' => $latestId])
            ->with(['user.department', 'license.software']);
    }

    private function usageExistsQuery(?string $cutoff = null)
    {
        return DB::table('software_discoveries as usage_discoveries')->selectRaw('1')
            ->whereColumn('usage_discoveries.user_id', 'software_assignments.user_id')
            ->whereColumn('usage_discoveries.software_id', 'optimization_licenses.software_id')
            ->where('usage_discoveries.organization_id', $this->orgId())
            ->where('usage_discoveries.status', 'mapped')
            ->when($cutoff, fn ($query) => $query->whereDate('usage_discoveries.last_used_date', '>=', $cutoff));
    }

    private function whereInactiveUsage(Builder $query, string $cutoff): Builder
    {
        return $query->whereExists($this->usageExistsQuery())
            ->whereNotExists($this->usageExistsQuery($cutoff));
    }

    private function whereRecentUsage(Builder $query, string $cutoff): Builder
    {
        return $query->whereExists($this->usageExistsQuery($cutoff));
    }

    private function whereNoUsageData(Builder $query): Builder
    {
        return $query->whereNotExists($this->usageExistsQuery());
    }

    private function countFor(callable $scope): int
    {
        $query = $this->assignmentQuery();
        $scope($query);
        return $query->count('software_assignments.id');
    }

    private function latestDiscovery(SoftwareAssignment $assignment): ?SoftwareDiscovery
    {
        return SoftwareDiscovery::where('organization_id', $this->orgId())
            ->where('user_id', $assignment->user_id)
            ->where('software_id', $assignment->license->software_id)
            ->where('status', 'mapped')
            ->orderByRaw('last_used_date IS NULL')->orderByDesc('last_used_date')->latest('id')->first();
    }

    private function annualSeatCost(SoftwareAssignment $assignment): float
    {
        $cost = (float) ($assignment->license->unit_cost ?: ($assignment->license->seats > 0 ? $assignment->license->total_cost / $assignment->license->seats : 0));
        return match ($assignment->license->subscription_period) {
            'monthly' => $cost * 12,
            'quarterly' => $cost * 4,
            'multi_year' => $cost / 3,
            'perpetual' => 0,
            default => $cost,
        };
    }

    private function completeReclaim(SoftwareUsageReview $review, string $notes): void
    {
        abort_unless($review->status === 'pending_user', 422, 'This review is already closed.');
        DB::transaction(function () use ($review, $notes) {
            $assignment = SoftwareAssignment::whereKey($review->software_assignment_id)->lockForUpdate()->firstOrFail();
            if ($assignment->status === 'active') {
                $assignment->update(['status' => 'returned', 'returned_date' => today()]);
            }
            $review->update([
                'status' => 'reclaimed', 'decision_notes' => $notes,
                'decided_by' => auth()->id(), 'decided_at' => now(),
            ]);
        });
    }

    private function authorizeAssignment(SoftwareAssignment $assignment): void
    {
        abort_unless($assignment->license()->where('organization_id', $this->orgId())->exists(), 403);
        $assignment->loadMissing(['license.software', 'user']);
    }

    private function authorizeReview(SoftwareUsageReview $review): void
    {
        abort_if($review->organization_id !== $this->orgId(), 403);
    }
}
