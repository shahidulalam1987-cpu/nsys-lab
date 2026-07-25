<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Dataset extends Model
{
    use SoftDeletes;

    public const EVENT_SOURCE_TYPES = [
        'website' => 'Website',
        'crm' => 'CRM',
        'app' => 'App',
        'offline' => 'Offline',
        'other' => 'Other',
    ];

    public const STATUSES = [
        'active' => 'Active',
        'inactive' => 'Inactive',
        'restricted' => 'Restricted',
    ];

    protected $fillable = [
        'dataset_name',
        'dataset_id',
        'business_manager_id',
        'ad_account_id',
        'client_id',
        'client_page_id',
        'platform',
        'event_source_type',
        'domain_url',
        'status',
        'notes',
        'created_by',
        'updated_by',
    ];

    public function businessManager()
    {
        return $this->belongsTo(BusinessManager::class);
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

    public function campaigns()
    {
        return $this->hasMany(Campaign::class);
    }

    public function eventSourceLabel(): string
    {
        return self::EVENT_SOURCE_TYPES[$this->event_source_type] ?? ucwords(str_replace('_', ' ', (string) $this->event_source_type));
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? ucwords(str_replace('_', ' ', (string) $this->status));
    }
}
