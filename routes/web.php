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
use App\Http\Controllers\Admin\ExportController;
use App\Http\Controllers\Admin\InvoiceController;
use App\Http\Controllers\Client\InvoiceController as ClientInvoiceController;

Route::get('/', [DashboardController::class, 'index']);

Route::get('/dashboard', function () {
    if (auth()->user()->role === 'admin') {
        return redirect('/admin/dashboard');
    }

    return redirect('/client/dashboard');
})->middleware(['auth'])->name('dashboard');

Route::get('/force-logout', function () {
    auth()->logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();

    return redirect('/login');
});

Route::middleware(['auth', 'admin'])->group(function () {
    Route::get('/admin/export/payments', [ExportController::class, 'paymentsCsv']);
    Route::get('/admin/export/daily-reports', [ExportController::class, 'dailyReportsCsv']);
    Route::get('/admin/dashboard', [AdminDashboardController::class, 'index']);
    Route::get('/admin/export/profit-history', [ExportController::class, 'profitHistoryCsv']);
    Route::get('/admin/clients/{id}/export-statement', [ExportController::class, 'clientStatementCsv']);
    Route::get('/admin/clients/{id}/statement-pdf', [ExportController::class, 'clientStatementPdf']);
    Route::get('/admin/invoices', [InvoiceController::class, 'index']);
    Route::get('/admin/invoices/create', [InvoiceController::class, 'create']);
    Route::post('/admin/invoices', [InvoiceController::class, 'store']);
    Route::get('/admin/invoices/{id}/pdf', [InvoiceController::class, 'downloadPdf']);
    Route::get('/admin/invoices/{id}/status/{status}', [InvoiceController::class, 'updateStatus']);

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
    Route::get('/client/statement', [ClientDashboardController::class, 'statement']);

    Route::get('/client/payments', [ClientPaymentController::class, 'index']);
    Route::get('/client/payments/create', [ClientPaymentController::class, 'create']);
    Route::post('/client/payments', [ClientPaymentController::class, 'store']);
    Route::get('/client/invoices', [ClientInvoiceController::class, 'index']);
    Route::get('/client/invoices/{id}/pdf', [ClientInvoiceController::class, 'downloadPdf']);
});

require __DIR__.'/auth.php';
