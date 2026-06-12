<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FacebookCard extends Model
{
    public const CURRENCY = 'USD';

    public const STATUSES = [
        'active' => 'Active',
        'low_balance' => 'Low Balance',
        'disabled' => 'Disabled',
        'expired' => 'Expired',
    ];

    protected $fillable = [
        'card_name',
        'card_type',
        'card_last_four',
        'provider',
        'current_balance',
        'currency',
        'ad_account_id',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'current_balance' => 'decimal:2',
        ];
    }

    public function adAccount()
    {
        return $this->belongsTo(AdAccount::class);
    }

    public function getCurrencyAttribute($value): string
    {
        return $value ?: self::CURRENCY;
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->effectiveStatus()] ?? ucwords(str_replace('_', ' ', (string) $this->status));
    }

    public function effectiveStatus(): string
    {
        if ($this->status === 'active' && (float) $this->current_balance < 100) {
            return 'low_balance';
        }

        return $this->status ?: 'active';
    }

    public function statusBadgeClass(): string
    {
        return [
            'active' => 'badge-success',
            'low_balance' => 'badge-warning',
            'disabled' => 'badge-danger',
            'expired' => 'badge-danger',
        ][$this->effectiveStatus()] ?? 'badge-neutral';
    }
}
