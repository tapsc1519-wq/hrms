<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DeviceAgent;
use App\Models\Organization;
use App\Models\PurchaseOrderItem;
use App\Models\Software;
use App\Models\SoftwareAssignment;
use App\Models\SoftwareComplianceAction;
use App\Models\SoftwareDiscovery;
use App\Models\SoftwareLicense;
use App\Models\SoftwarePolicyException;
use App\Models\SoftwareRecognitionRule;
use App\Models\SoftwareRequest;
use App\Models\SoftwareRenewalDecision;
use App\Models\SoftwareUsageReview;
use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use ZipArchive;

class SamAuditReportController extends Controller
{
    public function index()
    {
        $organizationId = $this->orgId();
        $stats = [
            'catalog' => Software::where('organization_id', $organizationId)->count(),
            'installations' => SoftwareDiscovery::where('organization_id', $organizationId)->where('is_installed', true)->count(),
            'inventory_gaps' => DeviceAgent::where('organization_id', $organizationId)
                ->where(fn ($query) => $query
                    ->whereNull('asset_id')
                    ->orWhereNull('user_id')
                    ->orWhereNull('last_seen_at')
                    ->orWhere('last_seen_at', '<', now()->subHours(24))
                    ->orWhereNotNull('last_error'))
                ->count(),
            'policy_gaps' => Software::where('organization_id', $organizationId)
                ->where(fn ($query) => $query
                    ->where('policy_status', 'unreviewed')
                    ->orWhereNull('policy_reviewed_at')
                    ->orWhere('policy_reviewed_at', '<', now()->subYear()))
                ->count(),
            'license_seats' => SoftwareLicense::where('organization_id', $organizationId)->where('status', 'active')->sum('seats'),
            'license_evidence_gaps' => SoftwareLicense::where('organization_id', $organizationId)
                ->where('status', 'active')
                ->where(fn ($query) => $query
                    ->whereNull('evidence_document')
                    ->orWhereNull('invoice_number')
                    ->orWhereNull('po_number')
                    ->orWhereNull('vendor_id'))
                ->count(),
            'overdue_actions' => SoftwareComplianceAction::where('organization_id', $organizationId)
                ->where('status', 'open')
                ->whereNotNull('due_date')
                ->where('due_date', '<', now()->toDateString())
                ->count(),
            'overdue_renewals' => SoftwareRenewalDecision::where('organization_id', $organizationId)
                ->where('status', 'planned')
                ->whereNotNull('due_date')
                ->where('due_date', '<', now()->toDateString())
                ->count(),
            'software_requests' => SoftwareRequest::where('organization_id', $organizationId)
                ->whereIn('status', ['pending', 'approved'])
                ->count(),
            'overdue_software_requests' => SoftwareRequest::where('organization_id', $organizationId)
                ->whereIn('status', ['pending', 'approved'])
                ->whereNotNull('needed_by')
                ->where('needed_by', '<', now()->toDateString())
                ->count(),
            'aging_software_requests' => SoftwareRequest::where('organization_id', $organizationId)
                ->whereIn('status', ['pending', 'approved'])
                ->where('created_at', '<', now()->subDays(7))
                ->count(),
            'software_po_items' => PurchaseOrderItem::where('item_type', 'software')
                ->whereHas('purchaseOrder', fn ($query) => $query->where('organization_id', $organizationId))
                ->count(),
            'renewal_plans' => SoftwareRenewalDecision::where('organization_id', $organizationId)->where('status', 'planned')->count(),
            'usage_reviews' => SoftwareUsageReview::where('organization_id', $organizationId)->count(),
            'open_actions' => SoftwareComplianceAction::where('organization_id', $organizationId)->where('status', 'open')->count(),
        ];

        return view('admin.reports.sam-audit', compact('stats'));
    }

    public function download(Request $request)
    {
        $validated = $request->validate([
            'activity_from' => 'required|date|before_or_equal:today',
            'include_removed' => 'nullable|boolean',
        ]);
        $organizationId = $this->orgId();
        $organization = Organization::findOrFail($organizationId);
        $activityFrom = Carbon::parse($validated['activity_from'])->startOfDay();
        $includeRemoved = $request->boolean('include_removed');
        $generatedAt = now();
        $tempRoot = storage_path('app/temp');
        File::ensureDirectoryExists($tempRoot);
        $identifier = (string) Str::uuid();
        $directory = $tempRoot.DIRECTORY_SEPARATOR.'sam-audit-'.$identifier;
        $zipPath = $tempRoot.DIRECTORY_SEPARATOR.'sam-audit-'.$identifier.'.zip';
        File::ensureDirectoryExists($directory);

        try {
            $this->writeSummary($directory, $organization, $activityFrom, $includeRemoved, $generatedAt);
            $this->writeCatalog($directory, $organizationId);
            $this->writeCompliance($directory, $organizationId);
            $this->writeLicenses($directory, $organizationId);
            $this->writeAssignments($directory, $organizationId);
            $this->writeInstallations($directory, $organizationId, $includeRemoved);
            $this->writeDevices($directory, $organizationId);
            $this->writeRecognitionRules($directory, $organizationId);
            $this->writePolicyExceptions($directory, $organizationId, $activityFrom);
            $this->writeRemediationActions($directory, $organizationId, $activityFrom);
            $this->writeRenewalDecisions($directory, $organizationId, $activityFrom);
            $this->writeUsageReviews($directory, $organizationId, $activityFrom);
            $this->writeSoftwareRequests($directory, $organizationId, $activityFrom);
            $this->writeSoftwareProcurement($directory, $organizationId, $activityFrom);
            $this->writeInventoryQuality($directory, $organizationId);
            $this->writePolicyGovernance($directory, $organizationId);
            $this->writeLicenseEvidenceQuality($directory, $organizationId);
            $this->writeRemediationSla($directory, $organizationId);
            $this->writeRenewalSla($directory, $organizationId);
            $this->writeSamHealthScore($directory, $organizationId);
            $this->writeSoftwareRequestSla($directory, $organizationId);
            File::put($directory.DIRECTORY_SEPARATOR.'README.txt', $this->readme($organization, $activityFrom, $includeRemoved, $generatedAt));

            $zip = new ZipArchive();
            if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                throw new \RuntimeException('Unable to create the SAM audit ZIP file.');
            }
            foreach (File::files($directory) as $file) $zip->addFile($file->getPathname(), $file->getFilename());
            $zip->close();
        } catch (\Throwable $exception) {
            File::deleteDirectory($directory);
            File::delete($zipPath);
            throw $exception;
        }

