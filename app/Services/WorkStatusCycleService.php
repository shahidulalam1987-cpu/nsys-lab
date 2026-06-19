<?php

namespace App\Services;

use App\Models\Employee;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

class WorkStatusCycleService
{
    public function period(Employee $employee, string $salaryMonth): array
    {
        $month = Carbon::createFromFormat('Y-m', $salaryMonth)->startOfMonth();
        $confirmationDate = $employee->salaryEligibilityDate();
        $salaryDay = $employee->salaryCycleDay();

        if (! $confirmationDate || ! $salaryDay) {
            throw ValidationException::withMessages([
                'employee_id' => 'Employee confirmation date and salary day are required for Monthly Cycle entry.',
            ]);
        }

        $cycleDate = $this->cycleDate($month, $salaryDay);
        $monthStart = $month->copy()->startOfMonth();
        $periodStart = $confirmationDate->gt($monthStart) ? $confirmationDate->copy() : $monthStart;

        $periodEnd = $cycleDate->copy();
        if ($employee->last_working_date && $employee->last_working_date->lt($periodEnd)) {
            $periodEnd = $employee->last_working_date->copy()->startOfDay();
        }

        if ($confirmationDate->gt($periodEnd) || $periodStart->gt($periodEnd)) {
            throw ValidationException::withMessages([
                'salary_month' => 'No salary-eligible dates exist for this employee in the selected cycle.',
            ]);
        }

        return [
            'salary_month' => $month,
            'salary_cycle_date' => $cycleDate,
            'period_start' => $periodStart,
            'period_end' => $periodEnd,
        ];
    }

    public function dates(Employee $employee, string $salaryMonth): array
    {
        $period = $this->period($employee, $salaryMonth);
        $dates = [];

        for ($date = $period['period_start']->copy(); $date->lte($period['period_end']); $date->addDay()) {
            $dates[] = $date->toDateString();
        }

        return $dates;
    }

    private function cycleDate(Carbon $month, int $salaryDay): Carbon
    {
        return $month->copy()->day(min($salaryDay, $month->copy()->endOfMonth()->day))->startOfDay();
    }
}
