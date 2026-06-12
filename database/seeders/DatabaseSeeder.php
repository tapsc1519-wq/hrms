<?php

namespace Database\Seeders;

use App\Models\Asset;
use App\Models\AssetAssignment;
use App\Models\AssetCategory;
use App\Models\Department;
use App\Models\DepreciationRecord;
use App\Models\Facility;
use App\Models\Location;
use App\Models\MaintenanceRecord;
use App\Models\Organization;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\User;
use App\Models\Supplier;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ── Super Admin ──────────────────────────────────────────────────────
        User::create([
            'name'     => 'Super Admin',
            'email'    => 'superadmin@itam.com',
            'password' => Hash::make('password'),
            'role'     => 'super_admin',
            'status'   => 'active',
        ]);

        // ── Organization ────────────────────────────────────────────────────
        $org = Organization::create([
            'name'    => 'TechCorp Solutions',
            'slug'    => 'techcorp-solutions',
            'email'   => 'info@techcorp.com',
            'phone'   => '+63 912 345 6789',
            'address' => '5F Technology Tower, Ayala Ave',
            'city'    => 'Makati City',
            'country' => 'Philippines',
            'status'  => 'active',
        ]);

        // ── Departments ─────────────────────────────────────────────────────
        $depts = [
            ['name' => 'IT Department',         'code' => 'IT'],
            ['name' => 'Finance',               'code' => 'FIN'],
            ['name' => 'Human Resources',       'code' => 'HR'],
            ['name' => 'Operations',            'code' => 'OPS'],
            ['name' => 'Sales & Marketing',     'code' => 'SALES'],
        ];

        $createdDepts = [];
        foreach ($depts as $d) {
            $createdDepts[] = Department::create(['organization_id' => $org->id, 'status' => 'active', ...$d]);
        }

        // ── Facilities ──────────────────────────────────────────────────────
        $facilityHQ = Facility::create([
            'organization_id' => $org->id,
            'name'    => 'Head Office',
            'code'    => 'HQ-MNL',
            'address' => '5F Technology Tower, Ayala Ave',
            'city'    => 'Makati City',
            'state'   => 'NCR',
            'country' => 'Philippines',
            'phone'   => '+63 2 8888 0001',
            'status'  => 'active',
        ]);

        $facilityCebu = Facility::create([
            'organization_id' => $org->id,
            'name'    => 'Cebu Branch',
            'code'    => 'BR-CEB',
            'city'    => 'Cebu City',
            'state'   => 'Cebu',
            'country' => 'Philippines',
            'phone'   => '+63 32 888 0002',
            'status'  => 'active',
        ]);

        // ── Locations (Work Locations under Facilities) ──────────────────
        $locations = [
            Location::create(['organization_id' => $org->id, 'facility_id' => $facilityHQ->id,   'name' => 'Main Office',   'building' => 'Tower A', 'floor' => '5F', 'status' => 'active']),
            Location::create(['organization_id' => $org->id, 'facility_id' => $facilityHQ->id,   'name' => 'Server Room',   'building' => 'Tower A', 'floor' => 'B1', 'status' => 'active']),
            Location::create(['organization_id' => $org->id, 'facility_id' => $facilityCebu->id, 'name' => 'Branch Office', 'building' => 'Annex B', 'floor' => '1F', 'status' => 'active']),
        ];

        // ── Users ────────────────────────────────────────────────────────────
        $admin = User::create([
            'organization_id' => $org->id,
            'department_id'   => $createdDepts[0]->id,
            'name'            => 'John Admin',
            'email'           => 'admin@techcorp.com',
            'password'        => Hash::make('password'),
            'role'            => 'admin',
            'employee_id'     => 'EMP-001',
            'job_title'       => 'IT Manager',
            'status'          => 'active',
        ]);

        $staff = User::create([
            'organization_id' => $org->id,
            'department_id'   => $createdDepts[1]->id,
            'name'            => 'Jane Staff',
            'email'           => 'staff@techcorp.com',
            'password'        => Hash::make('password'),
            'role'            => 'staff',
            'employee_id'     => 'EMP-002',
            'job_title'       => 'Accountant',
            'status'          => 'active',
        ]);

        // ── Vendors ──────────────────────────────────────────────────────────
        $vendorUser = User::create([
            'organization_id' => $org->id,
            'name'            => 'Dell Supplier',
            'email'           => 'vendor@delltech.com',
            'password'        => Hash::make('password'),
            'role'            => 'supplier',
            'status'          => 'active',
        ]);

        $vendor1 = Supplier::create([
            'organization_id' => $org->id,
            'user_id'         => $vendorUser->id,
            'name'            => 'Dell Technologies PH',
            'code'            => 'DELL-001',
            'email'           => 'sales@delltech.ph',
            'phone'           => '+63 2 8888 1234',
            'contact_person'  => 'Michael Cruz',
            'city'            => 'Taguig City',
            'country'         => 'Philippines',
            'status'          => 'active',
            'rating'          => 4.5,
        ]);

        $vendor2 = Supplier::create([
            'organization_id' => $org->id,
            'name'            => 'HP Philippines',
            'code'            => 'HP-001',
            'email'           => 'enterprise@hp.ph',
            'phone'           => '+63 2 8777 5678',
            'contact_person'  => 'Anna Santos',
            'city'            => 'Pasig City',
            'country'         => 'Philippines',
            'status'          => 'active',
            'rating'          => 4.2,
        ]);

        // ── Asset Categories ─────────────────────────────────────────────────
        $catLaptop  = AssetCategory::create(['organization_id' => $org->id, 'name' => 'Laptops',     'icon' => 'bi-laptop',    'depreciation_years' => 3]);
        $catDesktop = AssetCategory::create(['organization_id' => $org->id, 'name' => 'Desktops',    'icon' => 'bi-pc-display','depreciation_years' => 4]);
        $catServer  = AssetCategory::create(['organization_id' => $org->id, 'name' => 'Servers',     'icon' => 'bi-server',    'depreciation_years' => 5]);
        $catNetwork = AssetCategory::create(['organization_id' => $org->id, 'name' => 'Network Equipment', 'icon' => 'bi-router', 'depreciation_years' => 5]);
        $catPhone   = AssetCategory::create(['organization_id' => $org->id, 'name' => 'Mobile Phones','icon' => 'bi-phone',    'depreciation_years' => 2]);
        $catPrinter = AssetCategory::create(['organization_id' => $org->id, 'name' => 'Printers',    'icon' => 'bi-printer',   'depreciation_years' => 4]);

        // ── Assets ───────────────────────────────────────────────────────────
        $assets = [
            ['name' => 'Dell Latitude 5530',     'brand' => 'Dell',   'model' => 'Latitude 5530',  'category_id' => $catLaptop->id,  'vendor_id' => $vendor1->id, 'purchase_price' => 55000, 'serial_number' => 'DL5530-001', 'status' => 'assigned'],
            ['name' => 'Dell Latitude 5531',     'brand' => 'Dell',   'model' => 'Latitude 5531',  'category_id' => $catLaptop->id,  'vendor_id' => $vendor1->id, 'purchase_price' => 57000, 'serial_number' => 'DL5531-001', 'status' => 'available'],
            ['name' => 'HP EliteDesk 800 G9',   'brand' => 'HP',     'model' => 'EliteDesk 800',  'category_id' => $catDesktop->id, 'vendor_id' => $vendor2->id, 'purchase_price' => 45000, 'serial_number' => 'HP800G9-001','status' => 'available'],
            ['name' => 'Dell PowerEdge R740',    'brand' => 'Dell',   'model' => 'PowerEdge R740', 'category_id' => $catServer->id,  'vendor_id' => $vendor1->id, 'purchase_price' => 250000,'serial_number' => 'PE-R740-001','status' => 'available'],
            ['name' => 'Cisco Catalyst 2960',    'brand' => 'Cisco',  'model' => 'Catalyst 2960',  'category_id' => $catNetwork->id, 'vendor_id' => $vendor2->id, 'purchase_price' => 35000, 'serial_number' => 'CISCO-2960-01','status' => 'available'],
            ['name' => 'iPhone 14 Pro',          'brand' => 'Apple',  'model' => 'iPhone 14 Pro',  'category_id' => $catPhone->id,   'vendor_id' => null,         'purchase_price' => 65000, 'serial_number' => 'APL14P-001', 'status' => 'assigned'],
            ['name' => 'HP LaserJet Pro M404n',  'brand' => 'HP',     'model' => 'LaserJet M404n', 'category_id' => $catPrinter->id, 'vendor_id' => $vendor2->id, 'purchase_price' => 18000, 'serial_number' => 'HPLJ-M404-01','status' => 'available'],
        ];

        $createdAssets = [];
        foreach ($assets as $i => $assetData) {
            $assetData['organization_id'] = $org->id;
            $assetData['location_id']     = $locations[$i % count($locations)]->id;
            $assetData['asset_tag']       = 'ASSET-' . str_pad($i + 1, 4, '0', STR_PAD_LEFT);
            $assetData['purchase_date']   = now()->subMonths(rand(1, 24))->format('Y-m-d');
            $assetData['warranty_expiry_date'] = now()->addMonths(rand(6, 36))->format('Y-m-d');
            $assetData['condition']       = 'good';

            $asset = Asset::create($assetData);
            $createdAssets[] = $asset;

            // Add depreciation record
            DepreciationRecord::create([
                'asset_id'          => $asset->id,
                'method'            => 'straight_line',
                'useful_life_years' => AssetCategory::find($assetData['category_id'])->depreciation_years,
                'salvage_value'     => $assetData['purchase_price'] * 0.1,
                'start_date'        => $assetData['purchase_date'],
            ]);
        }

        // ── Assignments ──────────────────────────────────────────────────────
        AssetAssignment::create([
            'asset_id'      => $createdAssets[0]->id,
            'user_id'       => $staff->id,
            'assigned_by'   => $admin->id,
            'assigned_date' => now()->subMonths(3)->format('Y-m-d'),
            'status'        => 'active',
            'condition_out' => 'good',
            'purpose'       => 'Daily work laptop',
        ]);

        AssetAssignment::create([
            'asset_id'      => $createdAssets[5]->id,
            'user_id'       => $admin->id,
            'assigned_by'   => $admin->id,
            'assigned_date' => now()->subMonths(6)->format('Y-m-d'),
            'status'        => 'active',
            'condition_out' => 'excellent',
            'purpose'       => 'Business communications',
        ]);

        // ── Purchase Order ───────────────────────────────────────────────────
        $po = PurchaseOrder::create([
            'organization_id'        => $org->id,
            'vendor_id'              => $vendor1->id,
            'created_by'             => $admin->id,
            'po_number'              => 'PO-2024-0001',
            'order_date'             => now()->subMonths(3)->format('Y-m-d'),
            'expected_delivery_date' => now()->subMonths(2)->format('Y-m-d'),
            'actual_delivery_date'   => now()->subMonths(2)->subDays(5)->format('Y-m-d'),
            'status'                 => 'received',
            'subtotal'               => 112000,
            'tax_amount'             => 13440,
            'total_amount'           => 125440,
        ]);

        PurchaseOrderItem::create([
            'purchase_order_id' => $po->id,
            'category_id'       => $catLaptop->id,
            'item_name'         => 'Dell Latitude 5530',
            'brand'             => 'Dell',
            'model'             => 'Latitude 5530',
            'quantity'          => 2,
            'received_quantity' => 2,
            'unit_price'        => 56000,
            'total_price'       => 112000,
        ]);

        // ── Maintenance ──────────────────────────────────────────────────────
        MaintenanceRecord::create([
            'asset_id'        => $createdAssets[3]->id,
            'type'            => 'preventive',
            'scheduled_date'  => now()->addDays(15)->format('Y-m-d'),
            'description'     => 'Quarterly server maintenance and inspection',
            'technician_name' => 'Carlos Reyes',
            'status'          => 'scheduled',
            'next_maintenance_date' => now()->addMonths(3)->format('Y-m-d'),
        ]);

        MaintenanceRecord::create([
            'asset_id'        => $createdAssets[6]->id,
            'type'            => 'corrective',
            'scheduled_date'  => now()->subDays(10)->format('Y-m-d'),
            'completed_date'  => now()->subDays(7)->format('Y-m-d'),
            'description'     => 'Paper jam repair and roller replacement',
            'technician_name' => 'Luis Mendoza',
            'work_performed'  => 'Replaced paper feed rollers, cleaned interior',
            'parts_replaced'  => 'Paper feed roller set',
            'labor_cost'      => 1500,
            'parts_cost'      => 2200,
            'total_cost'      => 3700,
            'status'          => 'completed',
        ]);

        $this->command->info('Database seeded successfully!');
        $this->command->table(
            ['Role', 'Email', 'Password'],
            [
                ['Super Admin', 'superadmin@itam.com', 'password'],
                ['Admin',       'admin@techcorp.com',  'password'],
                ['Supplier',    'vendor@delltech.com', 'password'],
                ['Staff',       'staff@techcorp.com',  'password'],
            ]
        );
    }
}
