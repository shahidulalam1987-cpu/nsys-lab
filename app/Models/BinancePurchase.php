<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BinancePurchase extends Model
{
    protected $fillable = [
        'finance_account_id',
        'purchase_date',
        'usd_amount',
        'remaining_usd',
        'buy_rate',
        'total_bdt_cost',
        'source',
        'seller_name',
        'reference',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'purchase_date' => 'date',
            'usd_amount' => 'decimal:2',
            'remaining_usd' => 'decimal:2',
            'buy_rate' => 'decimal:4',
            'total_bdt_cost' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (BinancePurchase $purchase) {
            $purchase->total_bdt_cost = round((float) $purchase->usd_amount * (float) $purchase->buy_rate, 2);
            if ($purchase->remaining_usd === null) {
                $purchase->remaining_usd = $purchase->usd_amount;
            }
        });
    }

    public function loads()
    {
        return $this->hasMany(CardLoad::class);
    }

    public function transactions()
    {
        return $this->hasMany(CardTransaction::class);
    }

    public function financeAccount()
    {
        return $this->belongsTo(FinanceAccount::class);
    }
}
