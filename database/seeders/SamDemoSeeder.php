<?php

namespace Database\Seeders;

use App\Models\Asset;
use App\Models\Organization;
use App\Models\Software;
use App\Models\SoftwareAssignment;
use App\Models\SoftwareDiscovery;
use App\Models\SoftwareLicense;
use App\Models\User;
use Illuminate\Database\Seeder;

class SamDemoSeeder extends Seeder
{
    public function run(): void
    {
        $organizationId = (int) env('SAM_DEMO_ORGANIZATION_ID', 1);
        if (! Organization::whereKey($organizationId)->exists()) {
            $this->command?->error("Organization {$organizationId} does not exist.");
            return;
        }

        $admin = User::where('organization_id', $organizationId)->where('role', 'admin')->first();
        $staff = User::where('organization_id', $organizationId)->where('role', 'staff')->orderBy('id')->get();
        $assets = Asset::where('organization_id', $organizationId)->orderBy('id')->get();

        if ($staff->count() < 2 || $assets->count() < 2) {
            $this->command?->warn('Software and licenses will be created. Some demo assignments and discoveries need two staff users and two assets.');
        }

        [$lokesh, $ananya] = [$staff->get(0), $staff->get(1)];
        [$laptopOne, $laptopTwo] = [$assets->get(0), $assets->get(1)];

        $m365 = $this->software($organizationId, [
            'name' => 'Microsoft 365 Apps',
            'vendor' => 'Microsoft',
            'edition' => 'Business Standard',
            'category' => 'productivity',
            'software_type' => 'saas',
            'license_required' => true,
            'criticality' => 'high',
            'license_metric' => 'per_user',
            'trusted_publisher' => true,
        ]);
        $m365License = $this->license($organizationId, $m365, [
            'license_type' => 'subscription',
            'purchase_batch' => 'SAM-DEMO-M365',
            'seats' => 3,
            'purchase_date' => now()->subMonths(2)->toDateString(),
            'expiry_date' => now()->addMonths(10)->toDateString(),
            'renewal_date' => now()->addMonths(9)->toDateString(),
            'unit_cost' => 950,
            'invoice_number' => 'INV-SAM-M365',
        ]);
        $this->assign($m365License, $lokesh, $admin, 'M365 demo assignment');
        $this->assign($m365License, $ananya, $admin, 'M365 demo assignment');
        $this->discover($organizationId, $m365, $laptopOne, $lokesh, 'Microsoft 365 Apps for enterprise', 'Microsoft Corporation', '16.0', $admin);
        $this->discover($organizationId, $m365, $laptopTwo, $ananya, 'Microsoft 365 Apps for enterprise', 'Microsoft Corporation', '16.0', $admin);

        $adobe = $this->software($organizationId, [
            'name' => 'Adobe Creative Cloud',
            'vendor' => 'Adobe',
            'edition' => 'All Apps',
            'category' => 'design',
            'software_type' => 'commercial',
            'license_required' => true,
            'criticality' => 'high',
            'license_metric' => 'per_user',
            'trusted_publisher' => true,
        ]);
        $adobeLicense = $this->license($organizationId, $adobe, [
            'license_type' => 'subscription',
            'purchase_batch' => 'SAM-DEMO-ADOBE',
            'seats' => 1,
            'purchase_date' => now()->subMonth()->toDateString(),
            'expiry_date' => now()->addMonths(11)->toDateString(),
            'renewal_date' => now()->addMonths(10)->toDateString(),
            'unit_cost' => 4200,
            'invoice_number' => 'INV-SAM-ADOBE',
        ]);
        $this->assign($adobeLicense, $lokesh, $admin, 'Adobe demo assignment');
        $this->discover($organizationId, $adobe, $laptopOne, $lokesh, 'Adobe Photoshop 2025', 'Adobe Inc.', '26.0', $admin);
        $this->discover($organizationId, $adobe, $laptopTwo, $ananya, 'Adobe Photoshop 2025', 'Adobe Inc.', '26.0', $admin);

        $zoom = $this->software($organizationId, [
            'name' => 'Zoom Workplace',
            'vendor' => 'Zoom',
            'edition' => 'Business',
            'category' => 'communication',
            'software_type' => 'saas',
            'license_required' => true,
            'criticality' => 'medium',
            'license_metric' => 'per_user',
            'trusted_publisher' => true,
        ]);
        $zoomLicense = $this->license($organizationId, $zoom, [
            'license_type' => 'subscription',
            'purchase_batch' => 'SAM-DEMO-ZOOM',
            'seats' => 2,
            'purchase_date' => now()->subWeeks(3)->toDateString(),
            'expiry_date' => now()->addMonths(12)->toDateString(),
            'renewal_date' => now()->addMonths(11)->toDateString(),
            'unit_cost' => 1200,
            'invoice_number' => 'INV-SAM-ZOOM',
        ]);
        $this->assign($zoomLicense, $lokesh, $admin, 'Zoom demo assignment');
        $this->discover($organizationId, $zoom, $laptopOne, $lokesh, 'Zoom Workplace', 'Zoom Video Communications', '6.1', $admin);
        $this->discover($organizationId, $zoom, $laptopTwo, $ananya, 'Zoom Workplace', 'Zoom Video Communications', '6.1', $admin);

        $autocad = $this->software($organizationId, [
            'name' => 'AutoCAD',
            'vendor' => 'Autodesk',
            'edition' => 'Professional',
            'category' => 'design',
            'software_type' => 'commercial',
            'license_required' => true,
            'criticality' => 'critical',
            'license_metric' => 'per_user',
            'trusted_publisher' => true,
        ]);
        $this->discover($organizationId, $autocad, $laptopTwo, $ananya, 'Autodesk AutoCAD 2025', 'Autodesk', '2025', $admin);

        $winzip = $this->software($organizationId, [
            'name' => 'WinZip Pro',
            'vendor' => 'Corel',
            'edition' => 'Pro',
            'category' => 'productivity',
            'software_type' => 'commercial',
            'license_required' => true,
            'criticality' => 'medium',
            'license_metric' => 'per_user',
            'trusted_publisher' => false,
        ]);
        $this->license($organizationId, $winzip, [
            'license_type' => 'per_seat',
            'purchase_batch' => 'SAM-DEMO-WINZIP',
            'seats' => 1,
            'purchase_date' => now()->subYear()->toDateString(),
            'expiry_date' => now()->subMonth()->toDateString(),
            'renewal_date' => now()->subMonth()->toDateString(),
            'unit_cost' => 3200,
            'invoice_number' => 'INV-SAM-WINZIP',
        ]);
        $this->discover($organizationId, $winzip, $laptopOne, $lokesh, 'WinZip Pro', 'Corel Corporation', '28.0', $admin);

        $slack = $this->software($organizationId, [
            'name' => 'Slack Free',
            'vendor' => 'Salesforce',
            'edition' => 'Free',
            'category' => 'communication',
            'software_type' => 'freeware',
            'license_required' => false,
            'criticality' => 'low',
            'license_metric' => 'per_user',
            'trusted_publisher' => true,
        ]);
        $this->discover($organizationId, $slack, $laptopOne, $lokesh, 'Slack', 'Slack Technologies', '4.39', $admin);
        $this->discover($organizationId, $slack, $laptopTwo, $ananya, 'Slack', 'Slack Technologies', '4.39', $admin);

        if ($laptopTwo && $ananya) {
            SoftwareDiscovery::updateOrCreate([
                'organization_id' => $organizationId,
                'asset_id' => $laptopTwo->id,
                'user_id' => $ananya->id,
                'software_id' => null,
                'raw_name' => 'Unknown Screen Recorder Pro',
            ], [
                'raw_publisher' => 'Unknown Publisher',
                'raw_version' => '3.4',
                'source' => 'csv',
                'status' => 'unknown',
                'last_used_date' => now()->subDays(1)->toDateString(),
                'usage_count' => 8,
                'total_runtime_minutes' => 240,
            ]);
        }

        $this->command?->info('SAM demo software, licenses, assignments, and discovery records created.');
    }

