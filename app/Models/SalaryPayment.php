<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalaryPayment extends Model
{
    protected $fillable = [
        'client_id',
        'receipt_number',
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
        'approved_by',
        'approved_ip',
        'approved_user_agent',
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

    protected static function booted(): void
    {
        static::saving(function (SalaryPayment $payment) {
            if ($payment->exists && $payment->isDirty('receipt_number') && $payment->getOriginal('receipt_number')) {
                $payment->receipt_number = $payment->getOriginal('receipt_number');
            }

            foreach (['approved_by', 'approved_ip', 'approved_user_agent'] as $field) {
                if ($payment->exists && $payment->isDirty($field) && $payment->getOriginal($field)) {
                    $payment->{$field} = $payment->getOriginal($field);
                }
            }
        });
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function financeAccount()
    {
        return $this->belongsTo(FinanceAccount::class);
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function financeLedgers()
    {
        return $this->hasMany(FinanceAccountLedger::class, 'reference_id')
            ->where('reference_type', self::class);
    }

    public function clientFundLedgers()
    {
        return $this->morphMany(ClientFundLedger::class, 'source', 'source_type', 'source_id');
    }

    public function financeLedger()
    {
        return $this->financeLedgers()->where('transaction_type', 'client_payment')->where('direction', 'credit')->first();
    }

    public function clientFundLedger()
    {
        return $this->clientFundLedgers()
            ->where('fund_type', $this->fund_type ?: ClientFundLedger::FUND_EMPLOYEE_SALARY)
            ->where('direction', ClientFundLedger::DIRECTION_CREDIT)
            ->first();
    }

    public function receiptNumber(): string
    {
        return $this->receipt_number ?: self::receiptFor((int) $this->id, $this->created_at);
    }

    public static function receiptFor(int $id, mixed $date = null): string
    {
        $year = $date ? date('Y', strtotime((string) $date)) : date('Y');

        return 'NSYS-CP-' . $year . '-' . str_pad((string) $id, 6, '0', STR_PAD_LEFT);
    }
}
