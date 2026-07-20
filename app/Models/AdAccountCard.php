<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AdAccountCard extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'ad_account_id',
        'facebook_card_id',
        'is_primary',
        'status',
        'mapped_from',
        'mapped_to',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'mapped_from' => 'date',
        'mapped_to' => 'date',
    ];

    public function adAccount()
    {
        return $this->belongsTo(AdAccount::class);
    }

    public function card()
    {
        return $this->belongsTo(FacebookCard::class, 'facebook_card_id');
    }
}
