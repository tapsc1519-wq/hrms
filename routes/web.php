<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\SuperAdmin\DashboardController as SADashboard;
use App\Http\Controllers\SuperAdmin\OrganizationController;
use App\Http\Controllers\SuperAdmin\PaymentController as SAPaymentController;
use App\Http\Controllers\SuperAdmin\PricingController;
use App\Http\Controllers\SuperAdmin\SettingController as SASettingController;
use App\Http\Controllers\SuperAdmin\UserController as SAUserController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboard;
use App\Http\Controllers\Admin\AgentSourceController;
use App\Http\Controllers\Admin\AssetController;
use App\Http\Controllers\Admin\AssetDisposalController;
use App\Http\Controllers\Admin\AssetIssueReportController;
use App\Http\Controllers\Admin\SupplierController;
use App\Http\Controllers\Admin\PurchaseOrderController;
use App\Http\Controllers\Admin\AssignmentController;
use App\Http\Controllers\Admin\MaintenanceController;
use App\Http\Controllers\Admin\RequestController as AdminRequestController;
use App\Http\Controllers\Admin\DepartmentController;
use App\Http\Controllers\Admin\FacilityController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Admin\UserBulkImportController;
use App\Http\Controllers\Admin\RoleController as AdminRoleController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\TicketController as AdminTicketController;
use App\Http\Controllers\Admin\SoftwareController as AdminSoftwareController;
use App\Http\Controllers\Admin\SoftwareComplianceController;
use App\Http\Controllers\Admin\SoftwareDiscoveryController;
use App\Http\Controllers\Admin\SoftwareLicenseController;
use App\Http\Controllers\Admin\SoftwareOptimizationController;
use App\Http\Controllers\Admin\SoftwarePolicyController;
use App\Http\Controllers\Admin\SoftwareRequestController;
use App\Http\Controllers\Admin\SamAuditReportController;
use App\Http\Controllers\Admin\CatalogController;
use App\Http\Controllers\Admin\BulkImportController;
use App\Http\Controllers\Admin\EmployeeController;
use App\Http\Controllers\Admin\EmployeeBulkImportController;
use App\Http\Controllers\Admin\HrmsDashboardController;
use App\Http\Controllers\Admin\HrShiftController;
use App\Http\Controllers\Admin\PayrollController;
use App\Http\Controllers\Admin\AttendanceController as AdminAttendanceController;
use App\Http\Controllers\Admin\LeaveController as AdminLeaveController;
use App\Http\Controllers\Admin\LeaveBalanceController;
use App\Http\Controllers\Admin\HrmsSettingController;
use App\Http\Controllers\Admin\SsoSettingController;
use App\Http\Controllers\Supplier\DashboardController as SupplierDashboard;
use App\Http\Controllers\Supplier\PurchaseOrderController as SupplierPOController;
use App\Http\Controllers\Staff\DashboardController as StaffDashboard;
use App\Http\Controllers\Staff\AssetController as StaffAssetController;
use App\Http\Controllers\Staff\RequestController as StaffRequestController;
use App\Http\Controllers\Staff\TicketController as StaffTicketController;
use App\Http\Controllers\Staff\SoftwareController as StaffSoftwareController;
use App\Http\Controllers\Staff\HrmsDashboardController as StaffHrmsDashboardController;
use App\Http\Controllers\Staff\ProfileController as StaffProfileController;
use App\Http\Controllers\Staff\AttendanceController as StaffAttendanceController;
use App\Http\Controllers\Staff\LeaveController as StaffLeaveController;
use App\Http\Controllers\Staff\PayrollController as StaffPayrollController;
use Illuminate\Support\Facades\Route;

// ─── Auth ─────────────────────────────────────────────────────────────────────
Route::get('/', fn() => redirect()->route('login'));
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
Route::post('/register', [AuthController::class, 'register'])->name('register.post');
Route::post('/auth/sso/redirect', [AuthController::class, 'redirectToSso'])->name('sso.redirect');
Route::get('/auth/sso/callback', [AuthController::class, 'handleSsoCallback'])->name('sso.callback');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// ─── Super Admin ──────────────────────────────────────────────────────────────
Route::prefix('super-admin')->name('super-admin.')->middleware(['auth', 'role:super_admin'])->group(function () {
    Route::get('/dashboard', [SADashboard::class, 'index'])->name('dashboard');

    Route::patch('organizations/{organization}/modules', [OrganizationController::class, 'updateModules'])->name('organizations.modules.update');
    Route::post('organizations/{organization}/payments', [OrganizationController::class, 'recordPayment'])->name('organizations.payments.store');
    Route::resource('organizations', OrganizationController::class);
    Route::resource('users', SAUserController::class)->except(['show']);

    Route::get('pricing', [PricingController::class, 'edit'])->name('pricing.edit');
    Route::put('pricing', [PricingController::class, 'update'])->name('pricing.update');
    Route::get('payments', [SAPaymentController::class, 'index'])->name('payments.index');
    Route::get('payments/export', [SAPaymentController::class, 'export'])->name('payments.export');
    Route::get('payments/{payment}', [SAPaymentController::class, 'show'])->name('payments.show');

    Route::get('settings', [SASettingController::class, 'edit'])->name('settings');
    Route::put('settings', [SASettingController::class, 'update'])->name('settings.update');
    Route::delete('settings/logo', [SASettingController::class, 'removeLogo'])->name('settings.remove-logo');
    Route::delete('settings/favicon', [SASettingController::class, 'removeFavicon'])->name('settings.remove-favicon');
});

