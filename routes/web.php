<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Admin\ClientController;
use App\Http\Controllers\Admin\PaymentController as AdminPaymentController;
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
use App\Http\Controllers\Admin\CampaignController;

Route::get('/', [DashboardController::class, 'index']);

Route::get('/dashboard', function () {
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
});

Route::middleware(['auth', 'admin'])->group(function () {
    Route::get('/admin/export/payments', [ExportController::class, 'paymentsCsv']);
    Route::get('/admin/export/daily-reports', [ExportController::class, 'dailyReportsCsv']);
    Route::get('/admin/dashboard', [AdminDashboardController::class, 'index']);
    Route::get('/admin/facebook-dashboard', [AdminDashboardController::class, 'facebookDashboard']);
    Route::get('/admin/tiktok', [AdminDashboardController::class, 'tiktokPlaceholder']);
    Route::get('/admin/tiktok/ad-accounts', [AdminDashboardController::class, 'tiktokPlaceholder']);
    Route::get('/admin/tiktok/pages', [AdminDashboardController::class, 'tiktokPlaceholder']);
    Route::get('/admin/tiktok/campaigns', [AdminDashboardController::class, 'tiktokPlaceholder']);
    Route::get('/admin/tiktok/daily-performance', [AdminDashboardController::class, 'tiktokPlaceholder']);
    Route::get('/admin/tiktok/analytics', [AdminDashboardController::class, 'tiktokPlaceholder']);
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
    Route::get('/admin/invoices/{id}/status/{status}', [InvoiceController::class, 'updateStatus']);

    Route::get('/admin/employees', [EmployeeController::class, 'index']);
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
    Route::post('/admin/salary-payments/{id}/approve', [SalaryPaymentController::class, 'approve']);
    Route::post('/admin/salary-payments/{id}/reject', [SalaryPaymentController::class, 'reject']);
    Route::post('/admin/salary-payments/{payment}/delete', [SalaryPaymentController::class, 'destroy']);
    Route::get('/admin/salary-month-sheet', [SalaryMonthSheetController::class, 'index']);
    Route::get('/admin/salary-month-sheet/export', [SalaryMonthSheetController::class, 'export']);
    Route::get('/admin/salary-month-sheet/export/excel', [SalaryMonthSheetController::class, 'exportExcel']);

    Route::get('/admin/payroll', [EmployeePayrollController::class, 'index']);
    Route::get('/admin/payroll/export/csv', [EmployeePayrollController::class, 'exportCsv']);
    Route::get('/admin/payroll/export/excel', [EmployeePayrollController::class, 'exportExcel']);
    Route::get('/admin/payroll/create', [EmployeePayrollController::class, 'create']);
    Route::post('/admin/payroll', [EmployeePayrollController::class, 'store']);
    Route::redirect('/admin/payroll/upcoming', '/admin/payroll?status=upcoming');
    Route::redirect('/admin/payroll/unpaid', '/admin/payroll?status=due');
    Route::get('/admin/payroll/{id}', [EmployeePayrollController::class, 'show']);
    Route::get('/admin/payroll/{id}/edit', [EmployeePayrollController::class, 'edit']);
    Route::post('/admin/payroll/{id}/update', [EmployeePayrollController::class, 'update']);
    Route::post('/admin/payroll/{payroll}/approve', [EmployeePayrollController::class, 'approve']);
    Route::post('/admin/payroll/{payroll}/mark-paid', [EmployeePayrollController::class, 'markPaid']);
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

    Route::get('/admin/facebook-cards', [FacebookCardController::class, 'index']);
    Route::get('/admin/facebook-cards/create', [FacebookCardController::class, 'create']);
    Route::post('/admin/facebook-cards', [FacebookCardController::class, 'store']);
    Route::get('/admin/facebook-cards/{card}', [FacebookCardController::class, 'show']);
    Route::get('/admin/facebook-cards/{card}/edit', [FacebookCardController::class, 'edit']);
    Route::post('/admin/facebook-cards/{card}/update', [FacebookCardController::class, 'update']);
    Route::post('/admin/facebook-cards/{card}/balance', [FacebookCardController::class, 'updateBalance']);
    Route::redirect('/admin/financial-management', '/admin/facebook-financial/funding-dashboard');
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

    Route::get('/admin/campaigns', [CampaignController::class, 'index']);
    Route::get('/admin/campaigns/create', [CampaignController::class, 'create']);
    Route::post('/admin/campaigns', [CampaignController::class, 'store']);
    Route::get('/admin/campaigns/{campaign}', [CampaignController::class, 'show']);
    Route::get('/admin/campaigns/{campaign}/edit', [CampaignController::class, 'edit']);
    Route::post('/admin/campaigns/{campaign}/update', [CampaignController::class, 'update']);
    Route::post('/admin/campaigns/{campaign}/delete', [CampaignController::class, 'destroy']);

    Route::get('/admin/payments', [AdminPaymentController::class, 'index']);
    Route::get('/admin/payments/pending', [AdminPaymentController::class, 'pending']);
    Route::post('/admin/payments/{id}/approve', [AdminPaymentController::class, 'approve']);
    Route::post('/admin/payments/{id}/reject', [AdminPaymentController::class, 'reject']);

    Route::get('/admin/daily-reports', [DailyReportController::class, 'index']);
    Route::get('/admin/daily-reports/create', [DailyReportController::class, 'create']);
    Route::post('/admin/daily-reports', [DailyReportController::class, 'store']);
    Route::get('/admin/daily-reports/{dailyReport}/edit', [DailyReportController::class, 'edit']);
    Route::post('/admin/daily-reports/{dailyReport}/update', [DailyReportController::class, 'update']);
    Route::post('/admin/daily-reports/{dailyReport}/delete', [DailyReportController::class, 'destroy']);
    Route::get('/admin/daily-reports/{dailyReport}', [DailyReportController::class, 'show']);

    Route::get('/admin/profit-history', [ProfitController::class, 'index']);
});

Route::middleware(['auth', 'client', 'client.status'])->group(function () {
    Route::get('/client/dashboard', [ClientDashboardController::class, 'index']);
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
    Route::get('/employee/work-status', [EmployeeWorkStatusPortalController::class, 'index']);
    Route::get('/employee/attendance', [EmployeeAttendancePortalController::class, 'index']);
    Route::post('/employee/attendance', [EmployeeAttendancePortalController::class, 'store']);
    Route::post('/employee/attendance/check-in', [EmployeeAttendancePortalController::class, 'checkIn']);
    Route::post('/employee/attendance/check-out', [EmployeeAttendancePortalController::class, 'checkOut']);
    Route::get('/employee/salary', [EmployeePortalController::class, 'salary']);
    Route::get('/employee/salary/{payroll}/slip', [EmployeeSalarySlipController::class, 'download']);
    Route::get('/employee/assignments', [EmployeePortalController::class, 'assignments']);
    Route::get('/employee/documents', [EmployeePortalController::class, 'documents']);
    Route::get('/employee/profile', [EmployeePortalController::class, 'profile']);
    Route::post('/employee/profile', [EmployeePortalController::class, 'updateProfile']);
    Route::post('/employee/profile/password', [EmployeePortalController::class, 'updatePassword']);
    Route::get('/employee/notices', [EmployeePortalController::class, 'notices']);
    Route::post('/employee/notices/{notice}/read', [EmployeePortalController::class, 'markNoticeRead']);
});

require __DIR__.'/auth.php';
