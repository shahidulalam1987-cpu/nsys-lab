<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdAccountBillingHistory extends Model
{
    protected $table = 'ad_account_billing_history';

    protected $fillable = [
        'ad_account_id',
        'billing_date',
        'billing_amount_usd',
        'paid_date',
        'payment_status',
        'reference',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'billing_date' => 'date',
        'paid_date' => 'date',
        'billing_amount_usd' => 'decimal:2',
    ];

    public function adAccount()
    {
        return $this->belongsTo(AdAccount::class);
    }
}
