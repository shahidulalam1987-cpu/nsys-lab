<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\EmployeePayroll;
use App\Models\SalaryPayment;

class ClientFundController extends Controller
{
    public function dashboard()
    {
        $clients = Client::with(['salaryPayments', 'employeePayrolls'])
            ->orderBy('company_name')
            ->get();

        $rows = $clients->map(function (Client $client) {
            $fundReceived = (float) $client->salaryPayments
                ->where('status', 'approved')
                ->sum('amount');
            $pendingPayments = (float) $client->salaryPayments
                ->where('status', 'pending')
                ->sum('amount');
            $pendingPaymentCount = $client->salaryPayments
                ->where('status', 'pending')
                ->count();
            $salaryUsed = (float) $client->employeePayrolls->sum('paid_amount');
            $unpaidSalaryDue = (float) $client->employeePayrolls->sum(
                fn (EmployeePayroll $payroll) => in_array($payroll->calculated_status, ['unpaid', 'partial'], true)
                    ? max((float) $payroll->payable_salary - (float) $payroll->paid_amount, 0)
                    : 0
            );

            return [
                'client' => $client,
                'fund_received' => $fundReceived,
                'salary_used' => $salaryUsed,
                'available_balance' => $fundReceived - $salaryUsed,
                'pending_payments' => $pendingPayments,
                'pending_payment_count' => $pendingPaymentCount,
                'unpaid_salary_due' => $unpaidSalaryDue,
            ];
        });

        $totalFundReceived = (float) SalaryPayment::where('status', 'approved')->sum('amount');
        $totalSalaryUsed = (float) EmployeePayroll::whereNotNull('client_id')->sum('paid_amount');

        return view('admin.client-fund.dashboard', [
            'rows' => $rows,
            'summary' => [
                'total_fund_received' => $totalFundReceived,
                'total_salary_used' => $totalSalaryUsed,
                'available_balance' => $totalFundReceived - $totalSalaryUsed,
                'pending_client_payments' => (float) SalaryPayment::where('status', 'pending')->sum('amount'),
                'pending_client_payment_count' => SalaryPayment::where('status', 'pending')->count(),
                'unpaid_salary_due' => (float) EmployeePayroll::with('employee')
                    ->whereNotNull('client_id')
                    ->get()
                    ->sum(fn (EmployeePayroll $payroll) => in_array($payroll->calculated_status, ['unpaid', 'partial'], true)
                        ? max((float) $payroll->payable_salary - (float) $payroll->paid_amount, 0)
                        : 0),
            ],
        ]);
    }
}
