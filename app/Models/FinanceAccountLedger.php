<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FinanceAccountLedger extends Model
{
    public const TRANSACTION_TYPES = [
        'opening_balance' => 'Opening Balance',
        'manual_adjustment' => 'Manual Adjustment',
        'salary_payment' => 'Salary Payment',
        'salary_payment_reversal' => 'Salary Payment Reversal',
        'family_expense' => 'Family Expense',
        'family_expense_reversal' => 'Family Expense Reversal',
        'client_payment' => 'Client Payment',
        'client_payment_reversal' => 'Client Payment Reversal',
        'binance_purchase' => 'Binance Purchase',
        'card_load' => 'Card Load',
        'card_transaction' => 'Card Transaction',
        'loan_taken' => 'Loan Taken',
        'loan_given' => 'Loan Given',
        'loan_repayment' => 'Loan Repayment',
        'future_reserved' => 'Future Reserved',
    ];

    protected $fillable = [
        'finance_account_id',
        'employee_payroll_id',
        'ledger_date',
        'transaction_type',
        'amount',
        'currency',
        'direction',
        'previous_balance',
        'new_balance',
        'reference',
        'reference_type',
        'reference_id',
        'old_balance',
        'new_balance_snapshot',
        'description',
        'transaction_reference',
        'note',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'ledger_date' => 'date',
            'amount' => 'decimal:2',
            'old_balance' => 'decimal:2',
            'new_balance_snapshot' => 'decimal:2',
            'previous_balance' => 'decimal:2',
            'new_balance' => 'decimal:2',
        ];
    }

    public function account()
    {
        return $this->belongsTo(FinanceAccount::class, 'finance_account_id');
    }

    public function payroll()
    {
        return $this->belongsTo(EmployeePayroll::class, 'employee_payroll_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function typeLabel(): string
    {
        return self::TRANSACTION_TYPES[$this->transaction_type]
            ?? ucwords(str_replace('_', ' ', $this->transaction_type));
    }
}
