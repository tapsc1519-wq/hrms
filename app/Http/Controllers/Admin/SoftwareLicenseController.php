<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Software;
use App\Models\SoftwareAssignment;
use App\Models\SoftwareLicense;
use App\Models\SoftwareRenewalDecision;
use App\Models\User;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class SoftwareLicenseController extends Controller
{
    public function index(Request $request)
    {
        $perPage = in_array((int) $request->input('per_page'), [25, 50, 100], true) ? (int) $request->input('per_page') : 25;
        $query = SoftwareLicense::where('organization_id', $this->orgId())
            ->with(['software', 'supplier'])
            ->latest();

        if ($request->filled('status'))   $query->where('status', $request->status);
        if ($request->filled('software_id')) $query->where('software_id', $request->software_id);
        if ($request->evidence === 'missing') $this->applyEvidenceMissingFilter($query);
        if ($request->evidence === 'complete') $this->applyEvidenceCompleteFilter($query);

        $licenses = $query->paginate($perPage)->withQueryString();

        $softwareList = Software::where('organization_id', $this->orgId())
            ->orderBy('name')->get(['id','name']);

        // Compliance summary
        $allActive = SoftwareLicense::where('organization_id', $this->orgId())
            ->where('status','active')->with('activeAssignments')->get();
        $allLicenses = SoftwareLicense::where('organization_id', $this->orgId())->get();

        $compliance = [
            'over'   => $allActive->filter(fn($l) => $l->is_over_licensed)->count(),
            'expiring' => $allActive->filter(fn($l) => $l->is_expiring_soon)->count(),
            'total_seats' => $allActive->sum('seats'),
            'used_seats'  => $allActive->sum(fn($l) => $l->used_seats),
            'evidence_gaps' => $allLicenses->filter(fn ($license) => count($license->evidence_issues) > 0)->count(),
            'evidence_complete' => $allLicenses->filter(fn ($license) => count($license->evidence_issues) === 0)->count(),
            'evidence_score' => (int) round($allLicenses->avg(fn ($license) => $license->evidence_score) ?? 0),
        ];

        return view('admin.software-licenses.index', compact('licenses', 'softwareList', 'compliance', 'perPage'));
    }

    public function create(Request $request)
    {
        $software = Software::where('organization_id', $this->orgId())
            ->orderBy('name')->get(['id','name','vendor']);
        $suppliers = Supplier::where('organization_id', $this->orgId())
            ->orderBy('name')->get(['id','name']);
        $selectedSoftware = $request->filled('software_id')
            ? $request->software_id : null;

        return view('admin.software-licenses.create', compact('software', 'suppliers', 'selectedSoftware'));
    }

    public function renewals(Request $request)
    {
        $query = SoftwareLicense::where('organization_id', $this->orgId())
            ->where('status', 'active')
            ->with(['software', 'supplier', 'activeAssignments', 'activeRenewalDecision.owner'])
            ->orderByRaw('COALESCE(renewal_date, expiry_date) IS NULL')
            ->orderByRaw('COALESCE(renewal_date, expiry_date) ASC');

        if ($request->filled('window')) {
            $days = (int) $request->window;

            if ($days > 0) {
                $query->where(function ($q) use ($days) {
                    $q->whereBetween('renewal_date', [now()->toDateString(), now()->addDays($days)->toDateString()])
                        ->orWhereBetween('expiry_date', [now()->toDateString(), now()->addDays($days)->toDateString()]);
                });
            }
        }

        $licenses = $query->get();

        if ($request->filled('recommendation')) {
            $licenses = $licenses->filter(fn ($license) => $license->renewal_recommendation === $request->recommendation)->values();
        }
        if ($request->plan_status === 'planned') {
            $licenses = $licenses->filter(fn ($license) => $license->activeRenewalDecision)->values();
        } elseif ($request->plan_status === 'unplanned') {
            $licenses = $licenses->reject(fn ($license) => $license->activeRenewalDecision)->values();
        }

        $renewalValue = (float) $licenses->sum(fn ($license) => $license->total_cost);
        $plannedSpend = (float) $licenses->sum(fn ($license) => $license->activeRenewalDecision?->projected_cost ?? 0);
        $plannedSavings = (float) $licenses->sum(function ($license) {
            $decision = $license->activeRenewalDecision;
            if (! $decision || ! in_array($decision->decision, ['reduce', 'cancel'], true)) return 0;
            return max(0, $license->total_cost - (float) ($decision->projected_cost ?? 0));
        });

        $summary = [
            'expired' => $licenses->filter(fn ($license) => $license->is_expired)->count(),
            'expiring_30' => $licenses->filter(fn ($license) => $license->expiry_date && ! $license->is_expired && $license->expiry_date->lte(now()->addDays(30)))->count(),
            'unused' => $licenses->filter(fn ($license) => $license->used_seats === 0)->count(),
            'reduce' => $licenses->filter(fn ($license) => $license->renewal_recommendation === 'reduce')->count(),
            'renewal_value' => $renewalValue,
            'planned_spend' => $plannedSpend,
            'planned_savings' => $plannedSavings,
        ];

        $licenses = $this->paginateCollection($licenses, 25, 'renewals_page');
        $owners = User::where('organization_id', $this->orgId())->whereIn('role', ['admin', 'staff'])->orderBy('name')->get(['id','name']);
        $decisions = SoftwareRenewalDecision::where('organization_id', $this->orgId())
            ->with(['license.software','owner','createdBy','completedBy'])->latest()
            ->paginate(15, ['*'], 'decisions_page')->withQueryString();

        return view('admin.software-licenses.renewals', [
            'licenses' => $licenses,
            'summary' => $summary,
            'owners' => $owners,
            'decisions' => $decisions,
        ]);
    }

    public function planRenewal(Request $request, SoftwareLicense $softwareLicense)
    {
        abort_if($softwareLicense->organization_id !== $this->orgId(), 404);
        abort_if($softwareLicense->status !== 'active', 422, 'Only an active license can have a renewal plan.');
        abort_if($softwareLicense->activeRenewalDecision()->exists(), 422, 'This license already has an active renewal plan.');
        $validated = $request->validate([
            'decision' => 'required|in:renew,reduce,cancel,manual_review',
            'target_seats' => 'nullable|required_if:decision,renew,reduce|integer|min:1|max:99999',
            'projected_cost' => 'nullable|numeric|min:0|max:999999999999.99',
            'due_date' => 'required|date|after_or_equal:today',
            'owner_id' => 'nullable|integer',
            'rationale' => 'required|string|max:2000',
        ]);
        if (! empty($validated['owner_id'])) {
            abort_unless(User::where('organization_id', $this->orgId())->whereKey($validated['owner_id'])->exists(), 403);
        }
        $activeSeats = $softwareLicense->activeAssignments()->count();
        if (in_array($validated['decision'], ['renew','reduce'], true)) {
            abort_if((int) $validated['target_seats'] < $activeSeats, 422, 'Target seats cannot be lower than the current active allocations.');
        }
        if ($validated['decision'] === 'reduce') {
            abort_if((int) $validated['target_seats'] >= (int) $softwareLicense->seats, 422, 'A reduction plan must use fewer seats than the current license.');
        }
        if (in_array($validated['decision'], ['cancel','manual_review'], true)) {
            $validated['target_seats'] = null;
        }

        SoftwareRenewalDecision::create($validated + [
            'organization_id' => $this->orgId(), 'software_license_id' => $softwareLicense->id,
            'status' => 'planned', 'created_by' => auth()->id(),
        ]);
        return back()->with('success', 'Renewal decision planned for '.$softwareLicense->software?->name.'.');
    }

    public function completeRenewal(Request $request, SoftwareLicense $softwareLicense, SoftwareRenewalDecision $decision)
    {
        abort_if($softwareLicense->organization_id !== $this->orgId(), 404);
        abort_if($decision->organization_id !== $this->orgId() || $decision->software_license_id !== $softwareLicense->id, 404);
        abort_unless($decision->status === 'planned', 422, 'Only a planned renewal decision can be completed.');
        $validated = $request->validate([
            'actual_seats' => 'nullable|integer|min:1|max:99999',
            'actual_cost' => 'nullable|numeric|min:0|max:999999999999.99',
            'new_expiry_date' => 'nullable|date|after:today',
            'new_renewal_date' => 'nullable|date|after:today',
            'completion_notes' => 'required|string|max:2000',
        ]);

        DB::transaction(function () use ($softwareLicense, $decision, $validated) {
            $licenseUpdates = [];
            if ($decision->decision === 'cancel') {
                abort_if($softwareLicense->activeAssignments()->exists(), 422, 'Return all active allocations before cancelling this license.');
                $licenseUpdates['status'] = 'cancelled';
            } elseif (in_array($decision->decision, ['renew','reduce'], true)) {
                $actualSeats = (int) ($validated['actual_seats'] ?? $decision->target_seats);
                abort_if($actualSeats < $softwareLicense->activeAssignments()->count(), 422, 'Actual seats cannot be lower than active allocations.');
                if (in_array($softwareLicense->license_type, ['subscription','per_seat','volume'], true)) {
                    abort_if(empty($validated['new_expiry_date']) && empty($validated['new_renewal_date']), 422, 'Enter a new expiry or renewal date for this license.');
                }
                $licenseUpdates['seats'] = $actualSeats;
                $licenseUpdates['status'] = 'active';
                if (array_key_exists('actual_cost', $validated) && $validated['actual_cost'] !== null) $licenseUpdates['purchase_price'] = $validated['actual_cost'];
                if (! empty($validated['new_expiry_date'])) $licenseUpdates['expiry_date'] = $validated['new_expiry_date'];
                if (! empty($validated['new_renewal_date'])) $licenseUpdates['renewal_date'] = $validated['new_renewal_date'];
            }
            if ($licenseUpdates !== []) $softwareLicense->update($licenseUpdates);
            $decision->update([
                'status' => 'completed',
                'actual_seats' => $validated['actual_seats'] ?? $decision->target_seats,
                'actual_cost' => $validated['actual_cost'] ?? $decision->projected_cost,
                'new_expiry_date' => $validated['new_expiry_date'] ?? null,
                'new_renewal_date' => $validated['new_renewal_date'] ?? null,
                'completion_notes' => $validated['completion_notes'],
                'completed_by' => auth()->id(), 'completed_at' => now(),
            ]);
        });

        return back()->with('success', 'Renewal decision completed and the license record was updated.');
    }

    public function cancelRenewalPlan(SoftwareLicense $softwareLicense, SoftwareRenewalDecision $decision)
    {
        abort_if($softwareLicense->organization_id !== $this->orgId(), 404);
        abort_if($decision->organization_id !== $this->orgId() || $decision->software_license_id !== $softwareLicense->id, 404);
        abort_unless($decision->status === 'planned', 422, 'Only a planned renewal decision can be cancelled.');
        $decision->update(['status' => 'cancelled']);
        return back()->with('success', 'Renewal plan cancelled.');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'software_id'    => 'required|exists:software,id',
            'license_type'   => 'required|in:perpetual,subscription,concurrent,per_seat,per_device,oem,volume,open_source,freeware',
            'seats'          => 'required|integer|min:1|max:99999',
            'license_key'    => 'nullable|string|max:500',
            'purchase_batch' => 'nullable|string|max:100',
            'purchase_date'  => 'nullable|date',
            'expiry_date'    => 'nullable|date|after_or_equal:purchase_date',
            'renewal_date'   => 'nullable|date|after_or_equal:purchase_date',
            'purchase_price' => 'nullable|numeric|min:0',
            'unit_cost'      => 'nullable|numeric|min:0',
            'vendor_id'      => 'nullable|exists:suppliers,id',
            'po_number'      => 'nullable|string|max:100',
            'invoice_number' => 'nullable|string|max:100',
            'agreement_number' => 'nullable|string|max:100',
            'subscription_period' => 'nullable|in:monthly,quarterly,annual,multi_year,perpetual',
            'evidence_document' => 'nullable|file|mimes:pdf,jpg,jpeg,png,doc,docx|max:5120',
            'notes'          => 'nullable|string|max:1000',
        ]);

        $software = Software::findOrFail($validated['software_id']);
        abort_if($software->organization_id !== $this->orgId(), 403);

        if ($request->hasFile('evidence_document')) {
            $validated['evidence_document'] = $request->file('evidence_document')->store('license-evidence', 'public');
        }

        SoftwareLicense::create(array_merge($validated, [
            'organization_id' => $this->orgId(),
            'status'          => 'active',
        ]));

        return redirect()->route('admin.software.show', $validated['software_id'])
            ->with('success', 'License record added.');
    }

    public function show(SoftwareLicense $softwareLicense)
    {
        abort_if($softwareLicense->organization_id !== $this->orgId(), 403);

        $softwareLicense->load(['software','supplier','purchaseOrder','assignments.user','assignments.assignedBy']);

        $employees = User::where('organization_id', $this->orgId())
            ->where('role', 'staff')
            ->orderBy('name')
            ->get(['id','name','email','job_title']);

        return view('admin.software-licenses.show', compact('softwareLicense', 'employees'));
    }

    public function assign(Request $request, SoftwareLicense $softwareLicense)
    {
        abort_if($softwareLicense->organization_id !== $this->orgId(), 403);

        $request->validate([
            'user_id'       => 'required|exists:users,id',
            'assigned_date' => 'required|date',
            'notes'         => 'nullable|string|max:500',
        ]);

        // Warn if already assigned to this user under this license
        $existing = SoftwareAssignment::where('software_license_id', $softwareLicense->id)
            ->where('user_id', $request->user_id)
            ->where('status', 'active')
            ->first();

        if ($existing) {
            return back()->with('error', 'This license is already assigned to that employee.');
        }

        SoftwareAssignment::create([
            'software_license_id' => $softwareLicense->id,
            'user_id'             => $request->user_id,
            'assigned_by'         => auth()->id(),
            'assigned_date'       => $request->assigned_date,
            'notes'               => $request->notes,
            'status'              => 'active',
        ]);

        return back()->with('success', 'License assigned successfully.');
    }

    public function returnLicense(SoftwareLicense $softwareLicense, SoftwareAssignment $assignment)
    {
        abort_if($softwareLicense->organization_id !== $this->orgId(), 403);

        $assignment->update([
            'status'        => 'returned',
            'returned_date' => now()->toDateString(),
        ]);

        return back()->with('success', 'License returned and seat freed.');
    }

    public function destroy(SoftwareLicense $softwareLicense)
    {
        abort_if($softwareLicense->organization_id !== $this->orgId(), 403);
        if ($softwareLicense->evidence_document) {
            Storage::disk('public')->delete($softwareLicense->evidence_document);
        }
        $softwareLicense->delete();
        return redirect()->route('admin.software.show', $softwareLicense->software_id)
            ->with('success', 'License record deleted.');
    }

    private function paginateCollection($items, int $perPage, string $pageName)
    {
        $page = \Illuminate\Pagination\LengthAwarePaginator::resolveCurrentPage($pageName);

        return new \Illuminate\Pagination\LengthAwarePaginator(
            $items->forPage($page, $perPage)->values(),
            $items->count(),
            $perPage,
            $page,
            [
                'path' => request()->url(),
                'pageName' => $pageName,
                'query' => request()->query(),
            ]
        );
    }

    private function applyEvidenceMissingFilter($query): void
    {
        $query->where(function ($q) {
            $q->whereNull('vendor_id')
                ->orWhere(function ($po) {
                    $po->whereNull('purchase_order_id')
                        ->where(fn ($number) => $number->whereNull('po_number')->orWhere('po_number', ''));
                })
                ->orWhereNull('invoice_number')
                ->orWhere('invoice_number', '')
                ->orWhere(function ($cost) {
                    $cost->whereNull('purchase_price')->whereNull('unit_cost');
                })
                ->orWhere(function ($proof) {
                    $proof->whereNull('evidence_document')->whereNull('agreement_number');
                });
        });
    }

    private function applyEvidenceCompleteFilter($query): void
    {
        $query->whereNotNull('vendor_id')
            ->where(function ($q) {
                $q->where(fn ($po) => $po->whereNotNull('po_number')->where('po_number', '!=', ''))
                    ->orWhereNotNull('purchase_order_id');
            })
            ->whereNotNull('invoice_number')
            ->where('invoice_number', '!=', '')
            ->where(function ($q) {
                $q->whereNotNull('purchase_price')->orWhereNotNull('unit_cost');
            })
            ->where(function ($q) {
                $q->whereNotNull('evidence_document')->orWhereNotNull('agreement_number');
            });
    }
}
