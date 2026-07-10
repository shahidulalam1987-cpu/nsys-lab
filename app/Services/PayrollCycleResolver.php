<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\EmployeePayroll;
use Carbon\Carbon;

class PayrollCycleResolver
{
    public function resolvePayrollCycle(EmployeePayroll $payroll): array
    {
        $payroll->loadMissing('employee');
        $employee = $payroll->employee;

        if (! $employee) {
            return $this->emptyCycle($payroll, 'missing_employee');
        }

        if ($payroll->isFinalSettlementPayroll()) {
            return $this->finalSettlementCycle($payroll, $employee);
        }

        if ($payroll->cycle_due_date) {
            return $this->cycleFromOfficialDate($payroll, $employee, $payroll->cycle_due_date, 'cycle_due_date');
        }

        if ($this->hasExplicitNormalizedMetadata($payroll)) {
            return $this->cycleFromPeriod($payroll, $employee, 'explicit_normalized_metadata');
        }

        if ($cycle = $this->historicalPaidPayrollCycle($payroll, $employee)) {
            return $cycle;
        }

        if ($cycle = $this->legacyCompatibilityCycle($payroll, $employee)) {
            return $cycle;
        }

        return $this->cycleFromPeriod($payroll, $employee, 'period_fallback');
    }

    public function matchesSalaryCycleDate(EmployeePayroll $payroll, Carbon $calculatedSalaryDate): bool
    {
        if ($payroll->is_current === false || $payroll->superseded_by_id || $payroll->reversed_at) {
            return false;
        }

        if ($this->usesLegacyHandledSalaryMonth($payroll)) {
            return $payroll->salary_month->isSameMonth($calculatedSalaryDate);
        }

        $cycle = $this->resolvePayrollCycle($payroll);
        $officialSalaryDate = $cycle['official_salary_date'] ?? null;

        if ($officialSalaryDate instanceof Carbon && $officialSalaryDate->isSameDay($calculatedSalaryDate)) {
            return true;
        }

        return $payroll->salary_month?->isSameMonth($calculatedSalaryDate) ?? false;
    }

    private function usesLegacyHandledSalaryMonth(EmployeePayroll $payroll): bool
    {
        if (! $payroll->salary_month || ! $payroll->salary_period_to) {
            return false;
        }

        if (EmployeePayroll::statusFor((float) $payroll->payable_salary, (float) $payroll->paid_amount) !== 'paid') {
            return false;
        }

        $handledAt = $payroll->payment_confirmed_at
            ?: $payroll->paid_at
            ?: $payroll->payment_date;

        return $handledAt
            && $payroll->salary_month->isSameMonth($payroll->salary_period_to)
            && $handledAt->copy()->startOfDay()->lte($payroll->salary_period_to->copy()->endOfDay());
    }

    public function latestCompletedCycle(Employee $employee, Carbon $beforeDate): ?array
    {
        $beforeDate = $beforeDate->copy()->startOfDay();

        return $employee->payrolls()
            ->current()
            ->get()
            ->filter(fn (EmployeePayroll $payroll) => ! $payroll->isFinalSettlementPayroll())
            ->map(fn (EmployeePayroll $payroll) => $this->resolvePayrollCycle($payroll))
            ->filter(function (array $cycle) use ($beforeDate) {
                $cycleEnd = $cycle['cycle_end'] ?? null;

                return $cycleEnd instanceof Carbon && $cycleEnd->lt($beforeDate);
            })
            ->sortByDesc(fn (array $cycle) => $cycle['cycle_end']->timestamp)
            ->first();
    }

    public function latestCompletedCycleEnd(Employee $employee, Carbon $beforeDate): ?Carbon
    {
        return ($this->latestCompletedCycle($employee, $beforeDate)['cycle_end'] ?? null)?->copy();
    }

    public function settlementBoundary(Employee $employee): ?array
    {
        $confirmationDate = $employee->salaryEligibilityDate();
        $lastWorkingDate = $employee->last_working_date?->copy()->startOfDay();

        if (! $confirmationDate || ! $lastWorkingDate) {
            return null;
        }

        $latestCycle = $this->latestCompletedCycle($employee, $lastWorkingDate);
        $latestCycleEnd = $latestCycle['cycle_end'] ?? null;
        $periodStart = $latestCycleEnd instanceof Carbon && $latestCycleEnd->gte($confirmationDate)
            ? $latestCycleEnd->copy()->addDay()
            : $confirmationDate->copy();

        return [
            'period_start' => $periodStart,
            'period_end' => $lastWorkingDate,
            'latest_completed_cycle' => $latestCycle,
        ];
    }

