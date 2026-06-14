<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\EmployeePayroll;
use Carbon\Carbon;

class PayrollCategoryService
{
    public const PENDING_WORK_STATUS = 'pending_work_status';
    public const SALARY_READY = 'salary_ready';
    public const GENERATED = 'generated';
    public const UNPAID = 'unpaid';
    public const PAID = 'paid';
    public const FINAL_SETTLEMENT_PENDING = 'final_settlement_pending';
    public const FINAL_SETTLEMENT_UNPAID = 'final_settlement_unpaid';
    public const FINAL_SETTLEMENT_PAID = 'final_settlement_paid';

    public const LABELS = [
        self::PENDING_WORK_STATUS => 'Pending Work Status',
        self::SALARY_READY => 'Salary Ready',
        self::GENERATED => 'Generated',
        self::UNPAID => 'Unpaid',
        self::PAID => 'Paid',
        self::FINAL_SETTLEMENT_PENDING => 'Final Settlement Pending',
        self::FINAL_SETTLEMENT_UNPAID => 'Final Settlement Unpaid',
        self::FINAL_SETTLEMENT_PAID => 'Final Settlement Paid',
    ];

    public function __construct(private PayrollEstimateService $payrollEstimator)
    {
    }

    public function resolveEmployee(Employee $employee, ?Carbon $salaryDate = null): array
    {
        $employee->loadMissing(['payrolls' => fn ($query) => $query->current(), 'activeAssignments.client']);

        if ($employee->status === 'terminated') {
            $finalPayrolls = $employee->payrolls
                ->filter(fn (EmployeePayroll $payroll) => $payroll->isFinalSettlementPayroll());

            if ($finalPayrolls->contains(fn (EmployeePayroll $payroll) => $payroll->isFinalSettlementDue())) {
                return $this->category(self::FINAL_SETTLEMENT_UNPAID);
            }

            if ($finalPayrolls->contains(fn (EmployeePayroll $payroll) => $payroll->isFinalSettlementPaid())) {
                return $this->category(self::FINAL_SETTLEMENT_PAID);
            }

            return $this->category(self::FINAL_SETTLEMENT_PENDING);
        }

        $payroll = $this->latestCurrentPayroll($employee, $salaryDate);

        if ($payroll) {
            return $this->resolvePayroll($payroll);
        }

        $client = $employee->isAgencyInternal() ? null : $employee->activeAssignments->first()?->client;
        $estimate = $this->payrollEstimator->estimateCycle($employee, $salaryDate ?: $employee->currentSalaryDueDate(), $client);

        if ((float) $estimate['estimated_payable_salary'] <= 0 && (int) $estimate['work_status_records'] === 0) {
            return $this->category(self::PENDING_WORK_STATUS, $estimate);
        }

        return $this->category(self::SALARY_READY, $estimate);
    }

    public function resolvePayroll(EmployeePayroll $payroll): array
    {
        $payroll->loadMissing('employee');

        if ($payroll->isFinalSettlementDue()) {
            return $this->category(self::FINAL_SETTLEMENT_UNPAID);
        }

        if ($payroll->isFinalSettlementPaid()) {
            return $this->category(self::FINAL_SETTLEMENT_PAID);
        }

        if ($payroll->payroll_status === 'generated' && ! in_array($payroll->calculated_status, ['unpaid', 'partial', 'paid'], true)) {
            return $this->category(self::GENERATED);
        }

        if (in_array($payroll->calculated_status, ['unpaid', 'partial'], true)
            || (float) $payroll->paid_amount < (float) $payroll->payable_salary) {
            return $this->category(self::UNPAID);
        }

        return $this->category(self::PAID);
    }

    public function label(string $category): string
    {
        return self::LABELS[$category] ?? ucwords(str_replace('_', ' ', $category));
    }

    private function latestCurrentPayroll(Employee $employee, ?Carbon $salaryDate): ?EmployeePayroll
    {
        $payrolls = $employee->payrolls->filter(fn (EmployeePayroll $payroll) => $payroll->is_current);

        if ($salaryDate) {
            $cycleMonth = $salaryDate->copy()->startOfMonth()->toDateString();
            $cyclePayroll = $payrolls
                ->filter(fn (EmployeePayroll $payroll) => $payroll->salary_month?->copy()->startOfMonth()->toDateString() === $cycleMonth)
                ->sortByDesc('id')
                ->first();

            if ($cyclePayroll) {
                return $cyclePayroll;
            }
        }

        return $payrolls->sortByDesc('id')->first();
    }

    private function category(string $category, array $context = []): array
    {
        return array_merge($context, [
            'category' => $category,
            'label' => $this->label($category),
        ]);
    }
}
