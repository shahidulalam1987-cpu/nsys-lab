<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProviderFeeTracking extends Model
{
    protected $table = 'provider_fee_tracking';

    protected $fillable = [
        'payment_provider_id',
        'facebook_card_id',
        'sample_date',
        'facebook_charge_usd',
        'provider_deducted_usd',
        'fee_amount_usd',
        'fee_percentage',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'sample_date' => 'date',
        'facebook_charge_usd' => 'decimal:2',
        'provider_deducted_usd' => 'decimal:2',
        'fee_amount_usd' => 'decimal:2',
        'fee_percentage' => 'decimal:4',
    ];

    public function provider()
    {
        return $this->belongsTo(PaymentProvider::class, 'payment_provider_id');
    }

    public function card()
    {
        return $this->belongsTo(FacebookCard::class, 'facebook_card_id');
    }
}