    private function hasExplicitNormalizedMetadata(EmployeePayroll $payroll): bool
    {
        return (bool) $payroll->cycle_key
            && $payroll->salary_period_from
            && $payroll->salary_period_to;
    }

    private function historicalPaidPayrollCycle(EmployeePayroll $payroll, Employee $employee): ?array
    {
        if (EmployeePayroll::statusFor((float) $payroll->payable_salary, (float) $payroll->paid_amount) !== 'paid') {
            return null;
        }

        if (! $payroll->salary_period_from || ! $payroll->salary_period_to) {
            return null;
        }

        $handledAt = $payroll->payment_confirmed_at
            ?: $payroll->paid_at
            ?: $payroll->payment_date;

        if ($handledAt && $payroll->salary_period_to->copy()->startOfDay()->gt($handledAt->copy()->startOfDay())) {
            return null;
        }

        return $this->cycleFromPeriod($payroll, $employee, 'historical_paid_period');
    }

    private function legacyCompatibilityCycle(EmployeePayroll $payroll, Employee $employee): ?array
    {
        $referenceMonth = $this->legacyReferenceMonth($payroll);

        if (! $referenceMonth) {
            return null;
        }

        $officialSalaryDate = $employee->salaryDateForMonth($referenceMonth);

        if (! $officialSalaryDate) {
            return null;
        }

        return $this->cycleFromOfficialDate($payroll, $employee, $officialSalaryDate, 'legacy_compatibility');
    }

    private function cycleFromPeriod(EmployeePayroll $payroll, Employee $employee, string $source): array
    {
        $periodEnd = $payroll->salary_period_to?->copy()->startOfDay()
            ?: $payroll->to_date?->copy()->startOfDay()
            ?: $payroll->salary_month?->copy()->endOfMonth()->startOfDay();

        if (! $periodEnd) {
            return $this->emptyCycle($payroll, $source);
        }

        $officialSalaryDate = $this->officialDateForPeriodEnd($employee, $periodEnd);

        return $this->baseCycle($payroll, $employee, $officialSalaryDate, $source, [
            'cycle_start' => $payroll->salary_period_from?->copy()->startOfDay()
                ?: $payroll->from_date?->copy()->startOfDay(),
            'cycle_end' => $payroll->salary_period_to?->copy()->startOfDay()
                ?: $payroll->to_date?->copy()->startOfDay()
                ?: $periodEnd,
        ]);
    }

    private function cycleFromOfficialDate(EmployeePayroll $payroll, Employee $employee, Carbon $officialSalaryDate, string $source): array
    {
        $officialSalaryDate = $officialSalaryDate->copy()->startOfDay();
        $confirmationDate = $employee->salaryEligibilityDate();
        $previousCycleDate = $this->cycleDateInMonth($officialSalaryDate->copy()->subMonthNoOverflow(), $employee->salaryCycleDay());
        $cycleStart = $confirmationDate && $confirmationDate->gte($previousCycleDate)
            ? $confirmationDate->copy()
            : $previousCycleDate->copy()->addDay();

        return $this->baseCycle($payroll, $employee, $officialSalaryDate, $source, [
            'cycle_start' => $payroll->cycle_due_date || $payroll->cycle_key
                ? ($payroll->salary_period_from?->copy()->startOfDay() ?: $payroll->from_date?->copy()->startOfDay() ?: $cycleStart)
                : $cycleStart,
            'cycle_end' => $payroll->cycle_due_date || $payroll->cycle_key
                ? ($payroll->salary_period_to?->copy()->startOfDay() ?: $payroll->to_date?->copy()->startOfDay() ?: $officialSalaryDate)
                : $officialSalaryDate,
        ]);
    }

