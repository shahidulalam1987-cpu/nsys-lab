<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeePayroll extends Model
{
    public const FIXED_SALARY_MONTH_DAYS = 30;

    protected $appends = [
        'calculated_status',
        'salary_period',
    ];

    protected $fillable = [
        'employee_id',
        'payroll_employee_name',
        'payroll_employee_code',
        'client_id',
        'salary_source',
        'calculation_type',
        'salary_period_from',
        'salary_period_to',
        'from_date',
        'to_date',
        'working_days',
        'non_working_days',
        'month_days',
        'daily_salary',
        'salary_day_adjustments',
        'salary_month',
        'payable_salary',
        'payroll_salary_amount',
        'paid_amount',
        'payroll_bank_name',
        'payroll_account_name',
        'payroll_account_number',
        'payroll_branch_name',
        'payment_method',
        'finance_account_id',
        'finance_account_name',
        'payment_date',
        'status',
        'payment_status',
        'payroll_status',
        'generation_status',
        'regenerated_from_id',
        'approved_at',
        'approved_by',
        'paid_at',
        'paid_by',
        'payment_proof',
        'transaction_id',
        'payment_note',
        'salary_payment_attachment',
        'payment_confirmed_at',
        'reversed_at',
        'reversed_by',
        'reversal_note',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'from_date' => 'date',
            'to_date' => 'date',
            'salary_period_from' => 'date',
            'salary_period_to' => 'date',
            'salary_month' => 'date',
            'working_days' => 'decimal:2',
            'non_working_days' => 'decimal:2',
            'month_days' => 'integer',
            'daily_salary' => 'decimal:2',
            'salary_day_adjustments' => 'array',
            'payable_salary' => 'decimal:2',
            'payroll_salary_amount' => 'decimal:2',
            'paid_amount' => 'decimal:2',
            'payment_date' => 'date',
            'approved_at' => 'datetime',
            'paid_at' => 'datetime',
            'payment_confirmed_at' => 'datetime',
            'reversed_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (EmployeePayroll $payroll) {
            $payroll->attributes['payment_status'] = self::paymentStatusFor(
                $payroll->attributes['payment_status'] ?? null,
                (float) ($payroll->payable_salary ?? 0),
                (float) ($payroll->paid_amount ?? 0)
            );
            $payroll->attributes['payroll_status'] = $payroll->attributes['payroll_status'] ?? 'generated';
            $payroll->attributes['generation_status'] = $payroll->attributes['generation_status'] ?? 'generated';
            $payroll->attributes['status'] = self::statusFor(
                (float) ($payroll->payable_salary ?? 0),
                (float) ($payroll->paid_amount ?? 0)
            );
        });
    }

    public static function statusFor(float $payableSalary, float $paidAmount): string
    {
        $payableCents = self::amountToCents($payableSalary);
        $paidCents = self::amountToCents($paidAmount);

        if ($paidCents <= 0) {
            return 'unpaid';
        }

        if ($paidCents < $payableCents) {
            return 'partial';
        }

        return 'paid';
    }

    public static function paymentStatusFor(?string $selectedStatus, float $payableSalary, float $paidAmount): string
    {
        if ($selectedStatus === 'upcoming') {
            return 'upcoming';
        }

        return self::statusFor($payableSalary, $paidAmount);
    }

    public function getCalculatedStatusAttribute(): string
    {
        $status = self::paymentStatusFor(
            $this->attributes['payment_status'] ?? null,
            (float) ($this->attributes['payable_salary'] ?? 0),
            (float) ($this->attributes['paid_amount'] ?? 0)
        );

        if (in_array($status, ['paid', 'partial'], true)) {
            return $status;
        }

        return $this->salaryCycleStatusForZeroPayment($status);
    }

    public function getSalaryPeriodAttribute(): string
    {
        if ($this->salary_period_from && $this->salary_period_to) {
            return $this->salary_period_from->toDateString() . ' to ' . $this->salary_period_to->toDateString();
        }

        if ($this->from_date && $this->to_date) {
            return $this->from_date->toDateString() . ' to ' . $this->to_date->toDateString();
        }

        return $this->salary_month?->format('Y-m') ?? '-';
    }

    public function calculationTypeLabel(): string
    {
        return $this->calculation_type === 'monthly_cycle'
            ? 'Monthly Cycle'
            : 'Date To Date';
    }

    public function payrollStatusLabel(): string
    {
        return [
            'draft' => 'Draft',
            'generated' => 'Generated',
            'approved' => 'Approved',
            'paid' => 'Paid',
        ][$this->payroll_status] ?? 'Generated';
    }

    public function payrollStatusBadgeClass(): string
    {
        return [
            'draft' => 'badge-neutral',
            'generated' => 'badge-info',
            'approved' => 'badge-warning',
            'paid' => 'badge-success',
        ][$this->payroll_status] ?? 'badge-info';
    }

    public function generationStatusLabel(): string
    {
        return $this->generation_status === 'regenerated' ? 'Regenerated' : 'Generated';
    }

    public function generationStatusBadgeClass(): string
    {
        return $this->generation_status === 'regenerated' ? 'badge-warning' : 'badge-info';
    }

    public function canApprove(): bool
    {
        return in_array($this->payroll_status, ['draft', 'generated'], true);
    }

    public function canMarkPaid(): bool
    {
        return in_array($this->payroll_status, ['approved', 'paid'], true);
    }

    public function markAudit(string $action, ?int $userId = null, ?string $note = null): EmployeePayrollAudit
    {
        return $this->audits()->create([
            'user_id' => $userId,
            'action' => $action,
            'note' => $note,
        ]);
    }

    public function workflowActionLabel(string $action): string
    {
        return [
            'salary_generated' => 'Salary Generated',
            'salary_regenerated' => 'Salary Regenerated',
            'salary_approved' => 'Salary Approved',
            'salary_paid' => 'Salary Paid',
            'salary_reversed' => 'Salary Reversed',
        ][$action] ?? ucwords(str_replace('_', ' ', $action));
    }

    public function salarySourceLabel(): string
    {
        return Employee::SALARY_SOURCES[$this->salary_source ?: 'client_fund'] ?? 'Client Fund';
    }

    public function snapshotEmployeeName(): string
    {
        return $this->payroll_employee_name ?: ($this->employee?->name ?: '-');
    }

    public function snapshotEmployeeCode(): string
    {
        return $this->payroll_employee_code ?: ($this->employee?->employee_id ?: '-');
    }

    public function snapshotSalaryAmount(): float
    {
        return (float) ($this->payroll_salary_amount ?? $this->payable_salary ?? 0);
    }

    public function snapshotBankName(): string
    {
        return $this->payroll_bank_name ?: ($this->employee?->bank_name ?: '-');
    }

    public function snapshotAccountName(): string
    {
        return $this->payroll_account_name ?: ($this->employee?->account_name ?: '-');
    }

    public function snapshotAccountNumber(): string
    {
        return $this->payroll_account_number ?: ($this->employee?->account_number ?: '-');
    }

    public function snapshotBranchName(): string
    {
        return $this->payroll_branch_name ?: ($this->employee?->branch_name ?: '-');
    }

    public function getStatusAttribute($value): string
    {
        if (array_key_exists('payable_salary', $this->attributes)
            && array_key_exists('paid_amount', $this->attributes)) {
            return $this->calculated_status;
        }

        return $value;
    }

    public function scopeWithCalculatedStatus($query, ?string $status)
    {
        if ($status === 'upcoming') {
            return $query->where('payment_status', 'upcoming');
        }

        if ($status === 'unpaid') {
            return $query->where(function ($query) {
                $query->where('payment_status', 'unpaid')
                    ->orWhere(function ($query) {
                        $query->whereNull('payment_status')
                            ->where('paid_amount', '<=', 0);
                    });
            });
        }

        if ($status === 'partial') {
            return $query->where(function ($query) {
                $query->where('payment_status', 'partial')
                    ->orWhere(function ($query) {
                        $query->whereNull('payment_status')
                            ->where('paid_amount', '>', 0)
                            ->whereColumn('paid_amount', '<', 'payable_salary');
                    });
            });
        }

        if ($status === 'due') {
            return $query->where(function ($query) {
                $query->where('payment_status', 'unpaid')
                    ->orWhere('payment_status', 'partial')
                    ->orWhereColumn('paid_amount', '<', 'payable_salary');
            });
        }

        if ($status === 'paid') {
            return $query->where(function ($query) {
                $query->where('payment_status', 'paid')
                    ->orWhere(function ($query) {
                        $query->whereNull('payment_status')
                            ->where('paid_amount', '>', 0)
                            ->whereColumn('paid_amount', '>=', 'payable_salary');
                    });
            });
        }

        return $query;
    }

    public function matchesStatusFilter(?string $status): bool
    {
        if (! $status) {
            return true;
        }

        if ($status === 'due') {
            return in_array($this->calculated_status, ['unpaid', 'partial'], true)
                || (float) $this->paid_amount < (float) $this->payable_salary;
        }

        if ($status === 'upcoming') {
            return $this->calculated_status === 'upcoming'
                && $this->isWithinUpcomingSalaryWindow();
        }

        return $this->calculated_status === $status;
    }

    public function isFinalSettlement(): bool
    {
        $employee = $this->relationLoaded('employee')
            ? $this->employee
            : $this->employee()->first();

        return $employee?->status === 'terminated'
            && (float) $this->paid_amount < (float) $this->payable_salary;
    }

    public function settlementStatusLabel(): string
    {
        return $this->isFinalSettlement()
            ? 'Final Settlement Unpaid'
            : ([
                'upcoming' => 'Upcoming',
                'unpaid' => 'Unpaid',
                'partial' => 'Partially Paid',
                'paid' => 'Paid',
            ][$this->calculated_status] ?? ucfirst($this->calculated_status));
    }

    public function salaryDueDate(): ?\Carbon\Carbon
    {
        $employee = $this->relationLoaded('employee')
            ? $this->employee
            : $this->employee()->first();

        $salaryMonth = $this->salary_month?->copy()->startOfMonth()
            ?: $this->salary_period_to?->copy()->startOfMonth()
            ?: now()->startOfMonth();

        return $employee?->salaryDateForMonth($salaryMonth)
            ?: ($this->isFinalSettlement() ? $employee?->last_working_date : null);
    }

    public function overdueLabel(): string
    {
        $dueDate = $this->salaryDueDate();

        if (! $dueDate) {
            return '-';
        }

        $days = max(now()->startOfDay()->diffInDays($dueDate, false) * -1, 0);

        return $this->isFinalSettlement()
            ? 'Final Settlement Overdue: ' . $days . ' Days'
            : $days . ' Days Overdue';
    }

    private function salaryCycleStatusForZeroPayment(string $fallbackStatus): string
    {
        $employee = $this->relationLoaded('employee')
            ? $this->employee
            : $this->employee()->first();

        if (! $employee) {
            return $fallbackStatus;
        }

        $salaryMonth = $this->salary_month?->copy()->startOfMonth()
            ?: $this->salary_period_to?->copy()->startOfMonth()
            ?: now()->startOfMonth();
        $dueDate = $employee->salaryDateForMonth($salaryMonth);

        if (! $dueDate) {
            return $fallbackStatus;
        }

        $today = now()->startOfDay();

        if ($dueDate->lt($today)) {
            return 'unpaid';
        }

        return 'upcoming';
    }

    private function isWithinUpcomingSalaryWindow(): bool
    {
        $employee = $this->relationLoaded('employee')
            ? $this->employee
            : $this->employee()->first();

        if (! $employee) {
            return true;
        }

        $salaryMonth = $this->salary_month?->copy()->startOfMonth()
            ?: $this->salary_period_to?->copy()->startOfMonth()
            ?: now()->startOfMonth();
        $dueDate = $employee->salaryDateForMonth($salaryMonth);

        if (! $dueDate) {
            return true;
        }

        $today = now()->startOfDay();

        return $dueDate->betweenIncluded($today, $today->copy()->addDays(5));
    }

    private static function amountToCents(float $amount): int
    {
        return (int) round($amount * 100);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function audits()
    {
        return $this->hasMany(EmployeePayrollAudit::class)->latest();
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function payer()
    {
        return $this->belongsTo(User::class, 'paid_by');
    }

    public function financeAccount()
    {
        return $this->belongsTo(FinanceAccount::class);
    }

    public function financeLedgers()
    {
        return $this->hasMany(FinanceAccountLedger::class, 'employee_payroll_id');
    }
}
