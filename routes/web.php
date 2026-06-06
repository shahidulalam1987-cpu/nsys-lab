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
use App\Http\Controllers\Admin\ExportController;
use App\Http\Controllers\Admin\InvoiceController;
use App\Http\Controllers\Client\InvoiceController as ClientInvoiceController;
use App\Http\Controllers\Admin\EmployeeController;
use App\Http\Controllers\Admin\EmployeeAssignmentController;
use App\Http\Controllers\Admin\SalaryDayController;
use App\Http\Controllers\Admin\SalaryPaymentController;
use App\Http\Controllers\Admin\SalaryMonthSheetController;
use App\Http\Controllers\Admin\EmployeePayrollController;

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
    Route::get('/admin/employee-dashboard', [AdminDashboardController::class, 'employeeDepartment']);
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

    Route::get('/admin/salary-payments', [SalaryPaymentController::class, 'index']);
    Route::get('/admin/salary-payments/pending', [SalaryPaymentController::class, 'pending']);
    Route::post('/admin/salary-payments/{id}/approve', [SalaryPaymentController::class, 'approve']);
    Route::post('/admin/salary-payments/{id}/reject', [SalaryPaymentController::class, 'reject']);
    Route::get('/admin/salary-month-sheet', [SalaryMonthSheetController::class, 'index']);
    Route::get('/admin/salary-month-sheet/export', [SalaryMonthSheetController::class, 'export']);

    Route::get('/admin/payroll', [EmployeePayrollController::class, 'index']);
    Route::get('/admin/payroll/create', [EmployeePayrollController::class, 'create']);
    Route::post('/admin/payroll', [EmployeePayrollController::class, 'store']);
    Route::get('/admin/payroll/{id}', [EmployeePayrollController::class, 'show']);
    Route::get('/admin/payroll/{id}/edit', [EmployeePayrollController::class, 'edit']);
    Route::post('/admin/payroll/{id}/update', [EmployeePayrollController::class, 'update']);
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
});

require __DIR__.'/auth.php';