    private function software(int $organizationId, array $data): Software
    {
        return Software::updateOrCreate([
            'organization_id' => $organizationId,
            'name' => $data['name'],
        ], $data + ['organization_id' => $organizationId]);
    }

    private function license(int $organizationId, Software $software, array $data): SoftwareLicense
    {
        return SoftwareLicense::updateOrCreate([
            'organization_id' => $organizationId,
            'software_id' => $software->id,
            'purchase_batch' => $data['purchase_batch'],
        ], $data + [
            'organization_id' => $organizationId,
            'software_id' => $software->id,
            'status' => 'active',
        ]);
    }

    private function assign(SoftwareLicense $license, ?User $user, ?User $admin, string $notes): void
    {
        if (! $user) return;

        SoftwareAssignment::updateOrCreate([
            'software_license_id' => $license->id,
            'user_id' => $user->id,
            'status' => 'active',
        ], [
            'assigned_by' => $admin?->id,
            'assigned_date' => now()->subDays(15)->toDateString(),
            'notes' => $notes,
        ]);
    }

    private function discover(int $organizationId, Software $software, ?Asset $asset, ?User $user, string $rawName, string $publisher, string $version, ?User $admin): void
    {
        if (! $asset || ! $user) return;

        SoftwareDiscovery::updateOrCreate([
            'organization_id' => $organizationId,
            'asset_id' => $asset->id,
            'user_id' => $user->id,
            'software_id' => $software->id,
            'raw_name' => $rawName,
        ], [
            'raw_publisher' => $publisher,
            'raw_version' => $version,
            'source' => 'csv',
            'status' => 'mapped',
            'confidence_score' => 96,
            'reviewed_by' => $admin?->id,
            'reviewed_at' => $admin ? now() : null,
            'last_used_date' => now()->subDays(rand(1, 12))->toDateString(),
            'usage_count' => rand(4, 38),
            'total_runtime_minutes' => rand(120, 2600),
        ]);
    }
}
