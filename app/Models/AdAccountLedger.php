<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdAccountLedger extends Model
{
    public const TRANSACTION_TYPES = [
        'threshold_update' => 'Threshold Update',
        'balance_adjustment' => 'Balance Adjustment',
        'billing_paid' => 'Billing Paid',
        'manual_credit' => 'Manual Credit',
        'manual_debit' => 'Manual Debit',
        'status_change' => 'Status Change',
    ];

    protected $fillable = [
        'transaction_date',
        'ad_account_id',
        'transaction_type',
        'amount',
        'previous_value',
        'new_value',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'transaction_date' => 'date',
            'amount' => 'decimal:2',
            'previous_value' => 'decimal:2',
            'new_value' => 'decimal:2',
        ];
    }

    public function adAccount()
    {
        return $this->belongsTo(AdAccount::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function typeLabel(): string
    {
        return self::TRANSACTION_TYPES[$this->transaction_type] ?? ucwords(str_replace('_', ' ', $this->transaction_type));
    }
}
