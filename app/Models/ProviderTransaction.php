<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProviderTransaction extends Model
{
    protected $fillable = [
        'payment_provider_id',
        'facebook_card_id',
        'transaction_date',
        'transaction_type',
        'amount_usd',
        'fee_usd',
        'reference',
        'status',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'transaction_date' => 'date',
        'amount_usd' => 'decimal:2',
        'fee_usd' => 'decimal:2',
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
