<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WhatsAppLog extends Model
{
    protected $table = 'whats_app_logs';

    protected $fillable = [
        'loggable_type',
        'loggable_id',
        'client_id',
        'recipient',
        'message_type',
        'status',
        'message',
        'sent_at',
        'response',
        'created_by',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }
}
