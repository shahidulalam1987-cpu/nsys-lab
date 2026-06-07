<?php

namespace App\Services;

use App\Models\EmployeePayroll;
use Carbon\Carbon;

class SalaryMonthSheetService
{
    public function build(array $filters = []): array
    {
        $month = Carbon::createFromFormat('Y-m', $filters['month'] ?? now()->format('Y-m'))->startOfMonth();
        $rows = EmployeePayroll::with(['employee', 'client'])
            ->whereDate('salary_month', $month->toDateString())
            ->when($filters['employee_id'] ?? null, fn ($query, $employeeId) => $query->where('employee_id', $employeeId))
            ->withCalculatedStatus($filters['status'] ?? null)
            ->latest('salary_month')
            ->latest()
            ->get();

        return [
            'month' => $month,
            'rows' => $rows,
            'summary' => [
                'total_salary_records' => $rows->count(),
                'total_payable_salary' => $rows->sum('payable_salary'),
                'total_paid_salary' => $rows->sum('paid_amount'),
                'total_remaining_due' => $rows->sum(fn (EmployeePayroll $payroll) => max((float) $payroll->payable_salary - (float) $payroll->paid_amount, 0)),
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
            'client_id' => $rows->first()?->client_id,
            'payable_salary' => (float) $rows->sum('payable_salary'),
            'counted_days' => (int) $rows->sum('working_days'),
            'non_counted_days' => (int) $rows->sum('non_working_days'),
        ];
    }
}
