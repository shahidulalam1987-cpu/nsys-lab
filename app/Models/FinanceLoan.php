<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FinanceLoan extends Model
{
    public const TYPES = [
        'taken' => 'Loan Taken',
        'given' => 'Loan Given',
    ];

    public const STATUSES = [
        'open' => 'Open',
        'partial' => 'Partial',
        'paid' => 'Paid',
    ];

    protected $fillable = [
        'loan_type',
        'finance_account_id',
        'person_company_name',
        'amount',
        'loan_date',
        'due_date',
        'paid_amount',
        'remaining_balance',
        'status',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'loan_date' => 'date',
            'due_date' => 'date',
            'paid_amount' => 'decimal:2',
            'remaining_balance' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (FinanceLoan $loan) {
            $loan->remaining_balance = max(round((float) $loan->amount - (float) $loan->paid_amount, 2), 0);
            $loan->status = match (true) {
                (float) $loan->paid_amount <= 0 => 'open',
                (float) $loan->paid_amount < (float) $loan->amount => 'partial',
                default => 'paid',
            };
        });
    }

    public function repayments()
    {
        return $this->hasMany(FinanceLoanRepayment::class);
    }

    public function account()
    {
        return $this->belongsTo(FinanceAccount::class, 'finance_account_id');
    }

    public function typeLabel(): string
    {
        return self::TYPES[$this->loan_type] ?? ucwords(str_replace('_', ' ', (string) $this->loan_type));
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? ucwords(str_replace('_', ' ', (string) $this->status));
    }
}
