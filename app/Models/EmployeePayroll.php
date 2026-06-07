<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeePayroll extends Model
{
    protected $appends = [
        'calculated_status',
        'salary_period',
    ];

    protected $fillable = [
        'employee_id',
        'client_id',
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
        'paid_amount',
        'payment_method',
        'payment_date',
        'status',
        'payment_status',
        'payment_proof',
        'transaction_id',
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
            'working_days' => 'integer',
            'non_working_days' => 'integer',
            'month_days' => 'integer',
            'daily_salary' => 'decimal:2',
            'salary_day_adjustments' => 'array',
            'payable_salary' => 'decimal:2',
            'paid_amount' => 'decimal:2',
            'payment_date' => 'date',
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
}
