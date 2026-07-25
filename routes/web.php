<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Admin\ClientController;
use App\Http\Controllers\Admin\DailyReportController;
use App\Http\Controllers\Admin\ProfitController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\ClientDetailsController;
use App\Http\Controllers\Admin\EditClientController;
use App\Http\Controllers\Admin\ClientUserController;
use App\Http\Controllers\Client\PaymentController as ClientPaymentController;
use App\Http\Controllers\Client\DashboardController as ClientDashboardController;
use App\Http\Controllers\Client\EmployeeController as ClientEmployeeController;
use App\Http\Controllers\Employee\DashboardController as EmployeeDashboardController;
use App\Http\Controllers\Employee\AttendanceController as EmployeeAttendancePortalController;
use App\Http\Controllers\Employee\PortalController as EmployeePortalController;
use App\Http\Controllers\Employee\WorkStatusController as EmployeeWorkStatusPortalController;
use App\Http\Controllers\Employee\SalarySlipController as EmployeeSalarySlipController;
use App\Http\Controllers\Admin\ExportController;
use App\Http\Controllers\Admin\InvoiceController;
use App\Http\Controllers\Client\InvoiceController as ClientInvoiceController;
use App\Http\Controllers\Admin\EmployeeController;
use App\Http\Controllers\Admin\EmployeeAssignmentController;
use App\Http\Controllers\Admin\SalaryDayController;
use App\Http\Controllers\Admin\SalaryPaymentController;
use App\Http\Controllers\Admin\SalaryMonthSheetController;
use App\Http\Controllers\Admin\EmployeePayrollController;
use App\Http\Controllers\Admin\SalaryStatementController;
use App\Http\Controllers\Admin\EmployeeAttendanceController;
use App\Http\Controllers\Admin\EmployeeWorkStatusController;
use App\Http\Controllers\Admin\ClientFundController;
use App\Http\Controllers\Admin\ClientPageController;
use App\Http\Controllers\Admin\EmployeeNoticeController;
use App\Http\Controllers\Admin\BugReportController;
use App\Http\Controllers\Admin\SystemToolsController;
use App\Http\Controllers\Admin\BusinessManagerController;
use App\Http\Controllers\Admin\AdAccountController;
use App\Http\Controllers\Admin\FacebookCardController;
use App\Http\Controllers\Admin\FacebookFinancialController;
use App\Http\Controllers\Admin\FinanceManagementController;
use App\Http\Controllers\Admin\NotificationCenterController;
use App\Http\Controllers\Admin\CampaignController;
use App\Http\Controllers\Admin\DepartmentController;
use App\Http\Controllers\Admin\EmployeeRoleController;
use App\Http\Controllers\Admin\EmployeeDailySubmissionController as AdminEmployeeDailySubmissionController;
use App\Http\Controllers\Employee\DailySubmissionController as EmployeeDailySubmissionController;
use App\Http\Controllers\Admin\PerformanceVerificationController;
use App\Http\Controllers\Admin\EmployeeKpiController;
use App\Http\Controllers\Admin\LeaderboardController;
use App\Http\Controllers\Admin\PerformanceTargetController;
use App\Http\Controllers\Admin\BonusController;
use App\Http\Controllers\Admin\ExecutivePerformanceController;
use App\Http\Controllers\Admin\AutomationController;
use App\Http\Controllers\Admin\DocumentManagementController;
use App\Http\Controllers\Admin\MarketingOperationsController;
use App\Http\Controllers\Admin\MigrationGapController;
use App\Http\Controllers\Employee\PerformanceController as EmployeePerformanceController;

Route::get('/', [DashboardController::class, 'index']);

Route::get('/dashboard', function () {
    if (auth()->user()->hasRole('moderator')) {
        return redirect('/admin/dashboard');
    }

    if (auth()->user()->role === 'admin') {
        return redirect('/admin/dashboard');
    }

    if (auth()->user()->role === 'employee') {
        return redirect('/employee/dashboard');
    }

    return redirect('/client/dashboard');
})->middleware(['auth'])->name('dashboard');

