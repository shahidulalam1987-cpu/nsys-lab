<?php

namespace App\Services;

use App\Models\Client;
use App\Models\Employee;
use App\Models\EmployeePayroll;
use App\Models\EmployeeWorkStatus;
use Carbon\Carbon;

class PayrollEstimateService
{
    public function estimateCycle(Employee $employee, ?Carbon $salaryDate = null, ?Client $client = null): array
    {
        $periodEnd = $this->periodEnd($employee, $salaryDate);
        $monthlySalary = (float) $employee->monthly_salary;
        $monthDays = EmployeePayroll::FIXED_SALARY_MONTH_DAYS;
        $dailySalary = $monthDays > 0 ? $monthlySalary / $monthDays : 0.0;

        if (! $periodEnd || ! $employee->isSalaryEligible($periodEnd)) {
            return $this->emptyEstimate($monthlySalary, $monthDays, $dailySalary);
        }

        $cycleMonth = $periodEnd->copy()->startOfMonth();
        $cycleDate = $employee->salaryDateForMonth($cycleMonth);
        if ($employee->status === 'terminated' && $cycleDate?->lt($periodEnd)) {
            $cycleMonth->addMonthNoOverflow();
        }
        $cyclePeriod = app(WorkStatusCycleService::class)->period($employee, $cycleMonth->format('Y-m'));
        $periodStart = $cyclePeriod['period_start'];
        $periodEnd = $cyclePeriod['period_end'];

        $records = EmployeeWorkStatus::query()
            ->where('employee_id', $employee->id)
            ->whereDate('work_date', '>=', $periodStart->toDateString())
            ->whereDate('work_date', '<=', $periodEnd->toDateString())
            ->when($client, fn ($query) => $query->where('client_id', $client->id))
            ->when(! $client && $employee->isAgencyInternal(), fn ($query) => $query->whereNull('client_id'))
            ->get();

        $workingSalaryCount = (float) $records->sum('salary_count_value');
        $effectiveSalaryCount = EmployeePayroll::effectiveSalaryCount($workingSalaryCount);
        $nonWorkingCount = $records
            ->filter(fn (EmployeeWorkStatus $workStatus) => (float) $workStatus->salary_count_value <= 0)
            ->count();
        $estimatedPayableSalary = $effectiveSalaryCount >= $monthDays
            ? round($monthlySalary, 2)
            : min(round($dailySalary * $effectiveSalaryCount, 2), round($monthlySalary, 2));

        return [
            'salary_period_start' => $periodStart,
            'salary_period_end' => $periodEnd,
            'monthly_salary' => $monthlySalary,
            'month_days' => $monthDays,
            'daily_salary' => round($dailySalary, 2),
            'working_salary_count' => $workingSalaryCount,
            'actual_work_status_count' => $workingSalaryCount,
            'effective_salary_count' => $effectiveSalaryCount,
            'cap_applied' => EmployeePayroll::salaryCountCapApplied($workingSalaryCount),
            'non_working_count' => $nonWorkingCount,
            'estimated_payable_salary' => $estimatedPayableSalary,
            'estimate_status' => $records->isNotEmpty() ? 'based_on_work_status' : 'work_status_missing',
            'estimate_status_label' => $records->isNotEmpty() ? 'Based on Work Status' : 'Work Status Missing',
            'work_status_records' => $records->count(),
            'eligibility_label' => $this->eligibilityLabel($periodEnd, $estimatedPayableSalary, $records->count()),
        ];
    }

    public function hasWorkStatusRecordsForPeriod(Employee $employee, Carbon $periodStart, Carbon $periodEnd, ?Client $client = null): bool
    {
        if (! $employee->isSalaryEligible($periodEnd)) {
            return false;
        }

        $eligibilityDate = $employee->salaryEligibilityDate();
        if ($eligibilityDate && $eligibilityDate->gt($periodStart)) {
            $periodStart = $eligibilityDate;
        }

        return EmployeeWorkStatus::query()
            ->where('employee_id', $employee->id)
            ->whereDate('work_date', '>=', $periodStart->toDateString())
            ->whereDate('work_date', '<=', $periodEnd->toDateString())
            ->when($client, fn ($query) => $query->where('client_id', $client->id))
            ->when(! $client && $employee->isAgencyInternal(), fn ($query) => $query->whereNull('client_id'))
            ->exists();
    }

    private function periodEnd(Employee $employee, ?Carbon $salaryDate): ?Carbon
    {
        if ($employee->status === 'terminated') {
            return $employee->last_working_date?->copy();
        }

        return $salaryDate?->copy() ?: $employee->currentSalaryDueDate()?->copy();
    }

    private function emptyEstimate(float $monthlySalary, int $monthDays, float $dailySalary): array
    {
        return [
            'salary_period_start' => null,
            'salary_period_end' => null,
            'monthly_salary' => $monthlySalary,
            'month_days' => $monthDays,
            'daily_salary' => round($dailySalary, 2),
            'working_salary_count' => 0.0,
            'actual_work_status_count' => 0.0,
            'effective_salary_count' => 0.0,
            'cap_applied' => false,
            'non_working_count' => 0,
            'estimated_payable_salary' => 0.0,
            'estimate_status' => 'work_status_missing',
            'estimate_status_label' => 'Work Status Missing',
            'work_status_records' => 0,
            'eligibility_label' => 'Pending Work Status',
        ];
    }

    private function eligibilityLabel(?Carbon $salaryDate, float $estimatedPayableSalary, int $workStatusRecords): string
    {
        if ($estimatedPayableSalary > 0 && $workStatusRecords > 0) {
            return 'Salary Ready';
        }

        if (! $salaryDate) {
            return 'Pending Work Status';
        }

        $today = now()->startOfDay();

        if ($salaryDate->copy()->startOfDay()->lte($today)) {
            return 'Salary Day Reached';
        }

        return 'Upcoming Salary';
    }
}
