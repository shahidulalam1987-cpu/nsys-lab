<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FinanceAccountLedger extends Model
{
    protected $fillable = [
        'finance_account_id',
        'employee_payroll_id',
        'ledger_date',
        'transaction_type',
        'amount',
        'previous_balance',
        'new_balance',
        'reference',
        'note',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'ledger_date' => 'date',
            'amount' => 'decimal:2',
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
        return ucwords(str_replace('_', ' ', $this->transaction_type));
    }
}
