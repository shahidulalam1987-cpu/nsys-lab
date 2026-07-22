<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FundingBalance extends Model
{
    public const CURRENCY = 'USD';

    public const SOURCES = [
        'binance' => 'Binance',
        'redotpay' => 'RedotPay',
        'tavao' => 'Tevau',
    ];

    public const LOW_BALANCE_LIMITS = [
        'binance' => 200,
        'redotpay' => 100,
        'tavao' => 100,
    ];

    protected $fillable = [
        'source',
        'current_balance',
        'currency',
        'balance_date',
        'notes',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'current_balance' => 'decimal:2',
            'balance_date' => 'date',
        ];
    }

    public function histories()
    {
        return $this->hasMany(FundingBalanceHistory::class);
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function getCurrencyAttribute($value): string
    {
        return $value ?: self::CURRENCY;
    }

    public function sourceLabel(): string
    {
        return self::SOURCES[$this->source] ?? ucwords(str_replace('_', ' ', (string) $this->source));
    }

    public function lowBalanceLimit(): float
    {
        return (float) (self::LOW_BALANCE_LIMITS[$this->source] ?? 100);
    }

    public function isLowBalance(): bool
    {
        return (float) $this->current_balance < $this->lowBalanceLimit();
    }

    public function statusLabel(): string
    {
        return $this->isLowBalance() ? 'Low Balance' : 'Healthy';
    }

    public function statusBadgeClass(): string
    {
        return $this->isLowBalance() ? 'badge-warning' : 'badge-success';
    }
}