// ─── Admin ────────────────────────────────────────────────────────────────────
Route::prefix('admin')->name('admin.')->middleware(['auth', 'role:admin,super_admin'])->group(function () {
    Route::get('/dashboard', [AdminDashboard::class, 'index'])->name('dashboard');
    Route::get('sso-settings', [SsoSettingController::class, 'edit'])->name('sso-settings.edit');
    Route::put('sso-settings', [SsoSettingController::class, 'update'])->name('sso-settings.update');

    Route::middleware('module:itam')->group(function () {
    Route::resource('assets', AssetController::class)->only(['create', 'store'])->middleware('permission:assets.create');
    Route::resource('assets', AssetController::class)->only(['index', 'show'])->middleware('permission:assets.view');
    Route::resource('assets', AssetController::class)->only(['edit', 'update'])->middleware('permission:assets.edit');
    Route::resource('assets', AssetController::class)->only(['destroy'])->middleware('permission:assets.delete');
    Route::resource('suppliers', SupplierController::class)->middleware('permission:suppliers.manage');

    Route::get('purchase-orders', [PurchaseOrderController::class, 'index'])->middleware('permission:purchase_orders.manage')->name('purchase-orders.index');
    Route::get('purchase-orders/create', [PurchaseOrderController::class, 'create'])->middleware('permission:purchase_orders.manage')->name('purchase-orders.create');
    Route::post('purchase-orders', [PurchaseOrderController::class, 'store'])->middleware('permission:purchase_orders.manage')->name('purchase-orders.store');
    Route::get('purchase-orders/{purchaseOrder}/receive', [PurchaseOrderController::class, 'receive'])->middleware('permission:purchase_orders.manage')->name('purchase-orders.receive');
    Route::post('purchase-orders/{purchaseOrder}/receipts', [PurchaseOrderController::class, 'storeReceipt'])->middleware('permission:purchase_orders.manage')->name('purchase-orders.receipts.store');
    Route::get('purchase-orders/{purchaseOrder}', [PurchaseOrderController::class, 'show'])->middleware('permission:purchase_orders.manage')->name('purchase-orders.show');
    Route::patch('purchase-orders/{purchaseOrder}/status', [PurchaseOrderController::class, 'updateStatus'])->middleware('permission:purchase_orders.manage')->name('purchase-orders.status');
    Route::delete('purchase-orders/{purchaseOrder}', [PurchaseOrderController::class, 'destroy'])->middleware('permission:purchase_orders.manage')->name('purchase-orders.destroy');

    Route::get('assignments', [AssignmentController::class, 'index'])->middleware('permission:assignments.view')->name('assignments.index');
    Route::get('assignments/create', [AssignmentController::class, 'create'])->middleware('permission:assignments.create')->name('assignments.create');
    Route::post('assignments', [AssignmentController::class, 'store'])->middleware('permission:assignments.create')->name('assignments.store');
    Route::patch('assignments/{assignment}/return', [AssignmentController::class, 'returnAsset'])->middleware('permission:assignments.return')->name('assignments.return');
    Route::patch('assignments/{assignment}/handover', [AssignmentController::class, 'handover'])->middleware('permission:assignments.return')->name('assignments.handover');
    Route::get('assignments/bulk', [AssignmentController::class, 'bulk'])->middleware('permission:assignments.create')->name('assignments.bulk');
    Route::get('assignments/bulk/template', [AssignmentController::class, 'bulkTemplate'])->middleware('permission:assignments.create')->name('assignments.bulk.template');
    Route::post('assignments/bulk', [AssignmentController::class, 'storeBulk'])->middleware('permission:assignments.create')->name('assignments.bulk.store');

    // Bulk Import
    Route::get('bulk-import', [BulkImportController::class, 'index'])->middleware('permission:assets.import')->name('bulk-import.index');
    Route::get('bulk-import/template', [BulkImportController::class, 'downloadTemplate'])->middleware('permission:assets.import')->name('bulk-import.template');
    Route::post('bulk-import/preview', [BulkImportController::class, 'preview'])->middleware('permission:assets.import')->name('bulk-import.preview');
    Route::post('bulk-import/confirm', [BulkImportController::class, 'import'])->middleware('permission:assets.import')->name('bulk-import.confirm');

    Route::resource('maintenance', MaintenanceController::class)->except(['destroy'])->middleware('permission:maintenance.manage');

    Route::get('requests', [AdminRequestController::class, 'index'])->middleware('permission:requests.view')->name('requests.index');
    Route::get('requests/{assetRequest}', [AdminRequestController::class, 'show'])->middleware('permission:requests.view')->name('requests.show');
    Route::patch('requests/{assetRequest}/approve', [AdminRequestController::class, 'approve'])->middleware('permission:requests.review')->name('requests.approve');
    Route::patch('requests/{assetRequest}/reject', [AdminRequestController::class, 'reject'])->middleware('permission:requests.review')->name('requests.reject');
    Route::patch('requests/{assetRequest}/fulfill', [AdminRequestController::class, 'fulfill'])->middleware('permission:requests.fulfill')->name('requests.fulfill');

    Route::get('asset-issues', [AssetIssueReportController::class, 'index'])->middleware('permission:assets.disposal.view')->name('asset-issues.index');
    Route::get('asset-issues/{assetIssue}', [AssetIssueReportController::class, 'show'])->middleware('permission:assets.disposal.view')->name('asset-issues.show');
    Route::patch('asset-issues/{assetIssue}/review', [AssetIssueReportController::class, 'review'])->middleware('permission:assets.disposal.request')->name('asset-issues.review');
    Route::post('asset-issues/{assetIssue}/disposal', [AssetIssueReportController::class, 'createDisposal'])->middleware('permission:assets.disposal.request')->name('asset-issues.disposal');

    Route::get('disposals', [AssetDisposalController::class, 'index'])->middleware('permission:assets.disposal.view')->name('disposals.index');
    Route::get('disposals/requests', [AssetDisposalController::class, 'requests'])->middleware('permission:assets.disposal.request')->name('disposals.requests');
    Route::get('disposals/approvals', [AssetDisposalController::class, 'approvals'])->middleware('permission:assets.disposal.approve')->name('disposals.approvals');
    Route::get('disposals/history', [AssetDisposalController::class, 'history'])->middleware('permission:assets.disposal.view')->name('disposals.history');
    Route::get('disposals/bulk', [AssetDisposalController::class, 'bulk'])->middleware('permission:assets.disposal.request')->name('disposals.bulk');
    Route::post('disposals/bulk', [AssetDisposalController::class, 'storeBulk'])->middleware('permission:assets.disposal.request')->name('disposals.bulk.store');
    Route::get('disposals/create', [AssetDisposalController::class, 'create'])->middleware('permission:assets.disposal.request')->name('disposals.create');
    Route::post('disposals', [AssetDisposalController::class, 'store'])->middleware('permission:assets.disposal.request')->name('disposals.store');
    Route::get('disposals/{disposal}', [AssetDisposalController::class, 'show'])->middleware('permission:assets.disposal.view')->name('disposals.show');
    Route::patch('disposals/{disposal}/approve', [AssetDisposalController::class, 'approve'])->middleware('permission:assets.disposal.approve')->name('disposals.approve');
    Route::patch('disposals/{disposal}/reject', [AssetDisposalController::class, 'reject'])->middleware('permission:assets.disposal.approve')->name('disposals.reject');
    Route::patch('disposals/{disposal}/complete', [AssetDisposalController::class, 'complete'])->middleware('permission:assets.disposal.complete')->name('disposals.complete');
    Route::patch('disposals/{disposal}/cancel', [AssetDisposalController::class, 'cancel'])->middleware('permission:assets.disposal.request')->name('disposals.cancel');

    Route::resource('facilities', FacilityController::class)->middleware('permission:facilities.manage');
    Route::post('facilities/{facility}/locations', [FacilityController::class, 'storeLocation'])->middleware('permission:facilities.manage')->name('facilities.locations.store');
    Route::patch('facilities/{facility}/locations/{location}', [FacilityController::class, 'updateLocation'])->middleware('permission:facilities.manage')->name('facilities.locations.update');
    Route::delete('facilities/{facility}/locations/{location}', [FacilityController::class, 'destroyLocation'])->middleware('permission:facilities.manage')->name('facilities.locations.destroy');

    Route::get('departments', [DepartmentController::class, 'index'])->middleware('permission:departments.manage')->name('departments.index');
    Route::post('departments', [DepartmentController::class, 'store'])->middleware('permission:departments.manage')->name('departments.store');
    Route::patch('departments/{department}', [DepartmentController::class, 'update'])->middleware('permission:departments.manage')->name('departments.update');
    Route::delete('departments/{department}', [DepartmentController::class, 'destroy'])->middleware('permission:departments.manage')->name('departments.destroy');

    Route::get('users', [AdminUserController::class, 'index'])->middleware('permission:users.manage')->name('users.index');
    Route::post('users', [AdminUserController::class, 'store'])->middleware('permission:users.manage')->name('users.store');
    Route::patch('users/{user}', [AdminUserController::class, 'update'])->middleware('permission:users.manage')->name('users.update');
    Route::delete('users/{user}', [AdminUserController::class, 'destroy'])->middleware('permission:users.manage')->name('users.destroy');

    Route::get('roles', [AdminRoleController::class, 'index'])->middleware('permission:roles.manage')->name('roles.index');
    Route::post('roles', [AdminRoleController::class, 'store'])->middleware('permission:roles.manage')->name('roles.store');
    Route::patch('roles/{role}', [AdminRoleController::class, 'update'])->middleware('permission:roles.manage')->name('roles.update');
    Route::delete('roles/{role}', [AdminRoleController::class, 'destroy'])->middleware('permission:roles.manage')->name('roles.destroy');

    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('assets', [ReportController::class, 'assets'])->middleware('permission:reports.view')->name('assets');
        Route::get('vendors', [ReportController::class, 'vendors'])->middleware('permission:reports.view')->name('vendors');
        Route::get('maintenance', [ReportController::class, 'maintenance'])->middleware('permission:reports.view')->name('maintenance');
        Route::get('depreciation', [ReportController::class, 'depreciation'])->middleware('permission:reports.view')->name('depreciation');
    });
    });

    // Software Asset Management
    Route::middleware('module:sam')->group(function () {
    Route::get('software', [AdminSoftwareController::class, 'index'])->name('software.index');
    Route::get('software/create', [AdminSoftwareController::class, 'create'])->name('software.create');
    Route::post('software', [AdminSoftwareController::class, 'store'])->name('software.store');
    Route::get('software/{software}', [AdminSoftwareController::class, 'show'])->name('software.show');
    Route::get('software/{software}/edit', [AdminSoftwareController::class, 'edit'])->name('software.edit');
    Route::put('software/{software}', [AdminSoftwareController::class, 'update'])->name('software.update');
    Route::delete('software/{software}', [AdminSoftwareController::class, 'destroy'])->name('software.destroy');
    Route::get('software-policies', [SoftwarePolicyController::class, 'index'])->middleware('permission:software.policies.manage')->name('software-policies.index');
    Route::patch('software-policies/{software}', [SoftwarePolicyController::class, 'update'])->middleware('permission:software.policies.manage')->name('software-policies.update');
    Route::post('software-policies/{software}/remediation-tasks', [SoftwarePolicyController::class, 'createRemediationTasks'])->middleware('permission:software.policies.manage')->name('software-policies.remediation');
    Route::get('sam-audit', [SamAuditReportController::class, 'index'])->middleware('permission:software.audit.export')->name('sam-audit.index');
    Route::post('sam-audit/download', [SamAuditReportController::class, 'download'])->middleware('permission:software.audit.export')->name('sam-audit.download');

    Route::get('software-licenses', [SoftwareLicenseController::class, 'index'])->name('software-licenses.index');
    Route::get('software-licenses/renewals', [SoftwareLicenseController::class, 'renewals'])->name('software-licenses.renewals');
    Route::post('software-licenses/{softwareLicense}/renewal-plans', [SoftwareLicenseController::class, 'planRenewal'])->middleware('permission:software.manage')->name('software-licenses.renewal-plans.store');
    Route::patch('software-licenses/{softwareLicense}/renewal-plans/{decision}/complete', [SoftwareLicenseController::class, 'completeRenewal'])->middleware('permission:software.manage')->name('software-licenses.renewal-plans.complete');
    Route::patch('software-licenses/{softwareLicense}/renewal-plans/{decision}/cancel', [SoftwareLicenseController::class, 'cancelRenewalPlan'])->middleware('permission:software.manage')->name('software-licenses.renewal-plans.cancel');
    Route::get('software-licenses/create', [SoftwareLicenseController::class, 'create'])->name('software-licenses.create');
    Route::post('software-licenses', [SoftwareLicenseController::class, 'store'])->name('software-licenses.store');
    Route::get('software-licenses/{softwareLicense}', [SoftwareLicenseController::class, 'show'])->name('software-licenses.show');
    Route::post('software-licenses/{softwareLicense}/assign', [SoftwareLicenseController::class, 'assign'])->name('software-licenses.assign');
    Route::patch('software-licenses/{softwareLicense}/assignments/{assignment}/return', [SoftwareLicenseController::class, 'returnLicense'])->name('software-licenses.return');
    Route::delete('software-licenses/{softwareLicense}', [SoftwareLicenseController::class, 'destroy'])->name('software-licenses.destroy');

    Route::get('software-requests', [SoftwareRequestController::class, 'index'])->middleware('permission:software.requests.view')->name('software-requests.index');
    Route::get('software-requests/{softwareRequest}', [SoftwareRequestController::class, 'show'])->middleware('permission:software.requests.view')->name('software-requests.show');
    Route::patch('software-requests/{softwareRequest}/approve', [SoftwareRequestController::class, 'approve'])->middleware('permission:software.requests.review')->name('software-requests.approve');
    Route::patch('software-requests/{softwareRequest}/reject', [SoftwareRequestController::class, 'reject'])->middleware('permission:software.requests.review')->name('software-requests.reject');
    Route::post('software-requests/{softwareRequest}/fulfill', [SoftwareRequestController::class, 'fulfill'])->middleware('permission:software.requests.fulfill')->name('software-requests.fulfill');
    Route::get('software-optimization', [SoftwareOptimizationController::class, 'index'])->middleware('permission:software.optimization.view')->name('software-optimization.index');
    Route::post('software-optimization/assignments/{assignment}/reviews', [SoftwareOptimizationController::class, 'startReview'])->middleware('permission:software.optimization.manage')->name('software-optimization.reviews.store');
    Route::patch('software-optimization/reviews/{review}/retain', [SoftwareOptimizationController::class, 'retain'])->middleware('permission:software.optimization.manage')->name('software-optimization.reviews.retain');
    Route::patch('software-optimization/reviews/{review}/reclaim', [SoftwareOptimizationController::class, 'reclaim'])->middleware('permission:software.optimization.manage')->name('software-optimization.reviews.reclaim');
    Route::get('agent-sources', [AgentSourceController::class, 'index'])->middleware('permission:software.agents.manage')->name('agent-sources.index');
    Route::get('agent-sources/windows-package', [AgentSourceController::class, 'downloadWindowsPackage'])->middleware('permission:software.agents.manage')->name('agent-sources.windows-package');
    Route::get('agent-sources/windows-installer', [AgentSourceController::class, 'downloadWindowsInstaller'])->middleware('permission:software.agents.manage')->name('agent-sources.windows-installer');
    Route::post('agent-sources/tokens', [AgentSourceController::class, 'createToken'])->middleware('permission:software.agents.manage')->name('agent-sources.tokens.store');
    Route::patch('agent-sources/tokens/{token}/revoke', [AgentSourceController::class, 'revokeToken'])->middleware('permission:software.agents.manage')->name('agent-sources.tokens.revoke');
    Route::patch('agent-sources/{deviceAgent}/credential/revoke', [AgentSourceController::class, 'revokeDeviceCredential'])->middleware('permission:software.agents.manage')->name('agent-sources.credential.revoke');
    Route::get('agent-sources/{deviceAgent}', [AgentSourceController::class, 'show'])->middleware('permission:software.agents.manage')->name('agent-sources.show');
    Route::post('agent-sources/{deviceAgent}/commands/inventory-refresh', [AgentSourceController::class, 'queueInventory'])->middleware('permission:software.agents.manage')->name('agent-sources.commands.inventory-refresh');
    Route::post('agent-sources/commands/inventory-refresh/bulk', [AgentSourceController::class, 'bulkQueueInventory'])->middleware('permission:software.agents.manage')->name('agent-sources.commands.inventory-refresh.bulk');
    Route::patch('agent-sources/{deviceAgent}/commands/{command}/cancel', [AgentSourceController::class, 'cancelCommand'])->middleware('permission:software.agents.manage')->name('agent-sources.commands.cancel');

    Route::get('software-discovery', [SoftwareDiscoveryController::class, 'index'])->name('software-discovery.index');
    Route::get('software-discovery/import', [SoftwareDiscoveryController::class, 'import'])->name('software-discovery.import');
    Route::post('software-discovery/import', [SoftwareDiscoveryController::class, 'storeImport'])->name('software-discovery.import.store');
    Route::get('software-discovery/template', [SoftwareDiscoveryController::class, 'template'])->name('software-discovery.template');
    Route::get('software-normalization', [SoftwareDiscoveryController::class, 'workbench'])->name('software-normalization.index');
    Route::patch('software-normalization/map-group', [SoftwareDiscoveryController::class, 'normalizeGroup'])->name('software-normalization.map-group');
    Route::patch('software-normalization/ignore-group', [SoftwareDiscoveryController::class, 'ignoreGroup'])->name('software-normalization.ignore-group');
    Route::patch('software-discovery/{discovery}/normalize', [SoftwareDiscoveryController::class, 'normalize'])->name('software-discovery.normalize');
    Route::patch('software-discovery/{discovery}/ignore', [SoftwareDiscoveryController::class, 'ignore'])->name('software-discovery.ignore');
    Route::get('software-compliance', [SoftwareComplianceController::class, 'index'])->name('software-compliance.index');
    Route::get('software-compliance/{software}', [SoftwareComplianceController::class, 'show'])->name('software-compliance.show');
    Route::post('software-compliance/{software}/assign-missing-license', [SoftwareComplianceController::class, 'assignMissingLicense'])->name('software-compliance.assign-missing-license');
    Route::post('software-compliance/{software}/discoveries/{discovery}/uninstall-action', [SoftwareComplianceController::class, 'createUninstallAction'])->name('software-compliance.uninstall-action');
    Route::post('software-compliance/{software}/discoveries/{discovery}/policy-exceptions', [SoftwareComplianceController::class, 'approvePolicyException'])->middleware('permission:software.policies.manage')->name('software-compliance.policy-exceptions.store');
    Route::patch('software-compliance/{software}/policy-exceptions/{exception}/revoke', [SoftwareComplianceController::class, 'revokePolicyException'])->middleware('permission:software.policies.manage')->name('software-compliance.policy-exceptions.revoke');
    Route::post('software-compliance/{software}/actions', [SoftwareComplianceController::class, 'storeAction'])->name('software-compliance.actions.store');
    Route::patch('software-compliance/{software}/actions/{action}/complete', [SoftwareComplianceController::class, 'completeAction'])->name('software-compliance.actions.complete');
    });

    // HRMS
    Route::middleware('module:hrms')->group(function () {
    Route::get('hrms', [HrmsDashboardController::class, 'index'])->middleware('permission:hrms.dashboard')->name('hrms.dashboard');
    Route::get('hrms-shifts', [HrShiftController::class, 'index'])->middleware('permission:hrms.settings')->name('hrms-shifts.index');
    Route::post('hrms-shifts', [HrShiftController::class, 'store'])->middleware('permission:hrms.settings')->name('hrms-shifts.store');
    Route::patch('hrms-shifts/{shift}', [HrShiftController::class, 'update'])->middleware('permission:hrms.settings')->name('hrms-shifts.update');
    Route::delete('hrms-shifts/{shift}', [HrShiftController::class, 'destroy'])->middleware('permission:hrms.settings')->name('hrms-shifts.destroy');
    Route::get('employees/bulk-import', [EmployeeBulkImportController::class, 'index'])->middleware('permission:employees.manage')->name('employees.bulk-import.index');
    Route::get('employees/bulk-import/template', [EmployeeBulkImportController::class, 'template'])->middleware('permission:employees.manage')->name('employees.bulk-import.template');
    Route::post('employees/bulk-import/preview', [EmployeeBulkImportController::class, 'preview'])->middleware('permission:employees.manage')->name('employees.bulk-import.preview');
    Route::post('employees/bulk-import/confirm', [EmployeeBulkImportController::class, 'import'])->middleware('permission:employees.manage')->name('employees.bulk-import.confirm');
    Route::resource('employees', EmployeeController::class)->middleware('permission:employees.manage');
    Route::post('employees/{employee}/documents', [EmployeeController::class, 'storeDocument'])->middleware('permission:employees.documents')->name('employees.documents.store');
    Route::post('employees/{employee}/document-requests', [EmployeeController::class, 'requestDocument'])->middleware('permission:employees.documents')->name('employees.document-requests.store');
    Route::patch('employees/{employee}/document-requests/{documentRequest}/approve', [EmployeeController::class, 'approveDocumentRequest'])->middleware('permission:employees.documents')->name('employees.document-requests.approve');
    Route::patch('employees/{employee}/document-requests/{documentRequest}/reject', [EmployeeController::class, 'rejectDocumentRequest'])->middleware('permission:employees.documents')->name('employees.document-requests.reject');
    Route::delete('employees/{employee}/documents/{document}', [EmployeeController::class, 'destroyDocument'])->middleware('permission:employees.documents')->name('employees.documents.destroy');
    Route::get('attendance/summary', [AdminAttendanceController::class, 'summary'])->middleware('permission:attendance.view')->name('attendance.summary');
    Route::get('attendance/summary/export', [AdminAttendanceController::class, 'exportSummary'])->middleware('permission:attendance.view')->name('attendance.summary.export');
    Route::post('attendance/summary/lock', [AdminAttendanceController::class, 'lockMonth'])->middleware('permission:attendance.manage')->name('attendance.lock');
    Route::delete('attendance/summary/lock', [AdminAttendanceController::class, 'unlockMonth'])->middleware('permission:attendance.manage')->name('attendance.unlock');
    Route::get('attendance/regularizations', [AdminAttendanceController::class, 'regularizations'])->middleware('permission:attendance.regularizations.review')->name('attendance.regularizations');
    Route::patch('attendance/regularizations/{regularization}/approve', [AdminAttendanceController::class, 'approveRegularization'])->middleware('permission:attendance.regularizations.review')->name('attendance.regularizations.approve');
    Route::patch('attendance/regularizations/{regularization}/reject', [AdminAttendanceController::class, 'rejectRegularization'])->middleware('permission:attendance.regularizations.review')->name('attendance.regularizations.reject');
    Route::get('attendance', [AdminAttendanceController::class, 'index'])->middleware('permission:attendance.view')->name('attendance.index');
    Route::get('leaves', [AdminLeaveController::class, 'index'])->middleware('permission:leaves.manage')->name('leaves.index');
    Route::patch('leaves/{leave}/approve', [AdminLeaveController::class, 'approve'])->middleware('permission:leaves.manage')->name('leaves.approve');
    Route::patch('leaves/{leave}/reject', [AdminLeaveController::class, 'reject'])->middleware('permission:leaves.manage')->name('leaves.reject');
    Route::get('leave-balances', [LeaveBalanceController::class, 'index'])->middleware('permission:leave_balances.manage')->name('leave-balances.index');
    Route::patch('leave-balances/{leaveBalance}', [LeaveBalanceController::class, 'update'])->middleware('permission:leave_balances.manage')->name('leave-balances.update');
    Route::get('hrms-settings', [HrmsSettingController::class, 'index'])->middleware('permission:hrms.settings')->name('hrms-settings.index');
    Route::put('hrms-settings/rules', [HrmsSettingController::class, 'updateRules'])->middleware('permission:hrms.settings')->name('hrms-settings.rules.update');
    Route::post('hrms-settings/leave-types', [HrmsSettingController::class, 'storeLeaveType'])->middleware('permission:hrms.settings')->name('hrms-settings.leave-types.store');
    Route::patch('hrms-settings/leave-types/{leaveType}', [HrmsSettingController::class, 'updateLeaveType'])->middleware('permission:hrms.settings')->name('hrms-settings.leave-types.update');
    Route::delete('hrms-settings/leave-types/{leaveType}', [HrmsSettingController::class, 'destroyLeaveType'])->middleware('permission:hrms.settings')->name('hrms-settings.leave-types.destroy');
    Route::post('hrms-settings/holidays', [HrmsSettingController::class, 'storeHoliday'])->middleware('permission:hrms.settings')->name('hrms-settings.holidays.store');
    Route::patch('hrms-settings/holidays/{holiday}', [HrmsSettingController::class, 'updateHoliday'])->middleware('permission:hrms.settings')->name('hrms-settings.holidays.update');
    Route::delete('hrms-settings/holidays/{holiday}', [HrmsSettingController::class, 'destroyHoliday'])->middleware('permission:hrms.settings')->name('hrms-settings.holidays.destroy');
    });

    // Payroll
    Route::middleware('module:payroll')->group(function () {
    Route::get('payroll', [PayrollController::class, 'index'])->middleware('permission:payroll.setup')->name('payroll.index');
    Route::get('payroll/runs', [PayrollController::class, 'runs'])->middleware('permission:payroll.run')->name('payroll.runs');
    Route::post('payroll/runs', [PayrollController::class, 'generateRun'])->middleware('permission:payroll.run')->name('payroll.runs.generate');
    Route::get('payroll/runs/{run}', [PayrollController::class, 'showRun'])->middleware('permission:payroll.run')->name('payroll.runs.show');
    Route::get('payroll/runs/{run}/export', [PayrollController::class, 'exportRun'])->middleware('permission:payroll.export')->name('payroll.runs.export');
    Route::get('payroll/runs/{run}/bank-payment', [PayrollController::class, 'exportBankPayment'])->middleware('permission:payroll.export')->name('payroll.runs.bank-payment');
    Route::patch('payroll/runs/{run}/approve', [PayrollController::class, 'approveRun'])->middleware('permission:payroll.approve')->name('payroll.runs.approve');
    Route::patch('payroll/runs/{run}/paid', [PayrollController::class, 'markRunPaid'])->middleware('permission:payroll.pay')->name('payroll.runs.paid');
    Route::get('payroll/runs/{run}/items/{item}/payslip', [PayrollController::class, 'payslip'])->middleware('permission:payroll.export')->name('payroll.payslip');
    Route::delete('payroll/runs/{run}', [PayrollController::class, 'destroyRun'])->middleware('permission:payroll.run')->name('payroll.runs.destroy');
    Route::post('payroll/components', [PayrollController::class, 'storeComponent'])->middleware('permission:payroll.setup')->name('payroll.components.store');
    Route::patch('payroll/components/{component}', [PayrollController::class, 'updateComponent'])->middleware('permission:payroll.setup')->name('payroll.components.update');
    Route::delete('payroll/components/{component}', [PayrollController::class, 'destroyComponent'])->middleware('permission:payroll.setup')->name('payroll.components.destroy');
    Route::post('payroll/structures', [PayrollController::class, 'storeStructure'])->middleware('permission:payroll.setup')->name('payroll.structures.store');
    Route::delete('payroll/structures/{structure}', [PayrollController::class, 'destroyStructure'])->middleware('permission:payroll.setup')->name('payroll.structures.destroy');
    });

    // Asset Catalog (Categories, Brands, Models)
    Route::middleware('module:itam')->group(function () {
    Route::get('catalog',                                    [CatalogController::class, 'index'])->middleware('permission:assets.catalog')->name('catalog.index');
    Route::get('catalog/model-specs/{assetModel}',           [CatalogController::class, 'modelSpecs'])->middleware('permission:assets.catalog')->name('catalog.model-specs');
    Route::get('catalog/category-specs/{category}',          [CatalogController::class, 'categorySpecs'])->middleware('permission:assets.catalog')->name('catalog.category-specs');
    Route::post('catalog/categories',                        [CatalogController::class, 'storeCategory'])->middleware('permission:assets.catalog')->name('catalog.categories.store');
    Route::patch('catalog/categories/{category}',            [CatalogController::class, 'updateCategory'])->middleware('permission:assets.catalog')->name('catalog.categories.update');
    Route::delete('catalog/categories/{category}',           [CatalogController::class, 'destroyCategory'])->middleware('permission:assets.catalog')->name('catalog.categories.destroy');
    Route::post('catalog/brands',                            [CatalogController::class, 'storeBrand'])->middleware('permission:assets.catalog')->name('catalog.brands.store');
    Route::patch('catalog/brands/{brand}',                   [CatalogController::class, 'updateBrand'])->middleware('permission:assets.catalog')->name('catalog.brands.update');
    Route::delete('catalog/brands/{brand}',                  [CatalogController::class, 'destroyBrand'])->middleware('permission:assets.catalog')->name('catalog.brands.destroy');
    Route::post('catalog/models',                            [CatalogController::class, 'storeModel'])->middleware('permission:assets.catalog')->name('catalog.models.store');
    Route::patch('catalog/models/{assetModel}',              [CatalogController::class, 'updateModel'])->middleware('permission:assets.catalog')->name('catalog.models.update');
    Route::delete('catalog/models/{assetModel}',             [CatalogController::class, 'destroyModel'])->middleware('permission:assets.catalog')->name('catalog.models.destroy');
    });

    // Support Tickets
    Route::middleware('module:support')->group(function () {
    Route::get('tickets', [AdminTicketController::class, 'index'])->middleware('permission:tickets.manage')->name('tickets.index');
    Route::get('tickets/{ticket}', [AdminTicketController::class, 'show'])->middleware('permission:tickets.manage')->name('tickets.show');
    Route::post('tickets/{ticket}/reply', [AdminTicketController::class, 'reply'])->middleware('permission:tickets.manage')->name('tickets.reply');
    Route::patch('tickets/{ticket}/status', [AdminTicketController::class, 'updateStatus'])->middleware('permission:tickets.manage')->name('tickets.status');
    Route::patch('tickets/{ticket}/assign', [AdminTicketController::class, 'assign'])->middleware('permission:tickets.manage')->name('tickets.assign');
    });
});

