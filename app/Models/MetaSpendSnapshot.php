<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MetaSpendSnapshot extends Model
{
    protected $fillable = [
        'campaign_id',
        'ad_account_id',
        'client_id',
        'client_page_id',
        'snapshot_date',
        'spend_usd',
        'orders',
        'raw_payload',
        'source',
    ];

    protected $casts = [
        'snapshot_date' => 'date',
        'spend_usd' => 'decimal:2',
        'raw_payload' => 'array',
    ];

    public function campaign()
    {
        return $this->belongsTo(Campaign::class);
    }
}
