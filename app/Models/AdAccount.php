<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdAccount extends Model
{
    public const CURRENCY = 'USD';

    public const STATUSES = [
        'active' => 'Active',
        'payment_issue' => 'Payment Issue',
        'disabled' => 'Disabled',
        'review' => 'Review',
    ];

    protected $fillable = [
        'ad_account_name',
        'ad_account_id',
        'business_manager_id',
        'client_id',
        'currency',
        'timezone',
        'threshold_amount',
        'current_threshold_usage',
        'current_balance',
        'monthly_billing_date',
        'last_payment_date',
        'payment_method',
        'card_last_four',
        'status',
        'notes',
    ];

    protected $appends = [
        'remaining_threshold',
    ];

    protected function casts(): array
    {
        return [
            'threshold_amount' => 'decimal:2',
            'current_threshold_usage' => 'decimal:2',
            'current_balance' => 'decimal:2',
            'monthly_billing_date' => 'integer',
            'last_payment_date' => 'date',
        ];
    }

    public function businessManager()
    {
        return $this->belongsTo(BusinessManager::class);
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function pages()
    {
        return $this->hasMany(ClientPage::class);
    }

    public function getRemainingThresholdAttribute(): float
    {
        return max((float) $this->threshold_amount - (float) $this->current_threshold_usage, 0);
    }

    public function getCurrencyAttribute($value): string
    {
        return $value ?: self::CURRENCY;
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? ucwords(str_replace('_', ' ', $this->status));
    }
}