        File::deleteDirectory($directory);
        $filename = 'sam-audit-'.Str::slug($organization->name).'-'.$generatedAt->format('Y-m-d').'.zip';
        return response()->download($zipPath, $filename)->deleteFileAfterSend(true);
    }

    private function writeSummary(string $directory, Organization $organization, Carbon $activityFrom, bool $includeRemoved, Carbon $generatedAt): void
    {
        $organizationId = $organization->id;
        $rows = [
            ['Organization', $organization->name], ['Organization Email', $organization->email],
            ['Generated At', $generatedAt->toIso8601String()], ['Activity From', $activityFrom->toDateString()],
            ['Removed Installations Included', $includeRemoved ? 'Yes' : 'No'],
            ['Catalog Titles', Software::where('organization_id', $organizationId)->count()],
            ['Active Installations', SoftwareDiscovery::where('organization_id', $organizationId)->where('is_installed', true)->count()],
            ['Unknown Installations', SoftwareDiscovery::where('organization_id', $organizationId)->where('is_installed', true)->where('status', 'unknown')->count()],
            ['Enrolled Devices', DeviceAgent::where('organization_id', $organizationId)->count()],
            ['Inventory Data Quality Gaps', DeviceAgent::where('organization_id', $organizationId)->where(fn ($q) => $q->whereNull('asset_id')->orWhereNull('user_id')->orWhereNull('last_seen_at')->orWhere('last_seen_at', '<', now()->subHours(24))->orWhereNotNull('last_error'))->count()],
            ['Policy Governance Gaps', Software::where('organization_id', $organizationId)->where(fn ($q) => $q->where('policy_status', 'unreviewed')->orWhereNull('policy_reviewed_at')->orWhere('policy_reviewed_at', '<', now()->subYear()))->count()],
            ['Active License Seats', SoftwareLicense::where('organization_id', $organizationId)->where('status', 'active')->sum('seats')],
            ['License Evidence Gaps', SoftwareLicense::where('organization_id', $organizationId)->where('status', 'active')->where(fn ($q) => $q->whereNull('evidence_document')->orWhereNull('invoice_number')->orWhereNull('po_number')->orWhereNull('vendor_id'))->count()],
            ['Active Allocations', SoftwareAssignment::where('status', 'active')->whereHas('license', fn ($q) => $q->where('organization_id', $organizationId))->count()],
            ['Open Software Requests', SoftwareRequest::where('organization_id', $organizationId)->whereIn('status', ['pending', 'approved'])->count()],
            ['Overdue Software Requests', SoftwareRequest::where('organization_id', $organizationId)->whereIn('status', ['pending', 'approved'])->whereNotNull('needed_by')->where('needed_by', '<', now()->toDateString())->count()],
            ['Aging Software Requests', SoftwareRequest::where('organization_id', $organizationId)->whereIn('status', ['pending', 'approved'])->where('created_at', '<', now()->subDays(7))->count()],
            ['Software Purchase Order Lines', PurchaseOrderItem::where('item_type', 'software')->whereHas('purchaseOrder', fn ($q) => $q->where('organization_id', $organizationId))->count()],
            ['Active Policy Exceptions', SoftwarePolicyException::where('organization_id', $organizationId)->active()->count()],
            ['Planned Renewal Decisions', SoftwareRenewalDecision::where('organization_id', $organizationId)->where('status', 'planned')->count()],
            ['Usage Optimization Reviews', SoftwareUsageReview::where('organization_id', $organizationId)->count()],
            ['Open Remediation Actions', SoftwareComplianceAction::where('organization_id', $organizationId)->where('status', 'open')->count()],
            ['Overdue Remediation Actions', SoftwareComplianceAction::where('organization_id', $organizationId)->where('status', 'open')->whereNotNull('due_date')->where('due_date', '<', now()->toDateString())->count()],
            ['Overdue Renewal Decisions', SoftwareRenewalDecision::where('organization_id', $organizationId)->where('status', 'planned')->whereNotNull('due_date')->where('due_date', '<', now()->toDateString())->count()],
        ];
        $this->csv($directory, '00-summary.csv', ['Measure', 'Value'], fn ($handle) => collect($rows)->each(fn ($row) => fputcsv($handle, $row)));
    }

    private function writeCatalog(string $directory, int $organizationId): void
    {
        $this->csv($directory, '01-software-catalog.csv', ['Software ID','Name','Publisher','Version','Edition','Type','Category','License Required','License Metric','Criticality','Policy','Policy Notes','Policy Reviewed By','Policy Reviewed At'], function ($handle) use ($organizationId) {
            Software::where('organization_id', $organizationId)->with('policyReviewedBy')->orderBy('id')->chunkById(500, function ($items) use ($handle) {
                foreach ($items as $item) fputcsv($handle, [$item->id,$item->name,$item->vendor,$item->version,$item->edition,$item->software_type,$item->category,$item->license_required?'Yes':'No',$item->license_metric,$item->criticality,$item->policy_status,$item->policy_notes,$item->policyReviewedBy?->name,$item->policy_reviewed_at?->toIso8601String()]);
            });
        });
    }

    private function writeCompliance(string $directory, int $organizationId): void
    {
        $this->csv($directory, '02-compliance-snapshot.csv', ['Software ID','Software','Policy','License Metric','Active Installs','Discovered Users','Discovered Devices','Active Exceptions','Policy Violations','Required Seats','Purchased Seats','Active Allocations','Missing Seats','Allocation Mismatches','Status','Risk Score','Risk Level','Estimated Exposure'], function ($handle) use ($organizationId) {
            Software::where('organization_id', $organizationId)
                ->with(['licenses.activeAssignments','discoveries' => fn ($q) => $q->where('status','mapped')->where('is_installed',true)->with('activePolicyException')])
                ->orderBy('id')->chunkById(100, function ($items) use ($handle) {
                    foreach ($items as $software) {
                        $row = $this->complianceRow($software);
                        if ($row['installs'] === 0 && $row['purchased'] === 0 && $row['allocated'] === 0) continue;
                        fputcsv($handle, [$software->id,$software->name,$software->policy_status,$software->license_metric,$row['installs'],$row['users'],$row['devices'],$row['exceptions'],$row['policy_violations'],$row['required'],$row['purchased'],$row['allocated'],$row['missing'],$row['mismatches'],$row['status'],$row['risk_score'],$row['risk_level'],$row['exposure']]);
                    }
                });
        });
    }

    private function writeLicenses(string $directory, int $organizationId): void
    {
        $this->csv($directory, '03-license-entitlements.csv', ['License ID','Software','Publisher','License Type','Key Reference','Seats','Used Seats','Available Seats','Status','Purchase Date','Expiry Date','Renewal Date','Unit Cost','Total Cost','Supplier','PO Number','Invoice Number','Agreement Number','Evidence Document'], function ($handle) use ($organizationId) {
            SoftwareLicense::where('organization_id', $organizationId)->with(['software','vendor'])->orderBy('id')->chunkById(500, function ($items) use ($handle) {
                foreach ($items as $item) fputcsv($handle, [$item->id,$item->software?->name,$item->software?->vendor,$item->license_type,$this->maskKey($item->license_key),$item->seats,$item->used_seats,$item->available_seats,$item->status,$item->purchase_date?->toDateString(),$item->expiry_date?->toDateString(),$item->renewal_date?->toDateString(),$item->unit_cost,$item->total_cost,$item->vendor?->name,$item->po_number,$item->invoice_number,$item->agreement_number,$item->evidence_document]);
            });
        });
    }

    private function writeAssignments(string $directory, int $organizationId): void
    {
        $this->csv($directory, '04-license-allocations.csv', ['Assignment ID','Software','License ID','Employee Code','Employee','Email','Assigned Date','Returned Date','Status','Assigned By','Notes'], function ($handle) use ($organizationId) {
            SoftwareAssignment::whereHas('license', fn ($q) => $q->where('organization_id', $organizationId))->with(['license.software','user','assignedBy'])->orderBy('id')->chunkById(500, function ($items) use ($handle) {
                foreach ($items as $item) fputcsv($handle, [$item->id,$item->license?->software?->name,$item->software_license_id,$item->user?->employee_id,$item->user?->name,$item->user?->email,$item->assigned_date?->toDateString(),$item->returned_date?->toDateString(),$item->status,$item->assignedBy?->name,$item->notes]);
            });
        });
    }

    private function writeInstallations(string $directory, int $organizationId, bool $includeRemoved): void
    {
        $this->csv($directory, '05-discovered-installations.csv', ['Discovery ID','Mapped Software','Raw Name','Publisher','Version','Device UUID','Hostname','Asset Tag','Employee Code','Employee','Source','Mapping Status','Confidence','Installed','First Seen','Last Seen','Removed At','Last Used','Usage Count','Runtime Minutes'], function ($handle) use ($organizationId, $includeRemoved) {
            SoftwareDiscovery::where('organization_id', $organizationId)->when(! $includeRemoved, fn ($q) => $q->where('is_installed', true))->with(['software','deviceAgent','asset','user'])->orderBy('id')->chunkById(500, function ($items) use ($handle) {
                foreach ($items as $item) fputcsv($handle, [$item->id,$item->software?->name,$item->raw_name,$item->raw_publisher,$item->raw_version,$item->deviceAgent?->device_uuid,$item->deviceAgent?->hostname,$item->asset?->asset_tag,$item->user?->employee_id,$item->user?->name,$item->source,$item->status,$item->confidence_score,$item->is_installed?'Yes':'No',$item->first_seen_at?->toIso8601String(),$item->last_seen_at?->toIso8601String(),$item->uninstalled_at?->toIso8601String(),$item->last_used_date?->toDateString(),$item->usage_count,$item->total_runtime_minutes]);
            });
        });
    }

    private function writeDevices(string $directory, int $organizationId): void
    {
        $this->csv($directory, '06-device-coverage.csv', ['Device ID','Device UUID','Hostname','Serial Number','Asset Tag','Employee Code','Employee','Operating System','OS Version','Architecture','Agent Version','Health','Last Seen','Last Inventory','Credential Status'], function ($handle) use ($organizationId) {
            DeviceAgent::where('organization_id', $organizationId)->with(['asset','user','credential'])->orderBy('id')->chunkById(500, function ($items) use ($handle) {
                foreach ($items as $item) fputcsv($handle, [$item->id,$item->device_uuid,$item->hostname,$item->serial_number,$item->asset?->asset_tag,$item->user?->employee_id,$item->user?->name,$item->os_name,$item->os_version,$item->architecture,$item->agent_version,$item->health_status,$item->last_seen_at?->toIso8601String(),$item->last_inventory_at?->toIso8601String(),$item->credential?->is_active?'Active':($item->credential?'Revoked':'Pending')]);
            });
        });
    }

    private function writeRecognitionRules(string $directory, int $organizationId): void
    {
        $this->csv($directory, '07-recognition-rules.csv', ['Rule ID','Software','Name Pattern','Publisher Pattern','Confidence','Approved By','Created At'], function ($handle) use ($organizationId) {
            SoftwareRecognitionRule::where('organization_id', $organizationId)->with(['software','approvedBy'])->orderBy('id')->chunkById(500, function ($items) use ($handle) {
                foreach ($items as $item) fputcsv($handle, [$item->id,$item->software?->name,$item->raw_name_pattern,$item->raw_publisher_pattern,$item->confidence_score,$item->approvedBy?->name,$item->created_at->toIso8601String()]);
            });
        });
    }

    private function writePolicyExceptions(string $directory, int $organizationId, Carbon $activityFrom): void
    {
        $this->csv($directory, '08-policy-exceptions.csv', ['Exception ID','Software','Discovery ID','Employee Code','Employee','Asset Tag','Valid From','Expires At','Status','Reason','Conditions','Approved By','Revoked By','Revoked At','Created At'], function ($handle) use ($organizationId, $activityFrom) {
            SoftwarePolicyException::where('organization_id', $organizationId)->where(fn ($q) => $q->where('created_at','>=',$activityFrom)->orWhere('expires_at','>=',today()))->with(['software','user','asset','approvedBy','revokedBy'])->orderBy('id')->chunkById(500, function ($items) use ($handle) {
                foreach ($items as $item) fputcsv($handle, [$item->id,$item->software?->name,$item->software_discovery_id,$item->user?->employee_id,$item->user?->name,$item->asset?->asset_tag,$item->valid_from->toDateString(),$item->expires_at->toDateString(),$item->status_label,$item->reason,$item->conditions,$item->approvedBy?->name,$item->revokedBy?->name,$item->revoked_at?->toIso8601String(),$item->created_at->toIso8601String()]);
            });
        });
    }

    private function writeRemediationActions(string $directory, int $organizationId, Carbon $activityFrom): void
    {
        $this->csv($directory, '09-remediation-actions.csv', ['Action ID','Software','Discovery ID','Employee Code','Employee','Asset Tag','Action','Status','Quantity','Due Date','Owner','Created By','Completed At','Notes','Created At'], function ($handle) use ($organizationId, $activityFrom) {
            SoftwareComplianceAction::where('organization_id', $organizationId)->where(fn ($q) => $q->where('created_at','>=',$activityFrom)->orWhere('status','open'))->with(['software','user','asset','owner','createdBy'])->orderBy('id')->chunkById(500, function ($items) use ($handle) {
                foreach ($items as $item) fputcsv($handle, [$item->id,$item->software?->name,$item->software_discovery_id,$item->user?->employee_id,$item->user?->name,$item->asset?->asset_tag,$item->action_type_label,$item->status,$item->quantity,$item->due_date?->toDateString(),$item->owner?->name,$item->createdBy?->name,$item->completed_at?->toIso8601String(),$item->notes,$item->created_at->toIso8601String()]);
            });
        });
    }

    private function writeRenewalDecisions(string $directory, int $organizationId, Carbon $activityFrom): void
    {
        $headers = ['Decision ID','Software','Publisher','License ID','License Type','Current Seats','Used Seats','Decision','Status','Target Seats','Projected Cost','Due Date','Owner','Rationale','Actual Seats','Actual Cost','New Expiry Date','Next Renewal Date','Completion Notes','Created By','Completed By','Completed At','Created At'];

        $this->csv($directory, '10-renewal-decisions.csv', $headers, function ($handle) use ($organizationId, $activityFrom) {
            SoftwareRenewalDecision::where('organization_id', $organizationId)
                ->where(fn ($q) => $q->where('created_at', '>=', $activityFrom)->orWhere('status', 'planned'))
                ->with(['license.software', 'owner', 'createdBy', 'completedBy'])
                ->orderBy('id')
                ->chunkById(500, function ($items) use ($handle) {
                    foreach ($items as $item) {
                        $license = $item->license;
                        fputcsv($handle, [
                            $item->id,
                            $license?->software?->name,
                            $license?->software?->vendor,
                            $item->software_license_id,
                            $license?->license_type_label,
                            $license?->seats,
                            $license?->used_seats,
                            $item->decision_label,
                            $item->status,
                            $item->target_seats,
                            $item->projected_cost,
                            $item->due_date?->toDateString(),
                            $item->owner?->name,
                            $item->rationale,
                            $item->actual_seats,
                            $item->actual_cost,
                            $item->new_expiry_date?->toDateString(),
                            $item->new_renewal_date?->toDateString(),
                            $item->completion_notes,
                            $item->createdBy?->name,
                            $item->completedBy?->name,
                            $item->completed_at?->toIso8601String(),
                            $item->created_at->toIso8601String(),
                        ]);
                    }
                });
        });
    }

    private function writeUsageReviews(string $directory, int $organizationId, Carbon $activityFrom): void
    {
        $headers = ['Review ID','Software','Publisher','Assignment ID','Discovery ID','Employee Code','Employee','Email','Status','Inactivity Days','Last Used Date','Estimated Annual Savings','Due Date','Owner','Created By','Decided By','Decided At','Review Notes','Decision Notes','Created At'];

        $this->csv($directory, '11-usage-optimization-reviews.csv', $headers, function ($handle) use ($organizationId, $activityFrom) {
            SoftwareUsageReview::where('organization_id', $organizationId)
                ->where(fn ($q) => $q->where('created_at', '>=', $activityFrom)->orWhere('status', 'pending_user'))
                ->with(['assignment.user', 'assignment.license.software', 'owner', 'createdBy', 'decidedBy'])
                ->orderBy('id')
                ->chunkById(500, function ($items) use ($handle) {
                    foreach ($items as $item) {
                        $assignment = $item->assignment;
                        $software = $assignment?->license?->software;
                        $user = $assignment?->user;
                        fputcsv($handle, [
                            $item->id,
                            $software?->name,
                            $software?->vendor,
                            $item->software_assignment_id,
                            $item->software_discovery_id,
                            $user?->employee_id,
                            $user?->name,
                            $user?->email,
                            $item->status_label,
                            $item->inactivity_days,
                            $item->last_used_date?->toDateString(),
                            $item->estimated_annual_savings,
                            $item->due_date?->toDateString(),
                            $item->owner?->name,
                            $item->createdBy?->name,
                            $item->decidedBy?->name,
                            $item->decided_at?->toIso8601String(),
                            $item->notes,
                            $item->decision_notes,
                            $item->created_at->toIso8601String(),
                        ]);
                    }
                });
        });
    }

    private function writeSoftwareRequests(string $directory, int $organizationId, Carbon $activityFrom): void
    {
        $headers = ['Request ID','Employee Code','Employee','Email','Department','Software','Publisher','Status','Urgency','Needed By','Business Justification','Reviewed By','Reviewed At','Review Notes','License ID','Assignment ID','Purchase Order','Fulfilled By','Fulfilled At','Created At'];

        $this->csv($directory, '12-software-requests.csv', $headers, function ($handle) use ($organizationId, $activityFrom) {
            SoftwareRequest::where('organization_id', $organizationId)
                ->where(fn ($q) => $q->where('created_at', '>=', $activityFrom)->orWhereIn('status', ['pending', 'approved']))
                ->with(['requester.department', 'software', 'reviewer', 'license', 'assignment', 'purchaseOrderItem.purchaseOrder', 'fulfiller'])
                ->orderBy('id')
                ->chunkById(500, function ($items) use ($handle) {
                    foreach ($items as $item) {
                        fputcsv($handle, [
                            $item->id,
                            $item->requester?->employee_id,
                            $item->requester?->name,
                            $item->requester?->email,
                            $item->requester?->department?->name,
                            $item->software?->name,
                            $item->software?->vendor,
                            $item->status_label,
                            $item->urgency,
                            $item->needed_by?->toDateString(),
                            $item->business_justification,
                            $item->reviewer?->name,
                            $item->reviewed_at?->toIso8601String(),
                            $item->review_notes,
                            $item->software_license_id,
                            $item->software_assignment_id,
                            $item->purchaseOrderItem?->purchaseOrder?->po_number,
                            $item->fulfiller?->name,
                            $item->fulfilled_at?->toIso8601String(),
                            $item->created_at->toIso8601String(),
                        ]);
                    }
                });
        });
    }

    private function writeSoftwareProcurement(string $directory, int $organizationId, Carbon $activityFrom): void
    {
        $headers = ['PO Item ID','PO Number','PO Status','Supplier','Order Date','Expected Delivery','Actual Delivery','Software','Publisher','Item Name','License Type','Subscription Period','Ordered Quantity','Received Quantity','Pending Quantity','Unit Price','Total Price','Linked Requests','Created License Seats','Receipt Numbers','Invoice Numbers','Created At'];

        $this->csv($directory, '13-software-procurement.csv', $headers, function ($handle) use ($organizationId, $activityFrom) {
            PurchaseOrderItem::where('item_type', 'software')
                ->whereHas('purchaseOrder', fn ($query) => $query
                    ->where('organization_id', $organizationId)
                    ->where(fn ($q) => $q->where('created_at', '>=', $activityFrom)->orWhereNotIn('status', ['received', 'cancelled'])))
                ->with(['purchaseOrder.supplier', 'software', 'softwareRequests', 'softwareLicenses', 'receiptItems.goodsReceipt'])
                ->orderBy('id')
                ->chunkById(500, function ($items) use ($handle) {
                    foreach ($items as $item) {
                        $order = $item->purchaseOrder;
                        fputcsv($handle, [
                            $item->id,
                            $order?->po_number,
                            $order?->status,
                            $order?->supplier?->name,
                            $order?->order_date?->toDateString(),
                            $order?->expected_delivery_date?->toDateString(),
                            $order?->actual_delivery_date?->toDateString(),
                            $item->software?->name,
                            $item->software?->vendor,
                            $item->item_name,
                            $item->license_type,
                            $item->subscription_period,
                            $item->quantity,
                            $item->received_quantity,
                            $item->pending_quantity,
                            $item->unit_price,
                            $item->total_price,
                            $item->softwareRequests->pluck('id')->implode(', '),
                            $item->softwareLicenses->sum('seats'),
                            $item->receiptItems->pluck('goodsReceipt.receipt_number')->filter()->unique()->implode(', '),
                            $item->receiptItems->pluck('goodsReceipt.invoice_number')->filter()->unique()->implode(', '),
                            $item->created_at->toIso8601String(),
                        ]);
                    }
                });
        });
    }

    private function writeInventoryQuality(string $directory, int $organizationId): void
    {
        $headers = ['Device ID','Device UUID','Hostname','Serial Number','Asset Tag','Employee Code','Employee','Health','Agent Version','Last Seen','Last Inventory','Last Error','Last Error At','Quality Issues'];

        $this->csv($directory, '14-inventory-data-quality.csv', $headers, function ($handle) use ($organizationId) {
            DeviceAgent::where('organization_id', $organizationId)
                ->where(fn ($query) => $query
                    ->whereNull('asset_id')
                    ->orWhereNull('user_id')
                    ->orWhereNull('last_seen_at')
                    ->orWhere('last_seen_at', '<', now()->subHours(24))
                    ->orWhereNotNull('last_error'))
                ->with(['asset', 'user'])
                ->orderBy('hostname')
                ->chunkById(500, function ($items) use ($handle) {
                    foreach ($items as $item) {
                        $issues = collect([
                            $item->asset_id ? null : 'No asset link',
                            $item->user_id ? null : 'No employee link',
                            $item->last_seen_at ? null : 'Never seen',
                            $item->last_seen_at && $item->last_seen_at->lt(now()->subDays(7)) ? 'Offline over 7 days' : null,
                            $item->last_seen_at && $item->last_seen_at->between(now()->subDays(7), now()->subHours(24)) ? 'Stale over 24 hours' : null,
                            $item->last_error ? 'Agent error' : null,
                        ])->filter()->implode('; ');

                        fputcsv($handle, [
                            $item->id,
                            $item->device_uuid,
                            $item->hostname,
                            $item->serial_number,
                            $item->asset?->asset_tag,
                            $item->user?->employee_id,
                            $item->user?->name,
                            $item->health_status,
                            $item->agent_version,
                            $item->last_seen_at?->toIso8601String(),
                            $item->last_inventory_at?->toIso8601String(),
                            $item->last_error,
                            $item->last_error_at?->toIso8601String(),
                            $issues,
                        ]);
                    }
                });
        });
    }

    private function writePolicyGovernance(string $directory, int $organizationId): void
    {
        $headers = ['Software ID','Software','Publisher','Criticality','Policy Status','Policy Notes','Policy Reviewed By','Policy Reviewed At','Active Installs','Active Exceptions','Prohibited Or Restricted Violations','Open Uninstall Tasks','Governance Issues'];

        $this->csv($directory, '15-policy-governance.csv', $headers, function ($handle) use ($organizationId) {
            Software::where('organization_id', $organizationId)
                ->with([
                    'policyReviewedBy',
                    'discoveries' => fn ($query) => $query->where('status', 'mapped')->where('is_installed', true)->with('activePolicyException'),
                ])
                ->withCount(['complianceActions as open_uninstall_tasks_count' => fn ($query) => $query->where('action_type', 'uninstall_reclaim')->where('status', 'open')])
                ->orderBy('id')
                ->chunkById(500, function ($items) use ($handle) {
                    foreach ($items as $item) {
                        $activeInstalls = $item->discoveries->count();
                        $activeExceptions = $item->discoveries->filter(fn ($discovery) => $discovery->activePolicyException)->count();
                        $violations = in_array($item->policy_status, ['restricted', 'prohibited'], true)
                            ? max(0, $activeInstalls - $activeExceptions)
                            : 0;
                        $issues = collect([
                            $item->policy_status === 'unreviewed' ? 'Unreviewed policy' : null,
                            $item->policy_reviewed_at ? null : 'Never reviewed',
                            $item->policy_reviewed_at && $item->policy_reviewed_at->lt(now()->subYear()) ? 'Reviewed over 12 months ago' : null,
                            $violations > 0 ? 'Policy violations detected' : null,
                        ])->filter()->implode('; ');

                        if ($issues === '' && $activeInstalls === 0 && $activeExceptions === 0) {
                            continue;
                        }

                        fputcsv($handle, [
                            $item->id,
                            $item->name,
                            $item->vendor,
                            $item->criticality,
                            $item->policy_status_label,
                            $item->policy_notes,
                            $item->policyReviewedBy?->name,
                            $item->policy_reviewed_at?->toIso8601String(),
                            $activeInstalls,
                            $activeExceptions,
                            $violations,
                            $item->open_uninstall_tasks_count,
                            $issues,
                        ]);
                    }
                });
        });
    }

    private function writeLicenseEvidenceQuality(string $directory, int $organizationId): void
    {
        $headers = ['License ID','Software','Publisher','License Type','Seats','Used Seats','Available Seats','Supplier','PO Number','Invoice Number','Agreement Number','Purchase Date','Expiry Date','Renewal Date','Unit Cost','Total Cost','Evidence Document','Evidence Issues'];

        $this->csv($directory, '16-license-evidence-quality.csv', $headers, function ($handle) use ($organizationId) {
            SoftwareLicense::where('organization_id', $organizationId)
                ->where('status', 'active')
                ->where(function ($query) {
                    $query->whereNull('evidence_document')
                        ->orWhereNull('invoice_number')
                        ->orWhereNull('po_number')
                        ->orWhereNull('vendor_id')
                        ->orWhereNull('purchase_date')
                        ->orWhere(function ($costQuery) {
                            $costQuery->whereNull('purchase_price')->whereNull('unit_cost');
                        });
                })
                ->with(['software', 'vendor'])
                ->orderBy('id')
                ->chunkById(500, function ($items) use ($handle) {
                    foreach ($items as $item) {
                        $issues = collect([
                            $item->vendor_id ? null : 'Missing supplier',
                            $item->po_number ? null : 'Missing PO number',
                            $item->invoice_number ? null : 'Missing invoice number',
                            $item->agreement_number ? null : 'Missing agreement number',
                            $item->purchase_date ? null : 'Missing purchase date',
                            ($item->purchase_price || $item->unit_cost) ? null : 'Missing cost',
                            $item->evidence_document ? null : 'Missing evidence document',
                        ])->filter()->implode('; ');

                        fputcsv($handle, [
                            $item->id,
                            $item->software?->name,
                            $item->software?->vendor,
                            $item->license_type_label,
                            $item->seats,
                            $item->used_seats,
                            $item->available_seats,
                            $item->vendor?->name,
                            $item->po_number,
                            $item->invoice_number,
                            $item->agreement_number,
                            $item->purchase_date?->toDateString(),
                            $item->expiry_date?->toDateString(),
                            $item->renewal_date?->toDateString(),
                            $item->unit_cost,
                            $item->total_cost,
                            $item->evidence_document,
                            $issues,
                        ]);
                    }
                });
        });
    }

    private function writeRemediationSla(string $directory, int $organizationId): void
    {
        $headers = ['Action ID','Software','Action','Status','Owner','Due Date','SLA Status','Days Overdue','Quantity','Created By','Created At','Notes'];

        $this->csv($directory, '17-remediation-sla.csv', $headers, function ($handle) use ($organizationId) {
            SoftwareComplianceAction::where('organization_id', $organizationId)
                ->where('status', 'open')
                ->whereNotNull('due_date')
                ->with(['software', 'owner', 'createdBy'])
                ->orderBy('due_date')
                ->chunkById(500, function ($items) use ($handle) {
                    foreach ($items as $item) {
                        $slaStatus = match (true) {
                            $item->due_date->lt(today()) => 'Overdue',
                            $item->due_date->lte(today()->addDays(7)) => 'Due in 7 days',
                            default => 'Scheduled',
                        };
                        $daysOverdue = $item->due_date->lt(today()) ? $item->due_date->diffInDays(today()) : 0;

                        fputcsv($handle, [
                            $item->id,
                            $item->software?->name,
                            $item->action_type_label,
                            $item->status,
                            $item->owner?->name,
                            $item->due_date?->toDateString(),
                            $slaStatus,
                            $daysOverdue,
                            $item->quantity,
                            $item->createdBy?->name,
                            $item->created_at->toIso8601String(),
                            $item->notes,
                        ]);
                    }
                });
        });
    }

    private function writeRenewalSla(string $directory, int $organizationId): void
    {
        $headers = ['Decision ID','Software','License ID','Decision','Status','Owner','Due Date','SLA Status','Days Overdue','License Expiry Date','License Renewal Date','Projected Cost','Target Seats','Created By','Created At','Rationale'];

        $this->csv($directory, '18-renewal-sla.csv', $headers, function ($handle) use ($organizationId) {
            SoftwareRenewalDecision::where('organization_id', $organizationId)
                ->where('status', 'planned')
                ->whereNotNull('due_date')
                ->with(['license.software', 'owner', 'createdBy'])
                ->orderBy('due_date')
                ->chunkById(500, function ($items) use ($handle) {
                    foreach ($items as $item) {
                        $slaStatus = match (true) {
                            $item->due_date->lt(today()) => 'Overdue',
                            $item->due_date->lte(today()->addDays(14)) => 'Due in 14 days',
                            default => 'Scheduled',
                        };
                        $daysOverdue = $item->due_date->lt(today()) ? $item->due_date->diffInDays(today()) : 0;
                        $license = $item->license;

                        fputcsv($handle, [
                            $item->id,
                            $license?->software?->name,
                            $item->software_license_id,
                            $item->decision_label,
                            $item->status,
                            $item->owner?->name,
                            $item->due_date?->toDateString(),
                            $slaStatus,
                            $daysOverdue,
                            $license?->expiry_date?->toDateString(),
                            $license?->renewal_date?->toDateString(),
                            $item->projected_cost,
                            $item->target_seats,
                            $item->createdBy?->name,
                            $item->created_at->toIso8601String(),
                            $item->rationale,
                        ]);
                    }
                });
        });
    }

    private function writeSoftwareRequestSla(string $directory, int $organizationId): void
    {
        $headers = ['Request ID','Employee Code','Employee','Email','Department','Software','Publisher','Status','Urgency','Needed By','SLA Status','Days Open','Days Overdue','Aging Flag','Purchase Order','Created At','Reviewed At','Fulfilled At','SLA Issue'];

        $this->csv($directory, '20-software-request-sla.csv', $headers, function ($handle) use ($organizationId) {
            SoftwareRequest::where('organization_id', $organizationId)
                ->whereIn('status', ['pending', 'approved'])
                ->with(['requester.department', 'software', 'purchaseOrderItem.purchaseOrder'])
                ->orderByRaw('CASE WHEN needed_by IS NOT NULL AND needed_by < ? THEN 0 ELSE 1 END', [now()->toDateString()])
                ->orderBy('needed_by')
                ->orderBy('created_at')
                ->chunkById(500, function ($items) use ($handle) {
                    foreach ($items as $item) {
                        $daysOpen = $item->created_at ? (int) $item->created_at->diffInDays(now()) : 0;
                        $daysOverdue = ($item->needed_by && $item->needed_by->lt(today()))
                            ? (int) $item->needed_by->diffInDays(today())
                            : 0;
                        $issues = collect([
                            $item->is_overdue ? 'Needed-by date missed' : null,
                            $item->needed_by && $item->needed_by->gte(today()) && $item->needed_by->lte(today()->addDays(7)) ? 'Needed within 7 days' : null,
                            $item->is_aging ? 'Open for more than 7 days' : null,
                            $item->purchase_order_item_id && $item->status === 'approved' ? 'Awaiting allocation after procurement link' : null,
                        ])->filter()->implode('; ');

                        fputcsv($handle, [
                            $item->id,
                            $item->requester?->employee_id,
                            $item->requester?->name,
                            $item->requester?->email,
                            $item->requester?->department?->name,
                            $item->software?->name,
                            $item->software?->vendor,
                            $item->status_label,
                            $item->urgency,
                            $item->needed_by?->toDateString(),
                            $item->sla_label,
                            $daysOpen,
                            $daysOverdue,
                            $item->is_aging ? 'Yes' : 'No',
                            $item->purchaseOrderItem?->purchaseOrder?->po_number,
                            $item->created_at?->toIso8601String(),
                            $item->reviewed_at?->toIso8601String(),
                            $item->fulfilled_at?->toIso8601String(),
                            $issues,
                        ]);
                    }
                });
        });
    }

    private function writeSamHealthScore(string $directory, int $organizationId): void
    {
        $installed = SoftwareDiscovery::where('organization_id', $organizationId)->where('is_installed', true)->count();
        $mapped = SoftwareDiscovery::where('organization_id', $organizationId)->where('is_installed', true)->where('status', 'mapped')->count();
        $devices = DeviceAgent::where('organization_id', $organizationId)->count();
        $healthyDevices = DeviceAgent::where('organization_id', $organizationId)->where('last_seen_at', '>=', now()->subHours(24))->count();
        $riskRows = Software::where('organization_id', $organizationId)
            ->with(['licenses.activeAssignments', 'discoveries' => fn ($q) => $q->where('status', 'mapped')->where('is_installed', true)->with('activePolicyException')])
            ->get()
            ->map(fn ($software) => $this->complianceRow($software))
            ->filter(fn ($row) => $row['installs'] > 0 && $row['risk_score'] > 0)
            ->count();
        $stats = [
            'healthy_percent' => $devices > 0 ? (int) round(($healthyDevices / $devices) * 100) : 0,
            'normalized_percent' => $installed > 0 ? (int) round(($mapped / $installed) * 100) : 0,
            'risk_rows' => $riskRows,
            'overdue_actions' => SoftwareComplianceAction::where('organization_id', $organizationId)->where('status', 'open')->whereNotNull('due_date')->where('due_date', '<', now()->toDateString())->count(),
            'overdue_renewals' => SoftwareRenewalDecision::where('organization_id', $organizationId)->where('status', 'planned')->whereNotNull('due_date')->where('due_date', '<', now()->toDateString())->count(),
            'overdue_software_requests' => SoftwareRequest::where('organization_id', $organizationId)->whereIn('status', ['pending', 'approved'])->whereNotNull('needed_by')->where('needed_by', '<', now()->toDateString())->count(),
            'aging_software_requests' => SoftwareRequest::where('organization_id', $organizationId)->whereIn('status', ['pending', 'approved'])->where('created_at', '<', now()->subDays(7))->count(),
            'policy_gaps' => Software::where('organization_id', $organizationId)->where(fn ($q) => $q->where('policy_status', 'unreviewed')->orWhereNull('policy_reviewed_at')->orWhere('policy_reviewed_at', '<', now()->subYear()))->count(),
            'license_evidence_gaps' => SoftwareLicense::where('organization_id', $organizationId)->where('status', 'active')->where(fn ($q) => $q->whereNull('evidence_document')->orWhereNull('invoice_number')->orWhereNull('po_number')->orWhereNull('vendor_id'))->count(),
            'inventory_gaps' => DeviceAgent::where('organization_id', $organizationId)->where(fn ($q) => $q->whereNull('asset_id')->orWhereNull('user_id')->orWhereNull('last_seen_at')->orWhere('last_seen_at', '<', now()->subHours(24))->orWhereNotNull('last_error'))->count(),
        ];
        $penalties = [
            'Inventory coverage' => $stats['healthy_percent'] >= 80 ? 0 : ($stats['healthy_percent'] >= 60 ? 8 : 15),
            'Normalization backlog' => $stats['normalized_percent'] >= 85 ? 0 : ($stats['normalized_percent'] >= 65 ? 8 : 15),
            'Compliance risk' => min(20, $stats['risk_rows'] * 3),
            'Overdue remediation' => min(15, $stats['overdue_actions'] * 5),
            'Overdue renewals' => min(10, $stats['overdue_renewals'] * 4),
            'Demand SLA' => min(10, ($stats['overdue_software_requests'] * 4) + ($stats['aging_software_requests'] * 2)),
            'Policy governance' => min(12, $stats['policy_gaps'] * 2),
            'License evidence' => min(10, $stats['license_evidence_gaps'] * 2),
            'Inventory data quality' => min(10, $stats['inventory_gaps'] * 2),
        ];
        $score = max(0, 100 - array_sum($penalties));
        $label = $score >= 80 ? 'Healthy' : ($score >= 60 ? 'Needs Attention' : 'High Risk');

        $this->csv($directory, '19-sam-health-score.csv', ['Measure', 'Value'], function ($handle) use ($score, $label, $stats, $penalties) {
            fputcsv($handle, ['SAM Health Score', $score]);
            fputcsv($handle, ['SAM Health Label', $label]);
            foreach ($stats as $measure => $value) fputcsv($handle, [str_replace('_', ' ', ucfirst($measure)), $value]);
            foreach ($penalties as $measure => $value) fputcsv($handle, [$measure.' penalty', $value]);
        });
    }


    private function csv(string $directory, string $filename, array $headers, Closure $writer): void
    {
        $handle = fopen($directory.DIRECTORY_SEPARATOR.$filename, 'wb');
        if (! $handle) throw new \RuntimeException('Unable to create '.$filename.'.');
        try {
            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, $headers);
            $writer($handle);
        } finally {
            fclose($handle);
        }
    }

    private function complianceRow(Software $software): array
    {
        $discoveries = $software->discoveries;
        $licenses = $software->licenses;
        $validLicenses = $licenses->where('status','active')->reject(fn ($license) => $license->is_expired);
        $userIds = $discoveries->pluck('user_id')->filter()->unique();
        $assetIds = $discoveries->pluck('asset_id')->filter()->unique();
        $assignments = $validLicenses->flatMap(fn ($license) => $license->activeAssignments);
        $allocatedUserIds = $assignments->pluck('user_id')->filter()->unique();
        $required = ! $software->license_required ? 0 : match ($software->license_metric) {
            'per_device' => $assetIds->count() ?: $discoveries->count(),
            'site', 'enterprise' => $discoveries->isNotEmpty() ? 1 : 0,
            default => $userIds->count() ?: $discoveries->count(),
        };
        $purchased = (int) $validLicenses->sum('seats');
        $missing = max(0, $required - $purchased);
        $mismatches = $userIds->diff($allocatedUserIds)->count();
        $exceptions = $discoveries->filter(fn ($item) => $item->activePolicyException)->count();
        $policyViolations = in_array($software->policy_status, ['restricted','prohibited'], true) ? max(0, $discoveries->count() - $exceptions) : 0;
        $expired = $licenses->where('status','active')->filter(fn ($license) => $license->is_expired)->count();
        $status = $this->complianceStatus($software, $discoveries->count(), $required, $purchased, $expired, $mismatches, $policyViolations);
        $seatCount = (int) $validLicenses->sum('seats');
        $averageCost = $seatCount > 0 ? (float) $validLicenses->sum(fn ($license) => $license->total_cost) / $seatCount : 0;
        $exposure = $missing * $averageCost;
        $risk = $this->riskScore($software, $required, $missing, $exposure, $status);
        return ['installs'=>$discoveries->count(),'users'=>$userIds->count(),'devices'=>$assetIds->count(),'exceptions'=>$exceptions,'policy_violations'=>$policyViolations,'required'=>$required,'purchased'=>$purchased,'allocated'=>$assignments->count(),'missing'=>$missing,'mismatches'=>$mismatches,'status'=>$status,'risk_score'=>$risk,'risk_level'=>$risk>=70?'high':($risk>=35?'medium':'low'),'exposure'=>$exposure];
    }

    private function complianceStatus(Software $software, int $installs, int $required, int $purchased, int $expired, int $mismatches, int $policyViolations): string
    {
        if ($policyViolations > 0 && in_array($software->policy_status, ['restricted','prohibited'], true)) return $software->policy_status;
        if (! $software->license_required) return 'free';
        if ($installs > 0 && $purchased === 0) return $expired > 0 ? 'expired' : 'unauthorized';
        if ($required > $purchased) return 'under_licensed';
        if ($mismatches > 0) return 'allocation_mismatch';
        if ($purchased > $required && $required > 0) return 'over_licensed';
        return 'compliant';
    }

    private function riskScore(Software $software, int $required, int $missing, float $exposure, string $status): int
    {
        if ($status === 'prohibited') return 100;
        if ($status === 'restricted') return 85;
        if (in_array($status, ['free','compliant','over_licensed'], true)) return 0;
        $percentage = $required > 0 ? ($missing / $required) * 100 : 100;
        $shortage = $percentage <= 0 ? 0 : ($percentage <= 10 ? 20 : ($percentage <= 30 ? 50 : ($percentage <= 60 ? 80 : 100)));
        $criticality = match ($software->criticality) {'critical'=>100,'high'=>75,'low'=>25,default=>50};
        $cost = $exposure <= 0 ? 0 : ($exposure <= 100000 ? 25 : ($exposure <= 1000000 ? 50 : ($exposure <= 5000000 ? 75 : 100)));
        return (int) round(($shortage * .4) + ($criticality * .3) + ($cost * .3));
    }

    private function maskKey(?string $key): string
    {
        if (! filled($key)) return '';
        return str_repeat('*', min(12, max(4, strlen($key) - 4))).substr($key, -4);
    }

    private function readme(Organization $organization, Carbon $activityFrom, bool $includeRemoved, Carbon $generatedAt): string
    {
        return "OPSBRIDGE SAM AUDIT PACK\r\n\r\nOrganization: {$organization->name}\r\nGenerated: {$generatedAt->toIso8601String()}\r\nActivity period starts: {$activityFrom->toDateString()}\r\nRemoved installations included: ".($includeRemoved?'Yes':'No')."\r\n\r\nThe SAM health score summarizes inventory coverage, normalization, compliance risk, SLA, demand SLA, policy, evidence, and data quality signals. The compliance snapshot is point-in-time. Policy exceptions include active records and records created during the selected activity period. Policy governance highlights unreviewed, stale, restricted, and prohibited titles. License evidence quality highlights active entitlements missing supplier, invoice, PO, cost, or document proof. Remediation, renewal, and software request SLA files highlight open/planned work that is overdue, aging, or scheduled. Remediation, renewal, usage optimization, software request, and software procurement decisions include open/planned items and items created during that period. Inventory data quality highlights endpoint records that may affect SAM confidence. License keys are masked; source evidence remains controlled by the portal.\r\n";
    }
}
