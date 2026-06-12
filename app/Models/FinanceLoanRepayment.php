<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FinanceLoanRepayment extends Model
{
    protected $fillable = [
        'finance_loan_id',
        'payment_date',
        'amount',
        'method',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'payment_date' => 'date',
            'amount' => 'decimal:2',
        ];
    }

    public function loan()
    {
        return $this->belongsTo(FinanceLoan::class, 'finance_loan_id');
    }
}
