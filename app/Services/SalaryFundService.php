<?php

namespace App\Services;

use App\Models\Client;
use App\Models\SalaryDay;
use App\Models\SalaryPayment;
use Carbon\Carbon;

class SalaryFundService
{
    public function build(Client $client, ?string $month = null): array
    {
        $monthStart = Carbon::parse($month ?: now()->format('Y-m-01'))->startOfMonth();
        $monthEnd = $monthStart->copy()->endOfMonth();
        $daysInMonth = $monthStart->daysInMonth;

        $salaryDays = SalaryDay::with('employee')
            ->where('client_id', $client->id)
            ->whereBetween('date', [$monthStart->toDateString(), $monthEnd->toDateString()])
            ->orderBy('date')
            ->get();

        $employeeRows = $salaryDays
            ->groupBy('employee_id')
            ->map(function ($days) use ($daysInMonth) {
                $employee = $days->first()->employee;
                $countedDays = $days->where('is_counted', true)->count();
                $nonCountedDays = $days->where('is_counted', false)->count();
                $salary = $employee
                    ? ((float) $employee->monthly_salary / $daysInMonth) * $countedDays
                    : 0;

                return [
                    'employee' => $employee,
                    'counted_days' => $countedDays,
                    'non_counted_days' => $nonCountedDays,
                    'required_salary' => $salary,
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
            'salary_days' => $salaryDays,
            'payments' => $payments,
            'summary' => [
                'total_salary_required' => $requiredSalary,
                'paid_to_nsys' => $approvedPaid,
                'pending_payment' => $pendingPaid,
                'current_due' => max($netBalance, 0),
                'available_balance' => max($netBalance * -1, 0),
                'counted_days' => $salaryDays->where('is_counted', true)->count(),
                'non_counted_days' => $salaryDays->where('is_counted', false)->count(),
            ],
        ];
    }
}
