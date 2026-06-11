<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class AdAccount extends Model
{
    public const CURRENCY = 'USD';

    public const STATUSES = [
        'active' => 'Active',
        'payment_issue' => 'Payment Issue',
        'disabled' => 'Disabled',
        'review' => 'Review',
        'limit_reached' => 'Limit Reached',
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

    public function campaigns()
    {
        return $this->hasMany(Campaign::class);
    }

    public function ledgers()
    {
        return $this->hasMany(AdAccountLedger::class);
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

    public function thresholdUsagePercent(): float
    {
        $threshold = (float) $this->threshold_amount;

        if ($threshold <= 0) {
            return 0.0;
        }

        return min(((float) $this->current_threshold_usage / $threshold) * 100, 100);
    }

    public function thresholdStatus(): string
    {
        $threshold = (float) $this->threshold_amount;
        $usage = (float) $this->current_threshold_usage;

        if ($threshold > 0 && $usage >= $threshold) {
            return 'limit_reached';
        }

        $percent = $this->thresholdUsagePercent();

        if ($percent >= 95) {
            return 'critical';
        }

        if ($percent >= 80) {
            return 'warning';
        }

        return 'normal';
    }

    public function thresholdStatusLabel(): string
    {
        return [
            'normal' => 'Normal',
            'warning' => 'Warning',
            'critical' => 'Critical',
            'limit_reached' => 'Limit Reached',
        ][$this->thresholdStatus()];
    }

    public function daysUntilBilling(?Carbon $today = null): ?int
    {
        if (! $this->monthly_billing_date) {
            return null;
        }

        $today = ($today ?: now())->copy()->startOfDay();
        $billingDay = min((int) $this->monthly_billing_date, $today->copy()->daysInMonth);
        $billingDate = $today->copy()->day($billingDay);

        return (int) $today->diffInDays($billingDate, false);
    }

    public function billingStatus(?Carbon $today = null): string
    {
        $days = $this->daysUntilBilling($today);

        if ($days === null) {
            return 'not_set';
        }

        if ($days < 0) {
            return 'overdue';
        }

        if ($days <= 5) {
            return 'upcoming';
        }

        return 'normal';
    }

    public function billingStatusLabel(): string
    {
        return [
            'not_set' => 'Not Set',
            'normal' => 'Normal',
            'upcoming' => 'Upcoming Billing',
            'overdue' => 'Overdue Billing',
        ][$this->billingStatus()];
    }

    public function balanceStatus(): string
    {
        $balance = (float) $this->current_balance;

        if ($balance <= 0) {
            return 'negative';
        }

        if ($balance < 100) {
            return 'low';
        }

        return 'normal';
    }

    public function balanceStatusLabel(): string
    {
        return [
            'normal' => 'Normal',
            'low' => 'Low Balance',
            'negative' => 'Negative Balance',
        ][$this->balanceStatus()];
    }
}
