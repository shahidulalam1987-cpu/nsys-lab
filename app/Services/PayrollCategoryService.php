<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\EmployeePayroll;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class PayrollCategoryService
{
    public const UPCOMING = 'upcoming';
    public const PENDING_WORK_STATUS = 'pending_work_status';
    public const SALARY_READY = 'salary_ready';
    public const GENERATED = 'generated';
    public const UNPAID = 'unpaid';
    public const PAID = 'paid';
    public const FINAL_SETTLEMENT_PENDING = 'final_settlement_pending';
    public const FINAL_SETTLEMENT_UNPAID = 'final_settlement_unpaid';
    public const FINAL_SETTLEMENT_PAID = 'final_settlement_paid';
    public const NOT_SALARY_ELIGIBLE = 'not_salary_eligible';

    public const LABELS = [
        self::UPCOMING => 'Upcoming',
        self::PENDING_WORK_STATUS => 'Pending Work Status',
        self::SALARY_READY => 'Salary Ready',
        self::GENERATED => 'Generated',
        self::UNPAID => 'Unpaid',
        self::PAID => 'Paid',
        self::FINAL_SETTLEMENT_PENDING => 'Final Settlement Pending',
        self::FINAL_SETTLEMENT_UNPAID => 'Final Settlement Unpaid',
        self::FINAL_SETTLEMENT_PAID => 'Final Settlement Paid',
        self::NOT_SALARY_ELIGIBLE => 'Not Salary Eligible',
    ];

    public const PRIORITY = [
        self::FINAL_SETTLEMENT_UNPAID => 10,
        self::FINAL_SETTLEMENT_PAID => 20,
        self::FINAL_SETTLEMENT_PENDING => 30,
        self::UNPAID => 40,
        self::GENERATED => 50,
        self::UPCOMING => 60,
        self::SALARY_READY => 70,
        self::PENDING_WORK_STATUS => 80,
        self::PAID => 90,
        self::NOT_SALARY_ELIGIBLE => 100,
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
                return $this->category(self::FINAL_SETTLEMENT_UNPAID, [
                    'payroll' => $finalPayrolls->filter(fn (EmployeePayroll $payroll) => $payroll->isFinalSettlementDue())->sortByDesc('id')->first(),
                ]);
            }

            if ($finalPayrolls->contains(fn (EmployeePayroll $payroll) => $payroll->isFinalSettlementPaid())) {
                return $this->category(self::FINAL_SETTLEMENT_PAID, [
                    'payroll' => $finalPayrolls->filter(fn (EmployeePayroll $payroll) => $payroll->isFinalSettlementPaid())->sortByDesc('id')->first(),
                ]);
            }

            if (! $employee->isSalaryEligible($employee->last_working_date)) {
                return $this->category(self::NOT_SALARY_ELIGIBLE);
            }

            return $this->category(self::FINAL_SETTLEMENT_PENDING, [
                'salary_date' => $employee->last_working_date,
                'estimate' => $this->payrollEstimator->estimateCycle(
                    $employee,
                    $employee->last_working_date,
                    $employee->isAgencyInternal() ? null : $employee->activeAssignments->first()?->client
                ),
            ]);
        }

        $today = ($salaryDate ?: now())->copy()->startOfDay();
        $payrolls = $employee->payrolls
            ->filter(fn (EmployeePayroll $payroll) => $payroll->is_current || $payroll->is_current === null)
            ->sortByDesc('id');

        $overduePayroll = $payrolls->first(function (EmployeePayroll $payroll) use ($today) {
            $dueDate = $payroll->salaryDueDate();

            return (float) $payroll->paid_amount < (float) $payroll->payable_salary
                && (! $dueDate || $dueDate->copy()->startOfDay()->lt($today));
        });

        if ($overduePayroll) {
            return $this->category(self::UNPAID, ['payroll' => $overduePayroll]);
        }

        if (! $employee->isSalaryEligible($today) && $payrolls->isNotEmpty()) {
            $payroll = $payrolls->first();
            $resolved = $this->resolvePayroll($payroll);
            $resolved['payroll'] = $payroll;

            return $resolved;
        }

        if (! $employee->isSalaryEligible($today)) {
            return $this->category(self::NOT_SALARY_ELIGIBLE);
        }

        $client = $employee->isAgencyInternal() ? null : $employee->activeAssignments->first()?->client;
        $currentSalaryDate = $employee->currentSalaryDueDate($today);
        $currentPayroll = $this->payrollForCycle($payrolls, $currentSalaryDate);
        $upcomingSalaryDate = $employee->nextSalaryDate();
        $upcomingPayroll = $this->payrollForCycle($payrolls, $upcomingSalaryDate);
        $isUpcomingWindow = $upcomingSalaryDate
            && $upcomingSalaryDate->betweenIncluded($today, $today->copy()->addDays(5));

        if ($isUpcomingWindow && (! $upcomingPayroll || (float) $upcomingPayroll->paid_amount < (float) $upcomingPayroll->payable_salary)) {
            return $this->category(self::UPCOMING, [
                'payroll' => $upcomingPayroll,
                'salary_date' => $upcomingSalaryDate,
                'estimate' => $upcomingPayroll ? null : $this->payrollEstimator->estimateCycle($employee, $upcomingSalaryDate, $client),
            ]);
        }

        if ($currentPayroll) {
            $resolved = $this->resolvePayroll($currentPayroll);
            $resolved['payroll'] = $currentPayroll;

            return $resolved;
        }

        if ($currentSalaryDate && $currentSalaryDate->lt($today)) {
            $estimate = $this->payrollEstimator->estimateCycle($employee, $currentSalaryDate, $client);

            if ((float) $estimate['estimated_payable_salary'] <= 0 && (int) $estimate['work_status_records'] === 0) {
                return $this->category(self::PENDING_WORK_STATUS, array_merge($estimate, [
                    'estimate' => $estimate,
                    'salary_date' => $currentSalaryDate,
                ]));
            }

            return $this->category(self::SALARY_READY, array_merge($estimate, [
                'estimate' => $estimate,
                'salary_date' => $currentSalaryDate,
            ]));
        }

        $latestPaidPayroll = $payrolls->first(fn (EmployeePayroll $payroll) => (float) $payroll->paid_amount >= (float) $payroll->payable_salary);
        if ($latestPaidPayroll) {
            return $this->category(self::PAID, ['payroll' => $latestPaidPayroll]);
        }

        $futureSalaryDate = $upcomingSalaryDate ?: $currentSalaryDate;
        $estimate = $this->payrollEstimator->estimateCycle($employee, $futureSalaryDate, $client);

        if ((float) $estimate['estimated_payable_salary'] <= 0 && (int) $estimate['work_status_records'] === 0) {
            return $this->category(self::UPCOMING, [
                'salary_date' => $futureSalaryDate,
                'estimate' => $estimate,
            ]);
        }

        return $this->category(self::UPCOMING, [
            'salary_date' => $futureSalaryDate,
            'estimate' => $estimate,
        ]);
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

    public function upcomingCycles(?Carbon $today = null): Collection
    {
        $today = ($today ?: now())->copy()->startOfDay();
        $until = $today->copy()->addDays(5);

        return Employee::with(['payrolls' => fn ($query) => $query->current(), 'activeAssignments.client'])
            ->where('status', '!=', 'terminated')
            ->get()
            ->map(function (Employee $employee) use ($today, $until) {
                $stage = $this->resolveEmployee($employee, $today);
                $salaryDate = data_get($stage, 'salary_date');

                if (($stage['category'] ?? null) !== self::UPCOMING
                    || ! $salaryDate
                    || ! $salaryDate->betweenIncluded($today, $until)) {
                    return null;
                }

                return [
                    'employee' => $employee,
                    'salary_date' => $salaryDate,
                    'estimate' => data_get($stage, 'estimate'),
                    'payroll' => data_get($stage, 'payroll'),
                ];
            })
            ->filter()
            ->values();
    }

    private function payrollForCycle(Collection $payrolls, ?Carbon $salaryDate): ?EmployeePayroll
    {
        if (! $salaryDate) {
            return null;
        }

        $cycleMonth = $salaryDate->copy()->startOfMonth()->toDateString();

        return $payrolls
            ->first(fn (EmployeePayroll $payroll) => $payroll->salary_month?->copy()->startOfMonth()->toDateString() === $cycleMonth);
    }

    private function category(string $category, array $context = []): array
    {
        return array_merge($context, [
            'category' => $category,
            'label' => $this->label($category),
            'priority' => self::PRIORITY[$category] ?? 999,
        ]);
    }
}
