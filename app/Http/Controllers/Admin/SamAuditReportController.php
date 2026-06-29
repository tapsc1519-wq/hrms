<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DeviceAgent;
use App\Models\Organization;
use App\Models\Software;
use App\Models\SoftwareAssignment;
use App\Models\SoftwareComplianceAction;
use App\Models\SoftwareDiscovery;
use App\Models\SoftwareLicense;
use App\Models\SoftwarePolicyException;
use App\Models\SoftwareRecognitionRule;
use App\Models\SoftwareRenewalDecision;
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
            'license_seats' => SoftwareLicense::where('organization_id', $organizationId)->where('status', 'active')->sum('seats'),
            'renewal_plans' => SoftwareRenewalDecision::where('organization_id', $organizationId)->where('status', 'planned')->count(),
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
            ['Active License Seats', SoftwareLicense::where('organization_id', $organizationId)->where('status', 'active')->sum('seats')],
            ['Active Allocations', SoftwareAssignment::where('status', 'active')->whereHas('license', fn ($q) => $q->where('organization_id', $organizationId))->count()],
            ['Active Policy Exceptions', SoftwarePolicyException::where('organization_id', $organizationId)->active()->count()],
            ['Planned Renewal Decisions', SoftwareRenewalDecision::where('organization_id', $organizationId)->where('status', 'planned')->count()],
            ['Open Remediation Actions', SoftwareComplianceAction::where('organization_id', $organizationId)->where('status', 'open')->count()],
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
        return "OPSBRIDGE SAM AUDIT PACK\r\n\r\nOrganization: {$organization->name}\r\nGenerated: {$generatedAt->toIso8601String()}\r\nActivity period starts: {$activityFrom->toDateString()}\r\nRemoved installations included: ".($includeRemoved?'Yes':'No')."\r\n\r\nThe compliance snapshot is point-in-time. Policy exceptions include active records and records created during the selected activity period. Remediation and renewal decisions include open/planned items and items created during that period. License keys are masked; source evidence remains controlled by the portal.\r\n";
    }
}
