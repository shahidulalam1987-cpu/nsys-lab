<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClientPage extends Model
{
    public const PLATFORMS = ['Facebook', 'Instagram', 'TikTok', 'Website', 'Other'];

    protected $fillable = [
        'client_id',
        'page_name',
        'page_url',
        'platform',
        'status',
        'note',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }
}
