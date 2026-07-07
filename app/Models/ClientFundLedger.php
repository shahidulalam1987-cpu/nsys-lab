<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClientFundLedger extends Model
{
    public const FUND_EMPLOYEE_SALARY = 'employee_salary';
    public const FUND_FACEBOOK_ADS = 'facebook_ads';

    public const DIRECTION_CREDIT = 'credit';
    public const DIRECTION_DEBIT = 'debit';

    protected $fillable = [
        'client_id',
        'fund_type',
        'direction',
        'amount_bdt',
        'balance_before',
        'balance_after',
        'source_type',
        'source_id',
        'reference',
        'description',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'amount_bdt' => 'decimal:2',
            'balance_before' => 'decimal:2',
            'balance_after' => 'decimal:2',
        ];
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function source()
    {
        return $this->morphTo(__FUNCTION__, 'source_type', 'source_id');
    }

    public function fundTypeLabel(): string
    {
        return match ($this->fund_type) {
            self::FUND_EMPLOYEE_SALARY => 'Employee Salary Fund',
            self::FUND_FACEBOOK_ADS => 'Facebook Ads Fund',
            default => ucwords(str_replace('_', ' ', (string) $this->fund_type)),
        };
    }

    public function directionLabel(): string
    {
        return ucfirst((string) $this->direction);
    }

    public function isLegacyImported(): bool
    {
        return str_contains((string) $this->description, 'Legacy Imported');
    }
}
