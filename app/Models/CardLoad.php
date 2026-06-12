<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CardLoad extends Model
{
    protected $fillable = [
        'load_date',
        'facebook_card_id',
        'binance_purchase_id',
        'usd_loaded',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'load_date' => 'date',
            'usd_loaded' => 'decimal:2',
        ];
    }

    public function card()
    {
        return $this->belongsTo(FacebookCard::class, 'facebook_card_id');
    }

    public function binancePurchase()
    {
        return $this->belongsTo(BinancePurchase::class);
    }
}
