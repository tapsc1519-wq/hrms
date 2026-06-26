<?php

namespace App\Support;

class PageHelpRegistry
{
    public static function current(): array
    {
        $routeName = request()->route()?->getName() ?? '';

        foreach (self::entries() as $pattern => $help) {
            if (self::matches($routeName, $pattern)) {
                return $help;
            }
        }

        return [
            'title' => 'Page Help',
            'what' => 'This page is part of your operations portal. Use it to view records, update information, and complete the work related to this section.',
            'how' => [
                'Use filters and search to find the record you need.',
                'Open a record to see full details before making changes.',
                'Use the action buttons on the page to create, update, approve, reject, assign, return, or export information when available.',
            ],
            'next' => 'If you are not sure what to do, review the page title, table columns, and available action buttons first.',
        ];
    }

    private static function matches(string $routeName, string $pattern): bool
    {
        if ($routeName === $pattern) {
            return true;
        }

        return str_ends_with($pattern, '.*')
            && str_starts_with($routeName, rtrim($pattern, '*'));
    }

    private static function entries(): array
    {
        return [
            'admin.dashboard' => [
                'title' => 'Dashboard Help',
                'what' => 'The dashboard gives a quick summary of your organization: assets, people, requests, tickets, payroll, software, and pending work.',
                'how' => [
                    'Use the cards to understand what needs attention.',
                    'Open the module links to work on detailed records.',
                    'Check pending tasks before starting daily operations.',
                ],
                'next' => 'Start with alerts, pending approvals, or items that show a high count.',
            ],
            'admin.assets.*' => [
                'title' => 'Assets Help',
                'what' => 'Assets are physical company items such as laptops, desktops, phones, printers, network devices, and accessories.',
                'how' => [
                    'Add assets with correct asset tag, serial number, category, location, purchase, and warranty details.',
                    'Use asset status to know whether an item is available, assigned, under repair, retired, lost, or disposed.',
                    'Open an asset to review assignment, maintenance, depreciation, and disposal history.',
                ],
                'next' => 'Keep asset tag and serial number separate. Asset tag is your internal company label; serial number is from the manufacturer.',
            ],
            'admin.assignments.*' => [
                'title' => 'Assignments Help',
                'what' => 'Assignments record which employee currently has which asset. This helps with accountability during onboarding, transfers, returns, and exits.',
                'how' => [
                    'Create an assignment when an asset is given to an employee.',
                    'Use return when the employee gives the item back.',
                    'Use bulk assignment when many assets need to be assigned from a CSV file.',
                ],
                'next' => 'Before assigning, confirm the asset is available and the employee details are correct.',
            ],
            'admin.purchase-orders.*' => [
                'title' => 'Purchase Orders Help',
                'what' => 'Purchase Orders track approved purchases from suppliers before assets or licenses are received.',
                'how' => [
                    'Create a purchase order with supplier, item, quantity, and price details.',
                    'Receive items against the purchase order when delivery happens.',
                    'Use received items to create compliant asset records instead of manually adding assets without purchase proof.',
                ],
                'next' => 'For compliance, prefer creating assets from received purchase order items whenever possible.',
            ],
            'admin.disposals.*' => [
                'title' => 'Disposal Help',
                'what' => 'Disposal is used when an asset must be retired, scrapped, sold, donated, written off, or marked lost.',
                'how' => [
                    'Create a disposal request with reason and supporting notes.',
                    'Approval should be completed by an authorized role before the asset is finally disposed.',
                    'Keep disposal history for audit and compliance.',
                ],
                'next' => 'For lost or damaged devices, first review Asset Issues if the employee reported a problem.',
            ],
            'admin.asset-issues.*' => [
                'title' => 'Asset Issues Help',
                'what' => 'Asset Issues are reports from employees when an assigned asset is damaged, lost, stolen, not working, or needs disposal review.',
                'how' => [
                    'Review the employee report and asset details.',
                    'Decide whether the asset needs repair, replacement, disposal, or no action.',
                    'Create a disposal request from the issue only when disposal is required.',
                ],
                'next' => 'Use clear review notes so future audits can understand why the action was taken.',
            ],
            'admin.software.*' => [
                'title' => 'Software Catalog Help',
                'what' => 'The software catalog is the master list of software your organization wants to track, license, and audit.',
                'how' => [
                    'Create one catalog record per software product and edition where possible.',
                    'Set license requirement, criticality, type, and license metric correctly.',
                    'Use this catalog during discovery normalization and compliance review.',
                ],
                'next' => 'After catalog setup, add licenses and map discovered software to catalog records.',
            ],
            'admin.software-licenses.renewals' => [
                'title' => 'License Renewals Help',
                'what' => 'This page helps you decide what to renew, reduce, cancel, or manually review before license money is spent.',
                'how' => [
                    'Use the renewal window filter to focus on licenses expiring soon.',
                    'Review unused and low-use licenses before renewing.',
                    'Open a license to check assignments, supplier details, purchase evidence, and renewal dates.',
                ],
                'next' => 'Review expired and expiring licenses first, then look for reduce or cancel-review opportunities.',
            ],
            'admin.software-licenses.*' => [
                'title' => 'Software Licenses Help',
                'what' => 'Software licenses record purchased seats, subscription details, expiry dates, suppliers, invoices, and evidence documents.',
                'how' => [
                    'Add license purchases with seat count, cost, invoice, agreement, and expiry details.',
                    'Assign licenses to employees when a seat is allocated.',
                    'Return a license when the employee no longer needs it.',
                ],
                'next' => 'Keep purchase evidence attached so compliance checks have proof.',
            ],
            'admin.software-discovery.*' => [
                'title' => 'Discovery Inventory Help',
                'what' => 'Discovery Inventory is the raw list of software found on employee devices. It can come from CSV imports now and from a device agent later.',
                'how' => [
                    'Import discovery data from device inventory.',
                    'Check whether records are Unknown, Mapped, or Ignored.',
                    'Map unknown software before trusting compliance numbers.',
                ],
                'next' => 'Open Normalization Workbench to map unknown software to the catalog.',
            ],
            'admin.software-normalization.*' => [
                'title' => 'Normalization Help',
                'what' => 'Normalization means matching raw software names from devices to the correct software catalog record.',
                'how' => [
                    'Review unknown software one by one.',
                    'Map each record to the correct catalog software.',
                    'Create recognition rules for names that should be mapped automatically next time.',
                ],
                'next' => 'After mapping, open Software Compliance to review shortages and mismatches.',
            ],
            'admin.software-compliance.*' => [
                'title' => 'Software Compliance Help',
                'what' => 'Software Compliance checks whether discovered software has enough valid licenses and correct employee allocations.',
                'how' => [
                    'Review Under Licensed, Unauthorized, and Allocation Mismatch first.',
                    'Open Review to see exact users, devices, purchased licenses, and active allocations.',
                    'Record remediation so the decision is visible during audits.',
                ],
                'next' => 'Fix issues by allocating an available license, purchasing seats, approving an exception, or planning uninstall/reclaim.',
            ],
            'admin.employees.*' => [
                'title' => 'Employees Help',
                'what' => 'Employees are staff profiles used for HRMS, asset assignment, attendance, leave, payroll, software allocation, and requests.',
                'how' => [
                    'Create employee records with correct employee code, department, designation, and contact details.',
                    'Keep user account and employee profile information updated.',
                    'Use employee records to connect assets, software, documents, attendance, leave, and payroll.',
                ],
                'next' => 'Use Employee Code consistently wherever employee identification is required.',
            ],
            'admin.attendance.*' => [
                'title' => 'Attendance Help',
                'what' => 'Attendance tracks sign-in, sign-out, working time, late marks, early leaving, overtime, and regularization requests.',
                'how' => [
                    'Review daily attendance records for missing or unusual entries.',
                    'Approve or reject regularization requests based on company policy.',
                    'Lock attendance when the month is finalized for payroll.',
                ],
                'next' => 'Before payroll, confirm attendance summary and locks are correct.',
            ],
            'admin.leaves.*' => [
                'title' => 'Leaves Help',
                'what' => 'Leaves are employee time-off requests such as casual leave, sick leave, earned leave, or unpaid leave.',
                'how' => [
                    'Review leave dates, reason, balance, and overlap before approval.',
                    'Approve valid requests and reject requests that do not meet policy.',
                    'Keep balances updated so payroll and attendance remain correct.',
                ],
                'next' => 'Check leave balances before approving long leave requests.',
            ],
            'admin.payroll.*' => [
                'title' => 'Payroll Help',
                'what' => 'Payroll manages salary structures, salary components, payroll runs, approvals, payslips, and payment exports.',
                'how' => [
                    'Set salary components and employee salary structures first.',
                    'Generate payroll only after attendance and leave data are finalized.',
                    'Review, approve, export, and mark payroll runs as paid after validation.',
                ],
                'next' => 'Do not approve payroll until attendance, leaves, and employee salary structures are checked.',
            ],
            'admin.tickets.*' => [
                'title' => 'Support Tickets Help',
                'what' => 'Support Tickets track employee issues, requests, comments, assignments, and resolution status.',
                'how' => [
                    'Review the ticket details and supporting files.',
                    'Assign the ticket to the right support person.',
                    'Reply with updates and close the ticket only after resolution.',
                ],
                'next' => 'Use clear comments so the employee and support team know the latest status.',
            ],
            'admin.suppliers.*' => [
                'title' => 'Suppliers Help',
                'what' => 'Suppliers are vendors who provide assets, software, services, or support to your organization.',
                'how' => [
                    'Keep supplier contact, tax, and address details updated.',
                    'Use suppliers in purchase orders, invoices, warranties, and license purchases.',
                    'Review supplier history before placing new orders.',
                ],
                'next' => 'Add suppliers before creating purchase orders or license purchases.',
            ],
            'admin.reports.*' => [
                'title' => 'Reports Help',
                'what' => 'Reports summarize operational data for review, audit, compliance, planning, and management decisions.',
                'how' => [
                    'Use filters to narrow the report by date, status, category, supplier, employee, or department when available.',
                    'Review totals and exceptions before exporting.',
                    'Use exported reports for management review or audit documentation.',
                ],
                'next' => 'Export only after filters and dates are correct.',
            ],
            'staff.*' => [
                'title' => 'Employee Portal Help',
                'what' => 'This section lets employees view their own records and raise requests related to assets, HR, leave, attendance, payroll, software, and support.',
                'how' => [
                    'Use My Assets to view assigned company items and report problems.',
                    'Use requests, leaves, attendance, payslips, software, and tickets for self-service work.',
                    'Check status updates regularly after submitting a request.',
                ],
                'next' => 'Open the relevant self-service section and submit accurate information.',
            ],
            'supplier.*' => [
                'title' => 'Supplier Portal Help',
                'what' => 'The supplier portal lets suppliers review purchase orders and related purchase communication.',
                'how' => [
                    'Open purchase orders assigned to your supplier account.',
                    'Review item details, quantities, dates, and status.',
                    'Use the portal to track order progress and supporting information.',
                ],
                'next' => 'Contact the organization admin if purchase order details are incorrect.',
            ],
            'super-admin.*' => [
                'title' => 'Super Admin Help',
                'what' => 'Super Admin pages manage organizations, plans, payments, platform settings, and global users.',
                'how' => [
                    'Create and manage organization workspaces.',
                    'Enable modules based on the customer subscription.',
                    'Track trial, payment, and subscription status.',
                ],
                'next' => 'Check organization module access before troubleshooting missing menus.',
            ],
        ];
    }
}
