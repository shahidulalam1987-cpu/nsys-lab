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
        self::FINAL_SETTLEMENT_PENDING => 20,
        self::UNPAID => 30,
        self::GENERATED => 40,
        self::SALARY_READY => 50,
        self::PENDING_WORK_STATUS => 55,
        self::UPCOMING => 60,
        self::PAID => 90,
        self::FINAL_SETTLEMENT_PAID => 90,
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
                && (! $dueDate || $payroll->isOverdue($today));
        });

        if ($overduePayroll) {
            return $this->category(self::UNPAID, ['payroll' => $overduePayroll]);
        }

        $outstandingPayroll = $payrolls->first(
            fn (EmployeePayroll $payroll) => (float) $payroll->paid_amount < (float) $payroll->payable_salary
        );

        if ($outstandingPayroll) {
            $resolved = $this->resolvePayroll($outstandingPayroll);
            $resolved['payroll'] = $outstandingPayroll;

            return $resolved;
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

        if ($isUpcomingWindow && $upcomingPayroll) {
            $resolved = $this->resolvePayroll($upcomingPayroll);
            $resolved['payroll'] = $upcomingPayroll;

            return $resolved;
        }

        if ($isUpcomingWindow) {
            return $this->category(self::UPCOMING, [
                'salary_date' => $upcomingSalaryDate,
                'estimate' => $this->payrollEstimator->estimateCycle($employee, $upcomingSalaryDate, $client),
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

    public function employeeStages(?Carbon $today = null): Collection
    {
        $today = ($today ?: now())->copy()->startOfDay();

        return Employee::with(['payrolls' => fn ($query) => $query->current(), 'activeAssignments.client'])
            ->get()
            ->map(fn (Employee $employee) => [
                'employee' => $employee,
                'stage' => $this->resolveEmployee($employee, $today),
            ])
            ->filter(fn (array $row) => ($row['stage']['category'] ?? null) !== self::NOT_SALARY_ELIGIBLE)
            ->values();
    }

    public function queueCounts(?Carbon $today = null): array
    {
        $stages = $this->employeeStages($today);
        $unpaidCategories = [
            self::PENDING_WORK_STATUS,
            self::SALARY_READY,
            self::GENERATED,
            self::UNPAID,
            self::FINAL_SETTLEMENT_PENDING,
            self::FINAL_SETTLEMENT_UNPAID,
        ];

        return [
            'upcoming' => $stages->where('stage.category', self::UPCOMING)->count(),
            'unpaid' => $stages->filter(fn (array $row) => in_array($row['stage']['category'], $unpaidCategories, true))->count(),
            'pending_work_status' => $stages->where('stage.category', self::PENDING_WORK_STATUS)->count(),
            'salary_ready' => $stages->where('stage.category', self::SALARY_READY)->count(),
            'final_settlement_due' => $stages->filter(fn (array $row) => in_array($row['stage']['category'], [self::FINAL_SETTLEMENT_PENDING, self::FINAL_SETTLEMENT_UNPAID], true))->count(),
        ];
    }

    private function payrollForCycle(Collection $payrolls, ?Carbon $salaryDate): ?EmployeePayroll
    {
        if (! $salaryDate) {
            return null;
        }

        return $payrolls
            ->first(fn (EmployeePayroll $payroll) => $payroll->matchesSalaryCycleDate($salaryDate));
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
