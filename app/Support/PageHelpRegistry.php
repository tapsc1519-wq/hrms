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
                'what' => 'Purchase Orders track approved purchases from suppliers before physical assets or software license seats are received.',
                'how' => [
                    'Choose Asset or Software for each line so receiving creates the correct register record.',
                    'Combine approved software requests when the product and supplier are the same.',
                    'Receive delivered quantities to create assets or license seats with purchase evidence.',
                ],
                'next' => 'Receiving software seats automatically fulfills linked approved employee requests in priority order.',
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
            'admin.sam-dashboard.*' => [
                'title' => 'SAM Overview Help',
                'what' => 'SAM Overview brings discovery health, normalization backlog, licensing risk, renewals, and remediation work into one operating view.',
                'how' => [
                    'Start with device coverage so compliance is based on current inventory.',
                    'Normalize the largest unknown software groups before reviewing shortage or policy risk.',
                    'Use the risk and renewal lists to decide what needs procurement, allocation, exception, or uninstall action.',
                ],
                'next' => 'After the overview is clean, open Software Compliance for detailed per-product decisions.',
            ],
            'admin.software-licenses.renewals' => [
                'title' => 'License Renewals Help',
                'what' => 'This page helps you decide what to renew, reduce, cancel, or manually review before license money is spent.',
                'how' => [
                    'Use the renewal window filter to focus on licenses expiring soon.',
                    'Review unused and low-use licenses before renewing.',
                    'Create a renewal plan with the decision, target seats, projected cost, owner, due date, and rationale.',
                    'Complete the plan after supplier confirmation so seats, cost, dates, and license status stay current.',
                ],
                'next' => 'Review expired and expiring licenses first, then assign owners to every decision before its due date.',
            ],
            'admin.software-requests.*' => [
                'title' => 'Software Requests Help',
                'what' => 'Software Requests turns employee demand into a reviewed and traceable license allocation.',
                'how' => [
                    'Review the employee, software, urgency, needed date, and business reason.',
                    'Approve a valid need or reject it with a clear explanation.',
                    'Allocate an available valid license after approval. If no seat is available, keep it approved while procurement is arranged.',
                ],
                'next' => 'Fulfilled requests automatically create the employee license allocation and appear under My Software.',
            ],
            'admin.software-optimization.*' => [
                'title' => 'Usage Optimization Help',
                'what' => 'Usage Optimization finds paid software allocations that have not been used recently and helps recover unnecessary seats.',
                'how' => [
                    'Choose an inactivity period such as 60 or 90 days.',
                    'Start a review to ask the employee whether the software is still required.',
                    'Retain justified allocations or reclaim unused seats for another employee.',
                    'Reclaimed reviews appear in SAM Overview savings and the SAM Audit Pack evidence.',
                ],
                'next' => 'No Usage Data means discovery telemetry is missing; confirm device reporting before reclaiming those licenses.',
            ],
            'staff.software-requests.*' => [
                'title' => 'Request Software Help',
                'what' => 'Use this page to ask for software needed for your work and follow its approval status.',
                'how' => [
                    'Choose the software from your organization catalog.',
                    'Explain the work you need it for and when you need it.',
                    'Track the request here. Once allocated, the license appears under My Software.',
                ],
                'next' => 'Contact IT through a support ticket if the software is not listed in the catalog.',
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
            'admin.agent-sources.*' => [
                  'title' => 'Endpoint Management Help',
                  'what' => 'Endpoint Management shows computers reporting inventory and lets authorized administrators send a small set of signed, audited device actions.',
                'how' => [
                    'Filter Healthy, Stale, or Offline devices and resolve computers that are not linked to an asset or employee.',
                    'Select devices and queue a signed inventory refresh when current data is required.',
                    'Download the Windows installer and use a short-lived enrollment token during setup; every enrolled computer receives its own revocable device key.',
                      'Review the agent version before relying on newly introduced collection features.',
                      'Open a device to install or remove approved WinGet packages, lock its active session, or schedule a restart.',
                ],
                'next' => 'Offline or stale devices should be checked before relying on their software data for compliance decisions.',
            ],
            'admin.software-normalization.*' => [
                'title' => 'Normalization Help',
                'what' => 'Normalization groups unknown software reported across the fleet and matches each group to the correct catalog record.',
                'how' => [
                    'Start with groups affecting the most installations, devices, or employees.',
                    'Map a group once to update every matching installation across the organization.',
                    'Keep Create Recognition Rule selected so the same name and publisher are mapped automatically next time.',
                ],
                'next' => 'After mapping, open Software Compliance to review shortages and mismatches.',
            ],
            'admin.software-policies.*' => [
                'title' => 'Software Policies Help',
                'what' => 'Software Policies records whether each catalog application is approved, restricted, prohibited, or still awaiting review.',
                'how' => [
                    'Review detected software first so policy decisions focus on applications actually used in the organization.',
                    'Record conditions for restricted products and a clear reason for prohibited products.',
                    'For prohibited detected software, create remediation tasks for IT to review and complete safely.',
                ],
                'next' => 'Open Software Compliance to track prohibited installations and complete their remediation tasks.',
            ],
            'admin.sam-audit.*' => [
                'title' => 'SAM Audit Pack Help',
                'what' => 'The SAM Audit Pack creates a tenant-scoped ZIP containing point-in-time software, entitlement, discovery, policy governance, exception, device, inventory quality, renewal, usage optimization, software request, software procurement, and remediation evidence.',
                'how' => [
                    'Choose how far back historical exceptions, renewal decisions, usage reviews, software requests, software procurement, and remediation actions should be included.',
                    'Include removed installations when the auditor needs evidence of historical software presence.',
                    'Review policy governance for unreviewed, stale, restricted, or prohibited titles before sharing the pack.',
                    'Review inventory data quality when stale agents or missing employee/device links could affect compliance confidence.',
                    'Store the generated package according to your organization audit and data-retention policy.',
                ],
                'next' => 'Review the summary and compliance snapshot first, then use the detailed CSV files as supporting evidence.',
            ],
            'admin.software-compliance.*' => [
                'title' => 'Software Compliance Help',
                'what' => 'Software Compliance checks whether discovered software has enough valid licenses and correct employee allocations.',
                'how' => [
                    'Review Under Licensed, Unauthorized, and Allocation Mismatch first.',
                    'Open Review to see exact users, devices, purchased licenses, and active allocations.',
                    'Approve a time-bound policy exception only for a specific installation with a documented business reason.',
                    'Record remediation so every decision and follow-up remains visible during audits.',
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
