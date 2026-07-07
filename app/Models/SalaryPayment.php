<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalaryPayment extends Model
{
    protected $fillable = [
        'client_id',
        'fund_type',
        'finance_account_id',
        'salary_month',
        'amount',
        'payment_method',
        'transaction_id',
        'screenshot',
        'note',
        'status',
        'approved_at',
        'rejected_at',
        'reject_reason',
    ];

    protected function casts(): array
    {
        return [
            'salary_month' => 'date',
            'amount' => 'decimal:2',
            'approved_at' => 'datetime',
            'rejected_at' => 'datetime',
        ];
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function financeAccount()
    {
        return $this->belongsTo(FinanceAccount::class);
    }

    public function financeLedgers()
    {
        return $this->hasMany(FinanceAccountLedger::class, 'reference_id')
            ->where('reference_type', self::class);
    }
}
