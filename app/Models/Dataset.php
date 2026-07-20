<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Dataset extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'dataset_name',
        'dataset_id',
        'ad_account_id',
        'client_id',
        'client_page_id',
        'platform',
        'status',
        'notes',
        'created_by',
        'updated_by',
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