Route::get('/force-logout', function () {
    auth()->logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();

    return redirect('/login');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [\App\Http\Controllers\ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [\App\Http\Controllers\ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [\App\Http\Controllers\ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::get('/documents/{document}/download', [DocumentManagementController::class, 'download']);
    Route::get('/documents/{document}/preview', [DocumentManagementController::class, 'preview']);
});

Route::middleware(['auth', 'admin', 'department.permission'])->group(function () {
    Route::get('/admin/export/payments', [ExportController::class, 'paymentsCsv']);
    Route::get('/admin/export/daily-reports', [ExportController::class, 'dailyReportsCsv']);
    Route::get('/admin/dashboard', [AdminDashboardController::class, 'index']);
    Route::get('/admin/notifications', [NotificationCenterController::class, 'index']);
    Route::post('/admin/notifications/{notification}/status', [NotificationCenterController::class, 'updateStatus']);
    Route::get('/admin/automation', [AutomationController::class, 'index']);
    Route::post('/admin/automation/tasks/{task}/complete', [AutomationController::class, 'complete']);
    Route::get('/admin/documents', [DocumentManagementController::class, 'index']);
    Route::get('/admin/documents/create', [DocumentManagementController::class, 'create']);
    Route::post('/admin/documents', [DocumentManagementController::class, 'store']);
    Route::get('/admin/documents/{document}', [DocumentManagementController::class, 'show']);
    Route::get('/admin/documents/{document}/edit', [DocumentManagementController::class, 'edit']);
    Route::post('/admin/documents/{document}/update', [DocumentManagementController::class, 'update']);
    Route::post('/admin/documents/{document}/version', [DocumentManagementController::class, 'version']);
    Route::post('/admin/documents/{document}/archive', [DocumentManagementController::class, 'archive']);
    Route::post('/admin/documents/{document}/restore', [DocumentManagementController::class, 'restore']);
    Route::get('/admin/documents/{document}/download', [DocumentManagementController::class, 'download']);
    Route::get('/admin/documents/{document}/preview', [DocumentManagementController::class, 'preview']);
    Route::get('/admin/documents/{document}/versions/{version}/download', [DocumentManagementController::class, 'downloadVersion']);
    Route::get('/admin/documents/{document}/versions/{version}/preview', [DocumentManagementController::class, 'previewVersion']);
    Route::get('/admin/facebook-dashboard', [AdminDashboardController::class, 'facebookDashboard']);
    Route::get('/admin/marketing-operations', [MarketingOperationsController::class, 'dashboard']);
    Route::get('/admin/marketing-operations/agency', [MarketingOperationsController::class, 'agency']);
    Route::get('/admin/marketing-operations/settings', [MarketingOperationsController::class, 'settings']);
    Route::post('/admin/marketing-operations/settings', [MarketingOperationsController::class, 'updateSettings']);
    Route::get('/admin/marketing-operations/{module}/operations', [MarketingOperationsController::class, 'enterpriseIndex']);
    Route::get('/admin/marketing-operations/{module}/operations/create', [MarketingOperationsController::class, 'enterpriseCreate']);
    Route::post('/admin/marketing-operations/{module}/operations', [MarketingOperationsController::class, 'enterpriseStore']);
    Route::post('/admin/marketing-operations/{module}/operations/{id}/status', [MarketingOperationsController::class, 'enterpriseStatus']);
    Route::get('/admin/marketing-operations/reports', [MarketingOperationsController::class, 'reports']);
    Route::get('/admin/marketing-operations/verification', [MarketingOperationsController::class, 'verification']);
    Route::get('/admin/marketing-operations/{type}/create', [MarketingOperationsController::class, 'create']);
    Route::post('/admin/marketing-operations/{type}', [MarketingOperationsController::class, 'store']);
    Route::post('/admin/marketing-operations/reports/{report}/status', [MarketingOperationsController::class, 'updateStatus']);
    // Inactive platform guard: keep legacy TikTok URLs from showing dead placeholder screens.
    Route::any('/admin/tiktok/{path?}', fn () => abort(404, 'This platform module is not active.'))->where('path', '.*');
    Route::get('/admin/client-dashboard', [AdminDashboardController::class, 'clientDepartment']);
    Route::get('/admin/employee-dashboard', [AdminDashboardController::class, 'employeeDepartment']);
    Route::get('/admin/bug-tracker', [BugReportController::class, 'index']);
    Route::get('/admin/bug-tracker/create', [BugReportController::class, 'create']);
    Route::post('/admin/bug-tracker', [BugReportController::class, 'store']);
    Route::get('/admin/bug-tracker/{bug}/edit', [BugReportController::class, 'edit']);
    Route::post('/admin/bug-tracker/{bug}/update', [BugReportController::class, 'update']);
    Route::post('/admin/bug-tracker/{bug}/status', [BugReportController::class, 'updateStatus']);
    Route::post('/admin/bug-tracker/{bug}/delete', [BugReportController::class, 'destroy']);
    Route::get('/admin/activity-log', [SystemToolsController::class, 'activityLog']);
    Route::get('/admin/security-audit', [SystemToolsController::class, 'securityAudit']);
    Route::get('/admin/test-data-reset', [SystemToolsController::class, 'testDataReset']);
    Route::post('/admin/test-data-reset', [SystemToolsController::class, 'resetTestData']);
    Route::get('/admin/export/profit-history', [ExportController::class, 'profitHistoryCsv']);
    Route::get('/admin/clients/{id}/export-statement', [ExportController::class, 'clientStatementCsv']);
    Route::get('/admin/clients/{id}/statement-pdf', [ExportController::class, 'clientStatementPdf']);
    Route::get('/admin/invoices', [InvoiceController::class, 'index']);
    Route::get('/admin/invoices/create', [InvoiceController::class, 'create']);
    Route::post('/admin/invoices', [InvoiceController::class, 'store']);
    Route::get('/admin/invoices/{id}/pdf', [InvoiceController::class, 'downloadPdf']);
    Route::post('/admin/invoices/{id}/status/{status}', [InvoiceController::class, 'updateStatus']);

    Route::get('/admin/employees', [EmployeeController::class, 'index']);
    Route::get('/admin/departments', [DepartmentController::class, 'index']);
    Route::get('/admin/departments/create', [DepartmentController::class, 'create']);
    Route::post('/admin/departments', [DepartmentController::class, 'store']);
    Route::get('/admin/departments/{department}/edit', [DepartmentController::class, 'edit']);
    Route::put('/admin/departments/{department}', [DepartmentController::class, 'update']);
    Route::delete('/admin/departments/{department}', [DepartmentController::class, 'destroy']);
    Route::get('/admin/employee-roles', [EmployeeRoleController::class, 'index']);
    Route::get('/admin/employee-roles/create', [EmployeeRoleController::class, 'create']);
    Route::post('/admin/employee-roles', [EmployeeRoleController::class, 'store']);
    Route::get('/admin/employee-roles/{employeeRole}/edit', [EmployeeRoleController::class, 'edit']);
    Route::put('/admin/employee-roles/{employeeRole}', [EmployeeRoleController::class, 'update']);
    Route::delete('/admin/employee-roles/{employeeRole}', [EmployeeRoleController::class, 'destroy']);
    Route::get('/admin/employees/create', [EmployeeController::class, 'create']);
    Route::post('/admin/employees', [EmployeeController::class, 'store']);
    Route::get('/admin/employees/{employee}/create-login', [EmployeeController::class, 'createLogin']);
    Route::post('/admin/employees/{employee}/create-login', [EmployeeController::class, 'storeLogin']);
    Route::get('/admin/employees/{employee}/reset-login-password', [EmployeeController::class, 'resetLoginPassword']);
    Route::post('/admin/employees/{employee}/reset-login-password', [EmployeeController::class, 'updateLoginPassword']);
    Route::get('/admin/employees/{employee}/salary-ledger/export/csv', [EmployeeController::class, 'salaryLedgerCsv']);
    Route::get('/admin/employees/{employee}/salary-ledger/export/excel', [EmployeeController::class, 'salaryLedgerExcel']);
    Route::get('/admin/employees/{id}', [EmployeeController::class, 'show']);
    Route::get('/admin/employees/{id}/edit', [EmployeeController::class, 'edit']);
    Route::post('/admin/employees/{id}/update', [EmployeeController::class, 'update']);
    Route::post('/admin/employees/{id}/confirm', [EmployeeController::class, 'confirm']);
    Route::post('/admin/employees/{employee}/terminate', [EmployeeController::class, 'terminate']);
    Route::post('/admin/employees/{employee}/delete', [EmployeeController::class, 'destroy']);
    Route::post('/admin/employees/{employee}/assignments', [EmployeeAssignmentController::class, 'store']);
    Route::post('/admin/employee-assignments/{assignment}/update', [EmployeeAssignmentController::class, 'update']);
    Route::post('/admin/employee-assignments/{assignment}/delete', [EmployeeAssignmentController::class, 'destroy']);
    Route::post('/admin/employees/{employee}/salary-days', [SalaryDayController::class, 'store']);
    Route::post('/admin/salary-days/{salaryDay}/delete', [SalaryDayController::class, 'destroy']);

    Route::get('/admin/assignments', [EmployeeAssignmentController::class, 'index']);
    Route::get('/admin/assignments/create', [EmployeeAssignmentController::class, 'create']);
    Route::post('/admin/assignments', [EmployeeAssignmentController::class, 'storeFromManagement']);
    Route::get('/admin/assignments/{assignment}', [EmployeeAssignmentController::class, 'show']);
    Route::get('/admin/assignments/{assignment}/edit', [EmployeeAssignmentController::class, 'edit']);
    Route::post('/admin/assignments/{assignment}/update', [EmployeeAssignmentController::class, 'updateFromManagement']);
    Route::post('/admin/assignments/{assignment}/end', [EmployeeAssignmentController::class, 'end']);
    Route::post('/admin/assignments/{assignment}/remove', [EmployeeAssignmentController::class, 'remove']);

    Route::get('/admin/attendance', [EmployeeAttendanceController::class, 'index']);
    Route::get('/admin/attendance/export', [EmployeeAttendanceController::class, 'export']);
    Route::get('/admin/attendance/{attendance}/edit', [EmployeeAttendanceController::class, 'edit']);
    Route::post('/admin/attendance/{attendance}/update', [EmployeeAttendanceController::class, 'update']);
    Route::post('/admin/attendance/{attendance}/delete', [EmployeeAttendanceController::class, 'destroy']);

    Route::get('/admin/work-status', [EmployeeWorkStatusController::class, 'index']);
    Route::get('/admin/work-status/create', [EmployeeWorkStatusController::class, 'create']);
    Route::post('/admin/work-status', [EmployeeWorkStatusController::class, 'store']);
    Route::get('/admin/work-status/export', [EmployeeWorkStatusController::class, 'export']);
    Route::get('/admin/work-status/{workStatus}/edit', [EmployeeWorkStatusController::class, 'edit']);
    Route::post('/admin/work-status/{workStatus}/update', [EmployeeWorkStatusController::class, 'update']);
    Route::post('/admin/work-status/{workStatus}/delete', [EmployeeWorkStatusController::class, 'destroy']);

    Route::get('/admin/client-pages', [ClientPageController::class, 'index']);
    Route::get('/admin/client-pages/create', [ClientPageController::class, 'create']);
    Route::post('/admin/client-pages', [ClientPageController::class, 'store']);
    Route::get('/admin/client-pages/{page}/edit', [ClientPageController::class, 'edit']);
    Route::post('/admin/client-pages/{page}/update', [ClientPageController::class, 'update']);
    Route::post('/admin/client-pages/{page}/delete', [ClientPageController::class, 'destroy']);

    Route::get('/admin/employee-notices', [EmployeeNoticeController::class, 'index']);
    Route::get('/admin/employee-notices/create', [EmployeeNoticeController::class, 'create']);
    Route::post('/admin/employee-notices', [EmployeeNoticeController::class, 'store']);
    Route::get('/admin/employee-notices/{notice}/edit', [EmployeeNoticeController::class, 'edit']);
    Route::post('/admin/employee-notices/{notice}/update', [EmployeeNoticeController::class, 'update']);
    Route::post('/admin/employee-notices/{notice}/delete', [EmployeeNoticeController::class, 'destroy']);

    Route::get('/admin/client-fund', [ClientFundController::class, 'dashboard']);
    Route::get('/admin/client-fund/daily-statement', [ClientFundController::class, 'dailyStatement']);
    Route::post('/admin/client-fund/daily-statement', [ClientFundController::class, 'saveDailyStatement']);
    Route::get('/admin/client-fund/export/csv', [ClientFundController::class, 'exportCsv']);
    Route::get('/admin/client-fund/export/excel', [ClientFundController::class, 'exportExcel']);
    Route::get('/admin/client-fund/{client}/details', [ClientFundController::class, 'show']);
    Route::get('/admin/client-fund/{client}/details/export/csv', [ClientFundController::class, 'exportLedgerCsv']);
    Route::get('/admin/client-fund/{client}/details/export/excel', [ClientFundController::class, 'exportLedgerExcel']);
    Route::get('/admin/client-fund/{client}', [ClientFundController::class, 'show']);
    Route::get('/admin/salary-payments', [SalaryPaymentController::class, 'index']);
    Route::get('/admin/salary-payments/create', [SalaryPaymentController::class, 'create']);
    Route::post('/admin/salary-payments', [SalaryPaymentController::class, 'store']);
    Route::get('/admin/salary-payments/pending', [SalaryPaymentController::class, 'pending']);
    Route::get('/admin/salary-payments/{payment}', [SalaryPaymentController::class, 'show']);
    Route::get('/admin/salary-payments/{payment}/receipt-pdf', [SalaryPaymentController::class, 'receiptPdf']);
    Route::post('/admin/salary-payments/{id}/approve', [SalaryPaymentController::class, 'approve']);
    Route::post('/admin/salary-payments/{id}/reject', [SalaryPaymentController::class, 'reject']);
    Route::post('/admin/salary-payments/{payment}/delete', [SalaryPaymentController::class, 'destroy']);
    Route::get('/admin/salary-month-sheet', [SalaryMonthSheetController::class, 'index']);
    Route::get('/admin/salary-month-sheet/export', [SalaryMonthSheetController::class, 'export']);
    Route::get('/admin/salary-month-sheet/export/excel', [SalaryMonthSheetController::class, 'exportExcel']);

    Route::get('/admin/payroll', [EmployeePayrollController::class, 'index']);
    Route::get('/admin/payroll/export/csv', [EmployeePayrollController::class, 'exportCsv']);
    Route::get('/admin/payroll/export/excel', [EmployeePayrollController::class, 'exportExcel']);
    Route::get('/admin/payroll/payment-report', [EmployeePayrollController::class, 'paymentReport']);
    Route::get('/admin/payroll/payment-report/export/csv', [EmployeePayrollController::class, 'paymentReportCsv']);
    Route::get('/admin/payroll/payment-report/export/excel', [EmployeePayrollController::class, 'paymentReportExcel']);
    Route::get('/admin/payroll/create', [EmployeePayrollController::class, 'create']);
    Route::post('/admin/payroll', [EmployeePayrollController::class, 'store']);
    Route::redirect('/admin/payroll/upcoming', '/admin/payroll?status=upcoming');
    Route::redirect('/admin/payroll/unpaid', '/admin/payroll?status=due');
    Route::get('/admin/payroll/{payroll}/salary-statement', [SalaryStatementController::class, 'download']);
    Route::get('/admin/payroll/{id}', [EmployeePayrollController::class, 'show']);
    Route::get('/admin/payroll/{id}/edit', [EmployeePayrollController::class, 'edit']);
    Route::post('/admin/payroll/{id}/update', [EmployeePayrollController::class, 'update']);
    Route::post('/admin/payroll/{payroll}/approve', [EmployeePayrollController::class, 'approve']);
    Route::post('/admin/payroll/{payroll}/mark-paid', [EmployeePayrollController::class, 'markPaid']);
    Route::post('/admin/payroll/{payroll}/confirm-payment', [EmployeePayrollController::class, 'confirmPayment']);
    Route::post('/admin/payroll/{payroll}/reverse-payment', [EmployeePayrollController::class, 'reversePayment']);
    Route::post('/admin/payroll/{payroll}/delete', [EmployeePayrollController::class, 'destroy']);

    Route::get('/admin/clients', [ClientController::class, 'index']);
    Route::get('/admin/clients/create', [ClientController::class, 'create']);
    Route::post('/admin/clients', [ClientController::class, 'store']);
    Route::get('/admin/clients/{id}', [ClientDetailsController::class, 'show']);
    Route::get('/admin/clients/{id}/edit', [EditClientController::class, 'edit']);
    Route::post('/admin/clients/{id}/update', [EditClientController::class, 'update']);
    Route::post('/admin/clients/{client}/delete', [ClientController::class, 'destroy']);

    Route::get('/admin/client-users', [ClientUserController::class, 'index']);
    Route::get('/admin/client-users/create', [ClientUserController::class, 'create']);
    Route::post('/admin/client-users', [ClientUserController::class, 'store']);
    Route::post('/admin/client-users/{user}/delete', [ClientUserController::class, 'destroy']);
    Route::get('/admin/client-users/{user}/reset-password', [ClientUserController::class, 'editPassword']);
    Route::post('/admin/client-users/{user}/reset-password', [ClientUserController::class, 'updatePassword']);
    Route::post('/admin/client-users/{user}/toggle-status', [ClientUserController::class, 'toggleStatus']);

    Route::get('/admin/business-managers', [BusinessManagerController::class, 'index']);
    Route::get('/admin/business-managers/create', [BusinessManagerController::class, 'create']);
    Route::post('/admin/business-managers', [BusinessManagerController::class, 'store']);
    Route::get('/admin/business-managers/{businessManager}', [BusinessManagerController::class, 'show']);
    Route::get('/admin/business-managers/{businessManager}/edit', [BusinessManagerController::class, 'edit']);
    Route::post('/admin/business-managers/{businessManager}/update', [BusinessManagerController::class, 'update']);
    Route::post('/admin/business-managers/{businessManager}/delete', [BusinessManagerController::class, 'destroy']);

    Route::get('/admin/ad-accounts', [AdAccountController::class, 'index']);
    Route::get('/admin/ad-accounts/create', [AdAccountController::class, 'create']);
    Route::post('/admin/ad-accounts', [AdAccountController::class, 'store']);
    Route::get('/admin/ad-account-ledger', [AdAccountController::class, 'ledger']);
    Route::get('/admin/ad-account-ledger/{ledger}', [AdAccountController::class, 'ledgerShow']);
    Route::get('/admin/ad-accounts/{adAccount}', [AdAccountController::class, 'show']);
    Route::get('/admin/ad-accounts/{adAccount}/edit', [AdAccountController::class, 'edit']);
    Route::post('/admin/ad-accounts/{adAccount}/update', [AdAccountController::class, 'update']);
    Route::post('/admin/ad-accounts/{adAccount}/delete', [AdAccountController::class, 'destroy']);
    Route::get('/admin/ad-account-pages', [MigrationGapController::class, 'adAccountPages']);
    Route::post('/admin/ad-account-pages', [MigrationGapController::class, 'storeAdAccountPage']);
    Route::get('/admin/ad-account-cards', [MigrationGapController::class, 'adAccountCards']);
    Route::post('/admin/ad-account-cards', [MigrationGapController::class, 'storeAdAccountCard']);
    Route::get('/admin/ad-account-billing-history', [MigrationGapController::class, 'billingHistory']);
    Route::post('/admin/ad-account-billing-history', [MigrationGapController::class, 'storeBillingHistory']);
    Route::get('/admin/datasets', [MigrationGapController::class, 'datasets']);
    Route::post('/admin/datasets', [MigrationGapController::class, 'storeDataset']);
    Route::get('/admin/meta-spend-snapshots', [MigrationGapController::class, 'metaSnapshots']);
    Route::post('/admin/meta-spend-snapshots', [MigrationGapController::class, 'storeMetaSnapshot']);
    Route::get('/admin/meta-sync-logs', [MigrationGapController::class, 'metaSyncLogs']);
    Route::post('/admin/meta-sync-logs', [MigrationGapController::class, 'storeMetaSyncLog']);
    Route::get('/admin/whatsapp-logs', [MigrationGapController::class, 'whatsAppLogs']);
    Route::post('/admin/whatsapp-logs', [MigrationGapController::class, 'storeWhatsAppLog']);

    Route::get('/admin/facebook-cards', [FacebookCardController::class, 'index']);
    Route::get('/admin/facebook-cards/create', [FacebookCardController::class, 'create']);
    Route::post('/admin/facebook-cards', [FacebookCardController::class, 'store']);
    Route::get('/admin/facebook-cards/{card}', [FacebookCardController::class, 'show']);
    Route::get('/admin/facebook-cards/{card}/edit', [FacebookCardController::class, 'edit']);
    Route::post('/admin/facebook-cards/{card}/update', [FacebookCardController::class, 'update']);
    Route::post('/admin/facebook-cards/{card}/balance', [FacebookCardController::class, 'updateBalance']);
    Route::get('/admin/financial-management', [FinanceManagementController::class, 'dashboard']);
    Route::get('/admin/finance/accounts', [FinanceManagementController::class, 'accounts']);
    Route::post('/admin/finance/accounts', [FinanceManagementController::class, 'storeAccount']);
    Route::get('/admin/finance/accounts/{account}/edit', [FinanceManagementController::class, 'editAccount']);
    Route::post('/admin/finance/accounts/{account}/update', [FinanceManagementController::class, 'updateAccount']);
    Route::post('/admin/finance/accounts/{account}/delete', [FinanceManagementController::class, 'destroyAccount']);
    // Archived finance modules: hidden from navigation but guarded for old bookmarks.
    Route::any('/admin/finance/family-expenses/{path?}', fn () => abort(404, 'This finance module is archived.'))->where('path', '.*');
    Route::any('/admin/finance/loans/{path?}', fn () => abort(404, 'This finance module is archived.'))->where('path', '.*');
    Route::get('/admin/finance/reports/balance-sheet', [FinanceManagementController::class, 'balanceSheet']);
    Route::get('/admin/finance/reports/reconciliation', [FinanceManagementController::class, 'reconciliationReport']);
    Route::get('/admin/facebook-financial/binance-purchases', [FacebookFinancialController::class, 'binancePurchases']);
    Route::post('/admin/facebook-financial/binance-purchases', [FacebookFinancialController::class, 'storeBinancePurchase']);
    Route::get('/admin/facebook-financial/card-loads', [FacebookFinancialController::class, 'cardLoads']);
    Route::post('/admin/facebook-financial/card-loads', [FacebookFinancialController::class, 'storeCardLoad']);
    Route::get('/admin/facebook-financial/card-transactions', [FacebookFinancialController::class, 'cardTransactions']);
    Route::post('/admin/facebook-financial/card-transactions', [FacebookFinancialController::class, 'storeCardTransaction']);
    Route::get('/admin/facebook-financial/funding-dashboard', [FacebookFinancialController::class, 'fundingDashboard']);
    Route::get('/admin/facebook-financial/funding-dashboard/update', [FacebookFinancialController::class, 'createFundingBalance']);
    Route::post('/admin/facebook-financial/funding-dashboard/update', [FacebookFinancialController::class, 'storeFundingBalance']);
    Route::get('/admin/facebook-financial/funding-history/{history}', [FacebookFinancialController::class, 'fundingHistoryShow']);
    Route::get('/admin/facebook-financial/profit-dashboard', [FacebookFinancialController::class, 'profitDashboard']);
    Route::get('/admin/payment-providers', [MigrationGapController::class, 'providers']);
    Route::post('/admin/payment-providers', [MigrationGapController::class, 'storeProvider']);
    Route::get('/admin/provider-transactions', [MigrationGapController::class, 'providerTransactions']);
    Route::post('/admin/provider-transactions', [MigrationGapController::class, 'storeProviderTransaction']);
    Route::get('/admin/provider-fees', [MigrationGapController::class, 'providerFees']);
    Route::post('/admin/provider-fees', [MigrationGapController::class, 'storeProviderFee']);

    Route::get('/admin/campaigns', [CampaignController::class, 'index']);
    Route::get('/admin/campaigns/create', [CampaignController::class, 'create']);
    Route::post('/admin/campaigns', [CampaignController::class, 'store']);
    Route::get('/admin/campaigns/{campaign}', [CampaignController::class, 'show']);
    Route::get('/admin/campaigns/{campaign}/edit', [CampaignController::class, 'edit']);
    Route::post('/admin/campaigns/{campaign}/update', [CampaignController::class, 'update']);
    Route::post('/admin/campaigns/{campaign}/delete', [CampaignController::class, 'destroy']);

    Route::get('/admin/payments/pending', fn () => redirect('/admin/salary-payments/pending'));
    Route::get('/admin/payments', fn () => redirect('/admin/salary-payments'));
    Route::post('/admin/payments/{id}/approve', fn () => redirect('/admin/salary-payments/pending')->with('error', 'Legacy payment approval is disabled. Use Client Payment approval.'));
    Route::post('/admin/payments/{id}/reject', fn () => redirect('/admin/salary-payments/pending')->with('error', 'Legacy payment rejection is disabled. Use Client Payment approval.'));

    Route::get('/admin/daily-reports', [DailyReportController::class, 'index']);
    Route::get('/admin/daily-reports/create', [DailyReportController::class, 'create']);
    Route::post('/admin/daily-reports', [DailyReportController::class, 'store']);
    Route::get('/admin/daily-reports/{dailyReport}/edit', [DailyReportController::class, 'edit']);
    Route::post('/admin/daily-reports/{dailyReport}/update', [DailyReportController::class, 'update']);
    Route::post('/admin/daily-reports/{dailyReport}/delete', [DailyReportController::class, 'destroy']);
    Route::get('/admin/daily-reports/{dailyReport}', [DailyReportController::class, 'show']);
    Route::get('/admin/employee-submissions', [AdminEmployeeDailySubmissionController::class, 'index']);
    Route::get('/admin/employee-submissions/{submission}/edit', [AdminEmployeeDailySubmissionController::class, 'edit']);
    Route::put('/admin/employee-submissions/{submission}', [AdminEmployeeDailySubmissionController::class, 'update']);
    Route::post('/admin/employee-submissions/{submission}/approve', [AdminEmployeeDailySubmissionController::class, 'approve']);
    Route::post('/admin/employee-submissions/{submission}/reject', [AdminEmployeeDailySubmissionController::class, 'reject']);
    Route::post('/admin/employee-submissions/{submission}/merge', [AdminEmployeeDailySubmissionController::class, 'merge']);
    Route::get('/admin/performance-verification', [PerformanceVerificationController::class, 'index']);
    Route::get('/admin/performance-verification/export', [PerformanceVerificationController::class, 'export']);
    Route::post('/admin/performance-verification/{submission}/mismatch', [PerformanceVerificationController::class, 'markMismatch']);
    Route::get('/admin/employee-kpi', [EmployeeKpiController::class, 'index']);
    Route::get('/admin/employee-kpi/export', [EmployeeKpiController::class, 'export']);
    Route::get('/admin/leaderboard', [LeaderboardController::class, 'index']);
    Route::get('/admin/leaderboard/export', [LeaderboardController::class, 'export']);
    Route::get('/admin/performance-targets', [PerformanceTargetController::class, 'index']);
    Route::post('/admin/performance-targets', [PerformanceTargetController::class, 'store']);
    Route::delete('/admin/performance-targets/{target}', [PerformanceTargetController::class, 'destroy']);
    Route::get('/admin/bonuses', [BonusController::class, 'index']);
    Route::post('/admin/bonuses/rules', [BonusController::class, 'storeRule']);
    Route::post('/admin/bonuses/rules/{rule}/evaluate', [BonusController::class, 'evaluate']);
    Route::post('/admin/bonuses/{earning}/approve', [BonusController::class, 'approve']);
    Route::post('/admin/bonuses/{earning}/reject', [BonusController::class, 'reject']);
    Route::get('/admin/bonuses/export', [BonusController::class, 'export']);
    Route::get('/admin/executive-performance', [ExecutivePerformanceController::class, 'index']);
    Route::get('/admin/executive-performance/export/{format}', [ExecutivePerformanceController::class, 'export'])
        ->whereIn('format', ['csv', 'excel', 'pdf']);

    Route::get('/admin/profit-history', [ProfitController::class, 'index']);
});

Route::middleware(['auth', 'client', 'client.status'])->group(function () {
    Route::get('/client/dashboard', [ClientDashboardController::class, 'index']);
    Route::get('/client/performance-reports', [ClientDashboardController::class, 'performanceReport']);
    Route::get('/client/employee-dashboard', [ClientDashboardController::class, 'employeeDepartment']);
    Route::get('/client/statement', [ClientDashboardController::class, 'statement']);

    Route::get('/client/payments', [ClientPaymentController::class, 'index']);
    Route::get('/client/payments/create', [ClientPaymentController::class, 'create']);
    Route::post('/client/payments', [ClientPaymentController::class, 'store']);
    Route::get('/client/invoices', [ClientInvoiceController::class, 'index']);
    Route::get('/client/invoices/{id}/pdf', [ClientInvoiceController::class, 'downloadPdf']);
    Route::get('/client/employees', [ClientEmployeeController::class, 'index']);
    Route::get('/client/salary-fund', [ClientEmployeeController::class, 'salaryFund']);
    Route::get('/client/salary-payments', [ClientEmployeeController::class, 'paymentHistory']);
    Route::get('/client/salary-payments/create', [ClientEmployeeController::class, 'createPayment']);
    Route::post('/client/salary-payments', [ClientEmployeeController::class, 'storePayment']);
});

Route::middleware(['auth', 'employee'])->group(function () {
    Route::get('/employee/dashboard', [EmployeeDashboardController::class, 'index']);
    Route::get('/employee/daily-orders', [EmployeeDailySubmissionController::class, 'orders']);
    Route::post('/employee/daily-orders', [EmployeeDailySubmissionController::class, 'storeOrder']);
    Route::get('/employee/daily-spend', [EmployeeDailySubmissionController::class, 'spend']);
    Route::post('/employee/daily-spend', [EmployeeDailySubmissionController::class, 'storeSpend']);
    Route::get('/employee/performance', [EmployeePerformanceController::class, 'index']);
    Route::get('/employee/work-status', [EmployeeWorkStatusPortalController::class, 'index']);
    Route::get('/employee/attendance', [EmployeeAttendancePortalController::class, 'index']);
    Route::post('/employee/attendance', [EmployeeAttendancePortalController::class, 'store']);
    Route::post('/employee/attendance/check-in', [EmployeeAttendancePortalController::class, 'checkIn']);
    Route::post('/employee/attendance/check-out', [EmployeeAttendancePortalController::class, 'checkOut']);
    Route::get('/employee/salary', [EmployeePortalController::class, 'salary']);
    Route::get('/employee/salary/{payroll}/slip', [EmployeeSalarySlipController::class, 'download']);
    Route::get('/employee/salary/{payroll}/statement', [EmployeeSalarySlipController::class, 'download']);
    Route::get('/employee/assignments', [EmployeePortalController::class, 'assignments']);
    Route::get('/employee/documents', [EmployeePortalController::class, 'documents']);
    Route::get('/employee/profile', [EmployeePortalController::class, 'profile']);
    Route::post('/employee/profile', [EmployeePortalController::class, 'updateProfile']);
    Route::post('/employee/profile/password', [EmployeePortalController::class, 'updatePassword']);
    Route::get('/employee/notices', [EmployeePortalController::class, 'notices']);
    Route::post('/employee/notices/{notice}/read', [EmployeePortalController::class, 'markNoticeRead']);
});

require __DIR__.'/auth.php';