    private function finalSettlementCycle(EmployeePayroll $payroll, Employee $employee): array
    {
        $officialSalaryDate = $employee->finalSettlementSalaryDate()
            ?: $payroll->cycle_due_date
            ?: $payroll->salary_period_to
            ?: $employee->last_working_date;

        return $this->baseCycle($payroll, $employee, $officialSalaryDate?->copy()->startOfDay(), 'final_settlement', [
            'cycle_start' => $payroll->salary_period_from?->copy()->startOfDay()
                ?: $payroll->from_date?->copy()->startOfDay()
                ?: $employee->salaryEligibilityDate(),
            'cycle_end' => $payroll->salary_period_to?->copy()->startOfDay()
                ?: $payroll->to_date?->copy()->startOfDay()
                ?: $employee->last_working_date?->copy()->startOfDay(),
            'cycle_type' => 'final_settlement',
        ]);
    }

    private function baseCycle(EmployeePayroll $payroll, Employee $employee, ?Carbon $officialSalaryDate, string $source, array $overrides = []): array
    {
        $cycleStart = $overrides['cycle_start'] ?? null;
        $cycleEnd = $overrides['cycle_end'] ?? null;

        return [
            'payroll' => $payroll,
            'source' => $source,
            'cycle_start' => $cycleStart instanceof Carbon ? $cycleStart->copy()->startOfDay() : null,
            'cycle_end' => $cycleEnd instanceof Carbon ? $cycleEnd->copy()->startOfDay() : null,
            'cycle_month' => $payroll->salary_month?->copy()->startOfMonth()
                ?: $officialSalaryDate?->copy()->startOfMonth(),
            'cycle_due_date' => $payroll->cycle_due_date?->copy()->startOfDay(),
            'official_salary_date' => $officialSalaryDate?->copy()->startOfDay(),
            'cycle_type' => $overrides['cycle_type'] ?? ($payroll->isFinalSettlementPayroll() ? 'final_settlement' : 'normal'),
            'employee_id' => $employee->id,
            'payroll_id' => $payroll->id,
        ];
    }

    private function emptyCycle(EmployeePayroll $payroll, string $source): array
    {
        return [
            'payroll' => $payroll,
            'source' => $source,
            'cycle_start' => null,
            'cycle_end' => null,
            'cycle_month' => $payroll->salary_month?->copy()->startOfMonth(),
            'cycle_due_date' => $payroll->cycle_due_date?->copy()->startOfDay(),
            'official_salary_date' => $payroll->cycle_due_date?->copy()->startOfDay(),
            'cycle_type' => $payroll->isFinalSettlementPayroll() ? 'final_settlement' : 'normal',
            'employee_id' => $payroll->employee_id,
            'payroll_id' => $payroll->id,
        ];
    }

    private function officialDateForPeriodEnd(Employee $employee, Carbon $periodEnd): ?Carbon
    {
        $officialDate = $employee->salaryDateForMonth($periodEnd->copy());

        if ($officialDate && $officialDate->lt($periodEnd)) {
            return $employee->salaryDateForMonth($periodEnd->copy()->addMonthNoOverflow());
        }

        return $officialDate;
    }

    private function legacyReferenceMonth(EmployeePayroll $payroll): ?Carbon
    {
        $text = trim(implode(' ', array_filter([
            $payroll->payment_note,
            $payroll->note,
        ])));

        if ($text === '') {
            return null;
        }

        $months = 'January|February|March|April|May|June|July|August|September|October|November|December|Jan|Feb|Mar|Apr|Jun|Jul|Aug|Sep|Sept|Oct|Nov|Dec';

        if (preg_match('/\b(' . $months . ')\s+(\d{4})\b/i', $text, $matches)) {
            return Carbon::parse($matches[1] . ' 1 ' . $matches[2])->startOfMonth();
        }

        if (preg_match('/\b(\d{4})-(\d{2})\b/', $text, $matches)) {
            return Carbon::create((int) $matches[1], (int) $matches[2], 1)->startOfMonth();
        }

        return null;
    }

    private function cycleDateInMonth(Carbon $month, ?int $salaryDay): Carbon
    {
        $salaryDay = $salaryDay ?: 1;

        return $month
            ->copy()
            ->startOfMonth()
            ->day(min($salaryDay, $month->copy()->endOfMonth()->day))
            ->startOfDay();
    }
}
