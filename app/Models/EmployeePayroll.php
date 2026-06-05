<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeePayroll extends Model
{
    protected $appends = [
        'calculated_status',
    ];

    protected $fillable = [
        'employee_id',
        'client_id',
        'salary_month',
        'payable_salary',
        'paid_amount',
        'payment_method',
        'payment_date',
        'status',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'salary_month' => 'date',
            'payable_salary' => 'decimal:2',
            'paid_amount' => 'decimal:2',
            'payment_date' => 'date',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (EmployeePayroll $payroll) {
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

    public function getCalculatedStatusAttribute(): string
    {
        return self::statusFor(
            (float) ($this->attributes['payable_salary'] ?? 0),
            (float) ($this->attributes['paid_amount'] ?? 0)
        );
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
        if ($status === 'unpaid') {
            return $query->where('paid_amount', '<=', 0);
        }

        if ($status === 'partial') {
            return $query->where('paid_amount', '>', 0)
                ->whereColumn('paid_amount', '<', 'payable_salary');
        }

        if ($status === 'paid') {
            return $query->where('paid_amount', '>', 0)
                ->whereColumn('paid_amount', '>=', 'payable_salary');
        }

        return $query;
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
