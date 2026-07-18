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
                'sections' => [
                    'Command Center' => 'Shows enabled modules, pending actions, and the current working date.',
                    'Module Cards' => 'Shows each enabled module with its main count, secondary count, and Open action.',
                    'Operational Health' => 'Summarizes daily status and pending work across enabled modules.',
                    'Dashboard Panels' => 'Shows charts, recent activity, requests, tickets, and upcoming operational items.',
                ],
                'actions' => [
                    'Open' => 'Opens the selected module dashboard or work area.',
                    'Start Guide' => 'Walks through the key dashboard areas step by step.',
                    'Page Help' => 'Shows the written reference for the current page.',
                ],
                'tour' => [
                    [
                        'selector' => '.suite-hero',
                        'title' => 'Command Center',
                        'body' => 'Start here for the organization name, enabled module count, pending actions, and current date.',
                    ],
                    [
                        'selector' => '.module-card',
                        'title' => 'Module Cards',
                        'body' => 'Each card summarizes one enabled module. Use Open to jump directly into that area.',
                    ],
                    [
                        'selector' => '.suite-card .panel-title',
                        'title' => 'Operational Panels',
                        'body' => 'Dashboard panels group important health, trend, and action information so you can decide what needs attention first.',
                    ],
                    [
                        'selector' => '.mini-stat',
                        'title' => 'Quick Counts',
                        'body' => 'These compact numbers highlight daily status, pending work, and operational totals.',
                    ],
                    [
                        'selector' => '#globalPageHelpButton',
                        'title' => 'Page Help',
                        'body' => 'Use Page Help anytime for a written reference explaining this page and its actions.',
                    ],
                ],
                'next' => 'Start with alerts, pending approvals, or items that show a high count.',
            ],
            'admin.onboarding-wizard.*' => [
                'title' => 'Setup Wizard Help',
                'what' => 'The Setup Wizard shows the recommended order for preparing an organization before daily use or production rollout.',
                'how' => [
                    'Start from the first incomplete stage and use the action button beside the missing item.',
                    'Complete foundation and people setup before testing assets, software, support or workflows.',
                    'Use Production Readiness after the wizard to verify live-server and launch checks.',
                ],
                'sections' => [
                    'Admin onboarding' => 'Shows organization progress, enabled modules, remaining steps and the next recommended action.',
                    'Company Foundation' => 'Confirms facilities, departments and permission roles are ready.',
                    'People Setup' => 'Checks whether employee profiles are ready for assignments and workflow testing.',
                    'Workflow & Go-Live' => 'Highlights tasks, blockers and final readiness checks.',
                ],
                'actions' => [
                    'Continue Setup' => 'Opens the next incomplete setup area.',
                    'Open Production Readiness' => 'Opens the deeper launch checklist for production validation.',
                    'Create Setup Task' => 'Creates a task for any owner who needs to complete setup work.',
                ],
                'tour' => [
                    [
                        'target' => 'onboarding-summary',
                        'title' => 'Setup Progress',
                        'body' => 'This area shows the setup percentage, enabled modules and the next recommended action.',
                    ],
                    [
                        'target' => 'onboarding-stages',
                        'title' => 'Setup Stages',
                        'body' => 'Each stage groups related setup work and shows which items are complete or still pending.',
                    ],
                    [
                        'target' => 'onboarding-actions',
                        'title' => 'Final Actions',
                        'body' => 'Use these actions to review production readiness or create a setup task for another owner.',
                    ],
                ],
                'next' => 'Use the first incomplete action button, then return here to continue the next step.',
            ],
            'super-admin.organizations.create' => [
                'title' => 'Organization Onboarding Help',
                'what' => 'This page starts a customer onboarding flow for Niyantron products. It captures the organization, provisions OpsBridge, and prepares billing details.',
                'how' => [
                    'Complete company information first so the tenant record is clear.',
                    'Keep OpsBridge provisioning enabled when this customer has purchased or started a trial for OpsBridge.',
                    'Set trial, billing, partner and commission details before saving and continuing to first admin creation.',
                ],
                'sections' => [
                    'Organization Onboarding Wizard' => 'Shows the full customer onboarding path from organization creation to handover.',
                    'Company Information' => 'Stores the customer identity, contacts, website, tax number and address.',
                    'Product Provisioning' => 'Maps the organization to OpsBridge product domain and database.',
                ],
                'actions' => [
                    'Save & Continue' => 'Creates the organization and opens the next onboarding step.',
                ],
                'tour' => [
                    [
                        'target' => 'organization-onboarding-wizard',
                        'title' => 'Onboarding Path',
                        'body' => 'Use this strip to understand the full setup sequence for every new customer.',
                    ],
                    [
                        'target' => 'organization-details-step',
                        'title' => 'Organization Details',
                        'body' => 'Start by capturing the customer identity and contact details.',
                    ],
                    [
                        'target' => 'organization-product-step',
                        'title' => 'Product and Billing',
                        'body' => 'Provision OpsBridge, set trial or active status, and capture partner or commission details.',
                    ],
                ],
                'next' => 'After saving, create the first customer admin account from the next onboarding screen.',
            ],
            'super-admin.organizations.edit' => [
                'title' => 'Organization Handover Help',
                'what' => 'This page continues organization onboarding after the customer record exists.',
                'how' => [
                    'Review organization and product billing details.',
                    'Create the first customer admin if it is still missing.',
                    'Track credentials shared and initial setup completion from the onboarding checklist.',
                ],
                'sections' => [
                    'Organization Onboarding Wizard' => 'Shows which platform onboarding steps are complete.',
                    'First Admin Account' => 'Shows the first customer admin or provides the action to create one.',
                    'Onboarding Status' => 'Tracks handover items such as credentials and initial setup completion.',
                ],
                'actions' => [
                    'Create First Admin' => 'Opens user creation with the organization and Admin role preselected.',
                    'Save & Continue' => 'Updates organization, product and billing details.',
                ],
                'tour' => [
                    [
                        'target' => 'organization-onboarding-wizard',
                        'title' => 'Onboarding Progress',
                        'body' => 'Check where this customer stands in the onboarding journey.',
                    ],
                    [
                        'target' => 'organization-product-step',
                        'title' => 'Product Setup',
                        'body' => 'Confirm product, billing, partner and database/domain mapping.',
                    ],
                    [
                        'target' => 'organization-admin-step',
                        'title' => 'First Admin',
                        'body' => 'Create or verify the customer admin account before handover.',
                    ],
                ],
                'next' => 'Create the first admin, share credentials, then the customer admin can continue in the Admin Setup Wizard.',
            ],
            'staff.dashboard' => [
                'title' => 'My Dashboard Help',
                'what' => 'My Dashboard gives employees a quick view of attendance, actions needing attention, assigned items, requests, and shortcuts.',
                'how' => [
                    'Start with your attendance or pending attention items.',
                    'Use quick cards to jump to profile, assets, software, tickets, or requests.',
                    'Review recent lists to know what has changed in your account.',
                ],
                'tour' => [
                    [
                        'selector' => '.employee-hero',
                        'title' => 'Employee Overview',
                        'body' => 'Start here for your employee dashboard summary and date context.',
                    ],
                    [
                        'selector' => '.dash-card',
                        'title' => 'Dashboard Cards',
                        'body' => 'Cards show attendance, attention items, quick links, and recent activity.',
                    ],
                    [
                        'selector' => '.attention-item',
                        'title' => 'Needs Attention',
                        'body' => 'These items point to work that may require your response, upload, confirmation, or review.',
                    ],
                    [
                        'selector' => '.quick-card',
                        'title' => 'Quick Links',
                        'body' => 'Use these shortcuts to open common employee self-service pages quickly.',
                    ],
                    [
                        'selector' => '#globalPageHelpButton',
                        'title' => 'Page Help',
                        'body' => 'Use Page Help anytime for a written reference explaining this page and its actions.',
                    ],
                ],
                'next' => 'Handle urgent attention items first, then use quick links for routine self-service work.',
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
                'sections' => [
                    'Catalog list' => 'Shows software titles, publisher, category, licensing requirement, and quick actions.',
                    'Create/Edit form' => 'Stores product details, license metric, policy-related fields, WinGet Package ID, and endpoint deployment setting.',
                    'WinGet Package ID' => 'Exact package identifier used by the Windows agent for controlled install and uninstall commands.',
                    'Allow endpoint deployment' => 'Makes the software available in Endpoint Management install/remove dropdowns after a valid WinGet ID is entered.',
                ],
                'actions' => [
                    'Add Software' => 'Creates a new catalog title that can later receive licenses, discovery mappings, and policies.',
                    'Save Changes' => 'Updates the selected catalog title. If endpoint deployment is enabled, a WinGet Package ID is required.',
                    'Delete Software' => 'Removes the catalog title. Use carefully because linked licenses and assignments may be affected.',
                    'Copy command' => 'Copies a winget search command you can run on Windows to find the correct package ID.',
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
                'sections' => [
                    'SAM Health' => 'Overall score showing inventory, normalization, compliance, SLA, policy, evidence, and data quality risk.',
                    'Normalization Backlog' => 'Raw software names that still need mapping to catalog titles.',
                    'Highest Compliance Risk' => 'Software titles with license shortages, unauthorized installs, or policy risk.',
                    'Policy Exception Watch' => 'Approved exceptions that are expired or expiring soon.',
                    'License Evidence Quality' => 'Active licenses missing supplier, PO, invoice, cost, purchase date, or evidence document.',
                    'Inventory Data Quality' => 'Endpoints with missing employee/asset links, stale check-ins, or agent errors.',
                ],
                'actions' => [
                    'Audit Pack' => 'Opens the export page for auditor-ready SAM evidence.',
                    'Compliance' => 'Opens detailed license and policy compliance review.',
                    'Normalize' => 'Opens the workbench to map unknown discovered software.',
                    'Endpoints' => 'Opens device agent coverage and endpoint command management.',
                    'Policy exception counts' => 'Open filtered compliance lists for expired or expiring policy exceptions.',
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
            'staff.devices.*' => [
                'title' => 'Device Agent Help',
                'what' => 'Device Agent lets employees install the Windows, macOS, or Linux endpoint agent from their own account so IT can automatically link the reported computer to the correct employee.',
                'how' => [
                    'Create a setup token from this page and copy it immediately.',
                    'Download and run the correct agent installer for your company device.',
                    'Paste the API endpoint and setup token during installation.',
                    'After the first check-in, confirm your computer appears under Reported Agent Devices.',
                ],
                'sections' => [
                    'Install Device Agent' => 'Step-by-step employee installation instructions and the API endpoint required by the installer.',
                    'Setup Tokens' => 'Recent employee-bound setup tokens, their prefix, expiry, and active status.',
                    'Reported Agent Devices' => 'Computers that checked in using your employee-bound token or are otherwise linked to your account.',
                ],
                'actions' => [
                    'Download Agent' => 'Opens the operating-system selector for Windows, macOS, and Linux installers.',
                    'Create Setup Token' => 'Creates a 24-hour one-time enrollment token linked to your employee account.',
                    'Copy' => 'Copies the endpoint or setup token for pasting into the installer.',
                ],
                'next' => 'If installation needs administrator approval, contact IT support after creating your setup token.',
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
            'admin.agent-sources.index' => [
                  'title' => 'Endpoint Management Help',
                  'what' => 'Endpoint Management shows computers reporting inventory and lets authorized administrators send a small set of signed, audited device actions.',
                'how' => [
                    'Filter Healthy, Stale, or Offline devices and resolve computers that are not linked to an asset or employee.',
                    'Select devices and queue a signed inventory refresh when current data is required.',
                    'Use Download Agent to pick the operating system installer, then enroll with a short-lived setup token; every enrolled computer receives its own revocable device key.',
                      'Review the agent version before relying on newly introduced collection features.',
                      'Open a device to install or remove approved WinGet packages, lock its active session, or schedule a restart.',
                ],
                'sections' => [
                    'Endpoint list' => 'Shows enrolled Windows, macOS, and Linux devices, health, version, last seen, asset link, employee link, and inventory count.',
                    'Endpoint actions' => 'On a device page, this area contains lock, restart, inventory refresh, software install, and software remove controls.',
                    'Managed Software' => 'Lists only catalog software with endpoint deployment enabled and a WinGet Package ID.',
                    'Signed Command History' => 'Shows queued, delivered, executed, completed, failed, cancelled, or expired commands with result messages.',
                    'Software Inventory' => 'Shows software discovered on that device and whether each record is mapped to the catalog.',
                ],
                'actions' => [
                    'Download Agent' => 'Opens the operating-system selector for Windows, macOS, and Linux installers.',
                    'Create Enrollment Token' => 'Creates a short-lived setup token for agent installation.',
                    'Refresh Selected' => 'Queues inventory refresh commands for selected devices.',
                    'Link' => 'Connects a reported endpoint to the correct asset and employee.',
                ],
                'tour' => [
                    [
                        'target' => 'endpoint-list-header',
                        'title' => 'Endpoint Management',
                        'body' => 'Start here to understand device coverage, installer access, and where endpoint administration begins.',
                    ],
                    [
                        'target' => 'endpoint-installer-actions',
                        'title' => 'Installer and Enrollment',
                        'body' => 'Use these actions to download the correct agent installer and create enrollment tokens for new device setup.',
                    ],
                    [
                        'target' => 'endpoint-health-summary',
                        'title' => 'Fleet Health Summary',
                        'body' => 'These cards show enrolled devices, healthy check-ins, attention needed, and devices missing asset or employee links.',
                    ],
                    [
                        'target' => 'endpoint-filters',
                        'title' => 'Fleet Filters',
                        'body' => 'Use search, health, linking, access, version, and row filters to narrow the endpoint list before taking action.',
                    ],
                    [
                        'target' => 'endpoint-device-table',
                        'title' => 'Enrolled Devices',
                        'body' => 'This table is the main fleet view. Open a device name for command history, software inventory, and device actions.',
                    ],
                    [
                        'target' => 'endpoint-bulk-refresh',
                        'title' => 'Refresh Selected',
                        'body' => 'Select one or more devices, then queue an inventory refresh so the agent reports current hardware and software data.',
                    ],
                    [
                        'target' => 'endpoint-deployment-setup',
                        'title' => 'Deployment Setup',
                        'body' => 'Use this section to confirm the agent API endpoint and manage enrollment tokens used during rollout.',
                    ],
                ],
                'next' => 'Open a specific endpoint to review its inventory, command history, and secure device actions.',
            ],
            'admin.agent-sources.*' => [
                  'title' => 'Endpoint Management Help',
                  'what' => 'Endpoint Management shows computers reporting inventory and lets authorized administrators send a small set of signed, audited device actions.',
                'how' => [
                    'Filter Healthy, Stale, or Offline devices and resolve computers that are not linked to an asset or employee.',
                    'Select devices and queue a signed inventory refresh when current data is required.',
                    'Use Download Agent to pick the operating system installer, then enroll with a short-lived setup token; every enrolled computer receives its own revocable device key.',
                      'Review the agent version before relying on newly introduced collection features.',
                      'Open a device to install or remove approved WinGet packages, lock its active session, or schedule a restart.',
                ],
                'sections' => [
                    'Endpoint list' => 'Shows enrolled Windows, macOS, and Linux devices, health, version, last seen, asset link, employee link, and inventory count.',
                    'Endpoint actions' => 'On a device page, this area contains lock, restart, inventory refresh, software install, and software remove controls.',
                    'Managed Software' => 'Lists only catalog software with endpoint deployment enabled and a WinGet Package ID.',
                    'Signed Command History' => 'Shows queued, delivered, executed, completed, failed, cancelled, or expired commands with result messages.',
                    'Software Inventory' => 'Shows software discovered on that device and whether each record is mapped to the catalog.',
                ],
                'actions' => [
                    'Queue Inventory Refresh' => 'Asks the agent to rescan inventory on its next secure check-in.',
                    'Lock Session' => 'Queues a Windows session lock command for the endpoint.',
                    'Restart' => 'Schedules a restart command with a delay and employee-facing message.',
                    'Install' => 'Queues a winget install command for the selected managed software package ID.',
                    'Remove' => 'Queues a winget uninstall command for the selected managed software package ID.',
                    'Cancel' => 'Cancels a queued or delivered command before it is closed.',
                    'Revoke Device Access' => 'Revokes the device credential so the agent can no longer report or receive commands until re-enrolled.',
                ],
                'tour' => [
                    [
                        'target' => 'endpoint-header',
                        'title' => 'Endpoint Identity',
                        'body' => 'Start here to confirm hostname, OS, health, last seen, and whether this is the correct device.',
                    ],
                    [
                        'target' => 'endpoint-actions',
                        'title' => 'Endpoint Actions',
                        'body' => 'This area contains secure commands you can queue for this device. Controls are disabled if the agent cannot receive commands.',
                    ],
                    [
                        'target' => 'device-controls',
                        'title' => 'Device Controls',
                        'body' => 'Lock Session and Restart send signed commands to the Windows agent. They run after the next check-in.',
                    ],
                    [
                        'target' => 'managed-software',
                        'title' => 'Managed Software',
                        'body' => 'Install and Remove use the software catalog WinGet Package ID. Only enabled catalog titles appear here.',
                    ],
                    [
                        'target' => 'command-history',
                        'title' => 'Command History',
                        'body' => 'Every command is audited here with queued, delivered, executed, status, result, and package payload details.',
                    ],
                    [
                        'target' => 'software-inventory',
                        'title' => 'Software Inventory',
                        'body' => 'Use this section to verify what software the agent reported and whether it is mapped to your catalog.',
                    ],
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
                'sections' => [
                    'Unknown groups' => 'Shows raw software names and publishers that are not yet mapped to the software catalog.',
                    'Impact counts' => 'Shows how many installs, devices, and users are affected by each unknown group.',
                    'Recognition rules' => 'Saved mapping rules that automatically normalize future matching discoveries.',
                ],
                'actions' => [
                    'Map' => 'Links an unknown group or record to an existing software catalog title.',
                    'Create and Map' => 'Creates a new catalog title and maps the selected unknown software to it.',
                    'Ignore' => 'Marks noise or irrelevant software so it is excluded from compliance review.',
                    'Create Recognition Rule' => 'Remembers the mapping pattern for future discovery imports or agent reports.',
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
                'what' => 'The SAM Audit Pack creates a tenant-scoped ZIP containing SAM health score, point-in-time software, entitlement, license evidence quality, discovery, policy governance, exception, device, inventory quality, renewal, renewal SLA, usage optimization, software request, software procurement, remediation, and remediation SLA evidence.',
                'how' => [
                    'Start with the SAM health score to understand the biggest control gaps before reading the detailed CSV files.',
                    'Choose how far back historical exceptions, renewal decisions, usage reviews, software requests, software procurement, and remediation actions should be included.',
                    'Include removed installations when the auditor needs evidence of historical software presence.',
                    'Review policy governance for unreviewed, stale, restricted, or prohibited titles before sharing the pack.',
                    'Review license evidence quality when active entitlements are missing supplier, invoice, PO, cost, or document proof.',
                    'Review remediation SLA when open compliance actions are overdue or due soon.',
                    'Review renewal SLA when planned renewal decisions are overdue or due soon.',
                    'Review inventory data quality when stale agents or missing employee/device links could affect compliance confidence.',
                    'Store the generated package according to your organization audit and data-retention policy.',
                ],
                'sections' => [
                    'Summary cards' => 'Show catalog, installation, inventory, policy, license, request, procurement, and remediation evidence counts.',
                    'Build Audit Package' => 'Lets you choose activity history date and whether removed software records should be included.',
                    'Package Contents' => 'Explains every CSV/metadata file included in the generated ZIP.',
                    'Audit Manifest' => 'Lists each evidence file with row count, byte size, and SHA-256 hash for integrity checks.',
                ],
                'actions' => [
                    'Build and Download Audit Pack' => 'Generates a ZIP of SAM evidence using the selected options.',
                    'Include removed software records' => 'Adds historical software records that agents reported as removed.',
                    'Exception Expiry / Expired Exceptions cards' => 'Open Compliance with the matching exception-risk filter.',
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
                'sections' => [
                    'Compliance list' => 'Shows each software title with installs, required seats, purchased seats, status, risk score, and exposure.',
                    'Exception Risk filter' => 'Narrows the list to titles with approved policy exceptions that are expired or expiring soon.',
                    'Review page' => 'Shows licenses, allocations, discovery evidence, policy exceptions, and remediation actions for one software title.',
                    'Policy Exception History' => 'Shows time-bound approvals, expiry risk, renewal/extension action, and revocation history.',
                    'Remediation Actions' => 'Tracks tasks such as purchase seats, allocate licenses, review policy risk, or queue endpoint uninstall.',
                ],
                'actions' => [
                    'Filter' => 'Applies search, compliance status, and exception risk filters.',
                    'Review' => 'Opens full evidence and actions for one software title.',
                    'Assign License' => 'Allocates an available license seat to a discovered user.',
                    'Exception' => 'Approves a temporary policy exception for one detected installation.',
                    'Extend / Renew' => 'Changes the expiry date and reason for an approved policy exception.',
                    'Revoke' => 'Closes an active policy exception before expiry.',
                    'Queue Uninstall' => 'Queues an endpoint uninstall command when software, device, agent credential, and permissions are ready.',
                    'Done' => 'Marks a remediation action completed.',
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
