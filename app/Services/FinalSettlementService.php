<?php

namespace App\Services;

use App\Models\Employee;
use Carbon\Carbon;

class FinalSettlementService
{
    public const DEFAULT_GRACE_DAYS = 10;

    public function __construct(private PayrollCycleResolver $payrollCycleResolver)
    {
    }

    public function calculateSettlementPeriod(Employee $employee): ?array
    {
        return $this->payrollCycleResolver->settlementBoundary($employee);
    }

    public function calculateSettlementSalaryDate(Employee $employee): ?Carbon
    {
        $lastWorkingDate = $employee->last_working_date?->copy()->startOfDay();
        $salaryDay = $employee->salaryCycleDay();

        if (! $lastWorkingDate || ! $salaryDay) {
            return null;
        }

        $candidate = $this->salaryDateInMonth($lastWorkingDate->copy(), $salaryDay);

        if ($candidate->lte($lastWorkingDate)) {
            $candidate = $this->salaryDateInMonth($lastWorkingDate->copy()->addMonthNoOverflow(), $salaryDay);
        }

        return $candidate;
    }

    public function calculatePaymentDeadline(Employee $employee): ?Carbon
    {
        return $this->calculateSettlementSalaryDate($employee)?->addDays($this->graceDays());
    }

    public function daysUntilDeadline(Employee $employee, ?Carbon $asOf = null): ?int
    {
        $deadline = $this->calculatePaymentDeadline($employee);

        if (! $deadline) {
            return null;
        }

        $today = ($asOf ?: now())->copy()->startOfDay();

        return (int) $today->diffInDays($deadline->copy()->startOfDay(), false);
    }

    public function calculateOverdueDays(Employee $employee, ?Carbon $asOf = null): int
    {
        return max(-($this->daysUntilDeadline($employee, $asOf) ?? 0), 0);
    }

    public function isDueToday(Employee $employee, ?Carbon $asOf = null): bool
    {
        return $this->daysUntilDeadline($employee, $asOf) === 0;
    }

    public function isOverdue(Employee $employee, ?Carbon $asOf = null): bool
    {
        return $this->calculateOverdueDays($employee, $asOf) > 0;
    }

    public function calculateCurrentStage(Employee $employee, ?Carbon $asOf = null): string
    {
        $daysUntilDeadline = $this->daysUntilDeadline($employee, $asOf);

        if ($daysUntilDeadline === null) {
            return 'unknown';
        }

        if ($daysUntilDeadline > 0) {
            return 'due_in';
        }

        if ($daysUntilDeadline === 0) {
            return 'due_today';
        }

        return 'overdue';
    }

    public function deadlineLabel(Employee $employee, ?Carbon $asOf = null): string
    {
        $daysUntilDeadline = $this->daysUntilDeadline($employee, $asOf);

        if ($daysUntilDeadline === null) {
            return 'Final Settlement Deadline: -';
        }

        if ($daysUntilDeadline > 0) {
            return 'Final Settlement Due In: ' . $daysUntilDeadline . ' Days';
        }

        if ($daysUntilDeadline === 0) {
            return 'Final Settlement Due Today';
        }

        return 'Final Settlement Overdue: ' . abs($daysUntilDeadline) . ' Days';
    }

    public function graceDays(): int
    {
        return (int) config('payroll.final_settlement_grace_days', self::DEFAULT_GRACE_DAYS);
    }

    private function salaryDateInMonth(Carbon $month, int $salaryDay): Carbon
    {
        return $month
            ->copy()
            ->startOfMonth()
            ->day(min($salaryDay, $month->copy()->endOfMonth()->day))
            ->startOfDay();
    }
}