// ─── Supplier Portal ──────────────────────────────────────────────────────────
Route::prefix('supplier')->name('supplier.')->middleware(['auth', 'role:supplier', 'module:supplier_portal'])->group(function () {
    Route::get('/dashboard', [SupplierDashboard::class, 'index'])->name('dashboard');

    Route::get('purchase-orders', [SupplierPOController::class, 'index'])->name('purchase-orders.index');
    Route::get('purchase-orders/{purchaseOrder}', [SupplierPOController::class, 'show'])->name('purchase-orders.show');
});

// ─── Staff ────────────────────────────────────────────────────────────────────
Route::prefix('staff')->name('staff.')->middleware(['auth', 'role:staff'])->group(function () {
    Route::get('/dashboard', [StaffDashboard::class, 'index'])->name('dashboard');

    Route::middleware('module:hrms')->group(function () {
    Route::get('hrms', [StaffHrmsDashboardController::class, 'index'])->name('hrms.dashboard');
    Route::get('profile', [StaffProfileController::class, 'show'])->name('profile.show');
    Route::post('profile/document-requests/{documentRequest}/upload', [StaffProfileController::class, 'uploadRequestedDocument'])->name('profile.document-requests.upload');

    Route::get('attendance', [StaffAttendanceController::class, 'index'])->name('attendance.index');
    Route::post('attendance/sign-in', [StaffAttendanceController::class, 'signIn'])->name('attendance.sign-in');
    Route::patch('attendance/sign-out', [StaffAttendanceController::class, 'signOut'])->name('attendance.sign-out');
    Route::post('attendance/regularizations', [StaffAttendanceController::class, 'regularize'])->name('attendance.regularizations.store');
    Route::patch('attendance/regularizations/{regularization}/cancel', [StaffAttendanceController::class, 'cancelRegularization'])->name('attendance.regularizations.cancel');
    Route::get('leaves', [StaffLeaveController::class, 'index'])->name('leaves.index');
    Route::get('leaves/create', [StaffLeaveController::class, 'create'])->name('leaves.create');
    Route::post('leaves', [StaffLeaveController::class, 'store'])->name('leaves.store');
    Route::patch('leaves/{leave}/cancel', [StaffLeaveController::class, 'cancel'])->name('leaves.cancel');
    });
    Route::middleware('module:payroll')->group(function () {
    Route::get('payslips', [StaffPayrollController::class, 'index'])->name('payslips.index');
    Route::get('payslips/{item}', [StaffPayrollController::class, 'show'])->name('payslips.show');
    });

    Route::middleware('module:itam')->group(function () {
    Route::get('my-assets', [StaffAssetController::class, 'index'])->name('my-assets.index');
    Route::post('my-assets/{assignment}/issue-report', [StaffAssetController::class, 'reportIssue'])->name('my-assets.issue-report');
    Route::patch('my-assets/{assignment}/handover', [StaffAssetController::class, 'handover'])->name('my-assets.handover');
    Route::patch('my-assets/handovers/{handover}/accept', [StaffAssetController::class, 'acceptHandover'])->name('my-assets.handovers.accept');
    Route::patch('my-assets/handovers/{handover}/reject', [StaffAssetController::class, 'rejectHandover'])->name('my-assets.handovers.reject');

    Route::get('requests', [StaffRequestController::class, 'index'])->name('requests.index');
    Route::get('requests/create', [StaffRequestController::class, 'create'])->name('requests.create');
    Route::post('requests', [StaffRequestController::class, 'store'])->name('requests.store');
    Route::get('requests/{assetRequest}', [StaffRequestController::class, 'show'])->name('requests.show');
    Route::patch('requests/{assetRequest}/cancel', [StaffRequestController::class, 'cancel'])->name('requests.cancel');
    });

    // Support Tickets
    Route::middleware('module:support')->group(function () {
    Route::get('tickets', [StaffTicketController::class, 'index'])->name('tickets.index');
    Route::get('tickets/create', [StaffTicketController::class, 'create'])->name('tickets.create');
    Route::post('tickets', [StaffTicketController::class, 'store'])->name('tickets.store');
    Route::get('tickets/{ticket}', [StaffTicketController::class, 'show'])->name('tickets.show');
    Route::post('tickets/{ticket}/reply', [StaffTicketController::class, 'reply'])->name('tickets.reply');
    Route::patch('tickets/{ticket}/close', [StaffTicketController::class, 'close'])->name('tickets.close');
    });

    // My Software
    Route::middleware('module:sam')->group(function () {
    Route::get('my-software', [StaffSoftwareController::class, 'index'])->name('my-software.index');
    Route::get('software-requests', [StaffSoftwareController::class, 'requests'])->name('software-requests.index');
    Route::get('software-requests/create', [StaffSoftwareController::class, 'createRequest'])->name('software-requests.create');
    Route::post('software-requests', [StaffSoftwareController::class, 'storeRequest'])->name('software-requests.store');
    Route::patch('software-requests/{softwareRequest}/cancel', [StaffSoftwareController::class, 'cancelRequest'])->name('software-requests.cancel');
    Route::patch('software-usage-reviews/{review}/retain', [StaffSoftwareController::class, 'retainUsage'])->name('software-usage-reviews.retain');
    Route::patch('software-usage-reviews/{review}/release', [StaffSoftwareController::class, 'releaseUsage'])->name('software-usage-reviews.release');
    });
});
