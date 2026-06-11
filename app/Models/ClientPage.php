<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClientPage extends Model
{
    public const PLATFORMS = ['Facebook', 'Instagram', 'TikTok', 'Website', 'Other'];

    protected $fillable = [
        'client_id',
        'business_manager_id',
        'ad_account_id',
        'page_name',
        'page_id',
        'page_url',
        'platform',
        'status',
        'note',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function businessManager()
    {
        return $this->belongsTo(BusinessManager::class);
    }

    public function adAccount()
    {
        return $this->belongsTo(AdAccount::class);
    }
}
