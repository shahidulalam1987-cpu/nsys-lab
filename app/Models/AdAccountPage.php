<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AdAccountPage extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'ad_account_id',
        'client_id',
        'client_page_id',
        'status',
        'mapped_from',
        'mapped_to',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'mapped_from' => 'date',
        'mapped_to' => 'date',
    ];

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
}
