<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FinanceAccount extends Model
{
    public const TYPES = [
        'bank' => 'Bank Account',
        'bkash' => 'bKash',
        'nagad' => 'Nagad',
        'cash' => 'Cash',
        'binance' => 'Binance',
        'redotpay' => 'RedotPay',
        'tavao' => 'Tavao',
    ];

    public const CURRENCIES = [
        'BDT' => 'BDT',
        'USD' => 'USD',
    ];

    public const STATUSES = [
        'active' => 'Active',
        'inactive' => 'Inactive',
    ];

    protected $fillable = [
        'account_type',
        'account_name',
        'provider_name',
        'account_number',
        'currency',
        'current_balance',
        'status',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'current_balance' => 'decimal:2',
        ];
    }

    public function typeLabel(): string
    {
        return self::TYPES[$this->account_type] ?? ucwords(str_replace('_', ' ', (string) $this->account_type));
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? ucwords(str_replace('_', ' ', (string) $this->status));
    }

    public function ledgers()
    {
        return $this->hasMany(FinanceAccountLedger::class);
    }
}
