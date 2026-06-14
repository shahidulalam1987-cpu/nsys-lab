<?php

namespace App\Services;

use App\Models\EmployeePayroll;
use Illuminate\Support\Collection;

class SalaryStatementService
{
    public function data(EmployeePayroll $payroll): array
    {
        $payroll->loadMissing(['employee.shift', 'client', 'approver', 'payer', 'financeAccount']);

        $employee = $payroll->employee;
        $adjustments = collect($payroll->salary_day_adjustments ?? []);
        $summary = $this->workStatusSummary($payroll, $adjustments);
        $monthlySalary = (float) ($employee?->monthly_salary ?? 0);
        $monthDays = (int) ($payroll->month_days ?: EmployeePayroll::FIXED_SALARY_MONTH_DAYS);
        $dailySalary = (float) ($payroll->daily_salary ?: ($monthDays > 0 ? round($monthlySalary / $monthDays, 2) : 0));
        $workingDays = (float) ($payroll->working_days ?? $summary['working_days']);
        $reference = $this->reference($payroll);

        return [
            'payroll' => $payroll,
            'employee' => $employee,
            'adjustments' => $adjustments,
            'summary' => $summary,
            'reference' => $reference,
            'monthlySalary' => $monthlySalary,
            'monthDays' => $monthDays,
            'dailySalary' => $dailySalary,
            'workingDays' => $workingDays,
            'finalSalaryFormula' => round($dailySalary * $workingDays, 2),
            'remainingDue' => max((float) $payroll->payable_salary - (float) $payroll->paid_amount, 0),
            'settlementStatus' => $payroll->settlementStatusLabel(),
            'settlementDueDate' => $payroll->salaryDueDate(),
        ];
    }

    public function reference(EmployeePayroll $payroll): string
    {
        $year = $payroll->salary_month?->format('Y')
            ?: $payroll->created_at?->format('Y')
            ?: now()->format('Y');

        return 'NSYS-PAY-' . $year . '-' . str_pad((string) $payroll->id, 5, '0', STR_PAD_LEFT);
    }

    private function workStatusSummary(EmployeePayroll $payroll, Collection $adjustments): array
    {
        $statusKey = fn (array $adjustment): string => (string) ($adjustment['reason'] ?? $adjustment['status'] ?? '');

        return [
            'working_days' => (float) ($payroll->working_days ?? $adjustments->sum(fn (array $adjustment) => (float) ($adjustment['salary_count_value'] ?? 0))),
            'half_days' => $adjustments->filter(fn (array $adjustment) => (float) ($adjustment['salary_count_value'] ?? 0) === 0.5)->count(),
            'leave_days' => $adjustments->filter(fn (array $adjustment) => in_array($statusKey($adjustment), ['on_leave', 'sick_leave'], true))->count(),
            'client_issue_days' => $adjustments->filter(fn (array $adjustment) => $statusKey($adjustment) === 'client_issue')->count(),
            'boosting_off_days' => $adjustments->filter(fn (array $adjustment) => $statusKey($adjustment) === 'boosting_off')->count(),
            'non_working_days' => (float) ($payroll->non_working_days ?? $adjustments->filter(fn (array $adjustment) => (float) ($adjustment['salary_count_value'] ?? 0) <= 0)->count()),
        ];
    }
}
