<?php

namespace App\Support;

class DepartmentTemplateRegistry
{
    public static function all(): array
    {
        return [
            ['name' => 'Information Technology', 'code' => 'IT', 'description' => 'Technology support, infrastructure, endpoint and software operations.'],
            ['name' => 'Human Resources', 'code' => 'HR', 'description' => 'Employee records, attendance, leaves, hiring and people operations.'],
            ['name' => 'Finance', 'code' => 'FIN', 'description' => 'Budgeting, payments, payroll coordination and financial controls.'],
            ['name' => 'Administration', 'code' => 'ADMIN', 'description' => 'Office administration, policies, vendor coordination and general services.'],
            ['name' => 'Procurement', 'code' => 'PROC', 'description' => 'Purchases, supplier coordination, purchase orders and goods receipt.'],
            ['name' => 'Operations', 'code' => 'OPS', 'description' => 'Daily business operations, service delivery and operational planning.'],
            ['name' => 'Support', 'code' => 'SUP', 'description' => 'Internal support desk, issue handling and user assistance.'],
            ['name' => 'Facilities', 'code' => 'FAC', 'description' => 'Facilities, locations, seating, office assets and physical services.'],
            ['name' => 'Security', 'code' => 'SEC', 'description' => 'Physical security, access control and safety coordination.'],
            ['name' => 'Sales', 'code' => 'SALES', 'description' => 'Customer acquisition, sales pipeline and account ownership.'],
            ['name' => 'Marketing', 'code' => 'MKT', 'description' => 'Campaigns, brand communication, content and market activities.'],
            ['name' => 'Legal', 'code' => 'LEGAL', 'description' => 'Contracts, legal reviews, notices and statutory documentation.'],
            ['name' => 'Compliance', 'code' => 'COMP', 'description' => 'Policy compliance, audits, controls and risk follow-up.'],
        ];
    }

    public static function names(): array
    {
        return array_column(self::all(), 'name');
    }
}
