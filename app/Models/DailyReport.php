<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DailyReport extends Model
{
    protected $fillable = [
        'client_id',
        'report_date',
        'page_name',
        'dollar_spend',
        'orders',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }
}