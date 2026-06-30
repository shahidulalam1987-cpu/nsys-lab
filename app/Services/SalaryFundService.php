<?php

namespace App\Services;

use App\Models\Client;
use App\Models\EmployeePayroll;
use App\Models\SalaryPayment;
use Carbon\Carbon;

class SalaryFundService
{
    public function build(Client $client, ?string $month = null): array
    {
        $monthStart = Carbon::parse($month ?: now()->format('Y-m-01'))->startOfMonth();
        $monthEnd = $monthStart->copy()->endOfMonth();
        $payrolls = EmployeePayroll::current()
            ->with('employee')
            ->where('client_id', $client->id)
            ->whereDate('salary_month', $monthStart->toDateString())
            ->orderBy('employee_id')
            ->get();

        $employeeRows = $payrolls
            ->groupBy('employee_id')
            ->map(function ($rows) {
                return [
                    'employee' => $rows->first()->employee,
                    'counted_days' => (float) $rows->sum('working_days'),
                    'non_counted_days' => (float) $rows->sum('non_working_days'),
                    'required_salary' => (float) $rows->sum('payable_salary'),
                ];
            })
            ->values();

        $payments = SalaryPayment::where('client_id', $client->id)
            ->whereBetween('salary_month', [$monthStart->toDateString(), $monthEnd->toDateString()])
            ->latest()
            ->get();

        $approvedPaid = (float) $payments->where('status', 'approved')->sum('amount');
        $pendingPaid = (float) $payments->where('status', 'pending')->sum('amount');
        $requiredSalary = (float) $employeeRows->sum('required_salary');
        $netBalance = $requiredSalary - $approvedPaid;

        return [
            'month' => $monthStart,
            'employee_rows' => $employeeRows,
            'salary_days' => collect(),
            'payments' => $payments,
            'summary' => [
                'total_salary_required' => $requiredSalary,
                'paid_to_nsys' => $approvedPaid,
                'pending_payment' => $pendingPaid,
                'current_due' => max($netBalance, 0),
                'available_balance' => max($netBalance * -1, 0),
                'counted_days' => (float) $payrolls->sum('working_days'),
                'non_counted_days' => (float) $payrolls->sum('non_working_days'),
            ],
        ];
    }
}
