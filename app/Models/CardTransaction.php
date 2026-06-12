<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CardTransaction extends Model
{
    protected $fillable = [
        'transaction_date',
        'facebook_card_id',
        'binance_purchase_id',
        'ad_account_id',
        'client_id',
        'client_page_id',
        'campaign_id',
        'spend_usd',
        'fee_usd',
        'extra_charge_usd',
        'total_deducted_usd',
        'buy_rate',
        'bdt_cost',
        'client_rate',
        'client_revenue',
        'net_profit',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'transaction_date' => 'date',
            'spend_usd' => 'decimal:2',
            'fee_usd' => 'decimal:2',
            'extra_charge_usd' => 'decimal:2',
            'total_deducted_usd' => 'decimal:2',
            'buy_rate' => 'decimal:4',
            'bdt_cost' => 'decimal:2',
            'client_rate' => 'decimal:4',
            'client_revenue' => 'decimal:2',
            'net_profit' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (CardTransaction $transaction) {
            $transaction->total_deducted_usd = round((float) $transaction->spend_usd + (float) $transaction->fee_usd + (float) $transaction->extra_charge_usd, 2);
            $transaction->bdt_cost = round((float) $transaction->total_deducted_usd * (float) $transaction->buy_rate, 2);
            $transaction->client_revenue = round((float) $transaction->spend_usd * (float) $transaction->client_rate, 2);
            $transaction->net_profit = round((float) $transaction->client_revenue - (float) $transaction->bdt_cost, 2);
        });
    }

    public function card()
    {
        return $this->belongsTo(FacebookCard::class, 'facebook_card_id');
    }

    public function binancePurchase()
    {
        return $this->belongsTo(BinancePurchase::class);
    }

    public function adAccount()
    {
        return $this->belongsTo(AdAccount::class);
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function page()
    {
        return $this->belongsTo(ClientPage::class, 'client_page_id');
    }

    public function campaign()
    {
        return $this->belongsTo(Campaign::class);
    }

    public function profitPerUsd(): float
    {
        $spend = (float) $this->spend_usd;

        return $spend > 0 ? round((float) $this->net_profit / $spend, 2) : 0;
    }
}
