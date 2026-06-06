<?php

namespace App\Services;

use App\Models\Employee;
use Carbon\Carbon;

class SalaryMonthSheetService
{
    public function build(array $filters = []): array
    {
        $month = Carbon::createFromFormat('Y-m', $filters['month'] ?? now()->format('Y-m'))->startOfMonth();
        $monthEnd = $month->copy()->endOfMonth();
        $daysInMonth = $month->daysInMonth;

        $employees = Employee::with([
            'salaryDays' => function ($query) use ($month, $monthEnd) {
                $query->whereBetween('date', [$month->toDateString(), $monthEnd->toDateString()]);
            },
        ])
            ->when($filters['employee_id'] ?? null, fn ($query, $employeeId) => $query->whereKey($employeeId))
            ->whereHas('salaryDays', function ($query) use ($month, $monthEnd) {
                $query->whereBetween('date', [$month->toDateString(), $monthEnd->toDateString()]);
            })
            ->orderBy('employee_id')
            ->get();

        $rows = $employees
            ->map(function (Employee $employee) use ($daysInMonth, $month) {
                $salaryDays = $employee->salaryDays;
                $countedDays = $salaryDays->where('is_counted', true)->count();
                $nonCountedDays = $salaryDays->where('is_counted', false)->count();
                $monthlySalary = (float) $employee->monthly_salary;
                $payableSalary = round(($monthlySalary * $countedDays) / $daysInMonth, 2);

                return [
                    'employee' => $employee,
                    'client_id' => $salaryDays->first()?->client_id,
                    'month' => $month,
                    'monthly_salary' => $monthlySalary,
                    'counted_days' => $countedDays,
                    'non_counted_days' => $nonCountedDays,
                    'payable_salary' => $payableSalary,
                ];
            });

        return [
            'month' => $month,
            'rows' => $rows,
            'summary' => [
                'total_employees' => $rows->count(),
                'total_counted_days' => $rows->sum('counted_days'),
                'total_payable_salary' => $rows->sum('payable_salary'),
            ],
        ];
    }

    public function employeePayable(int $employeeId, string $month): array
    {
        $sheet = $this->build([
            'employee_id' => $employeeId,
            'month' => $month,
        ]);
        $rows = $sheet['rows'];

        return [
            'month' => $sheet['month'],
            'client_id' => $rows->first()['client_id'] ?? null,
            'payable_salary' => (float) $rows->sum('payable_salary'),
            'counted_days' => (int) $rows->sum('counted_days'),
            'non_counted_days' => (int) $rows->sum('non_counted_days'),
        ];
    }
}
