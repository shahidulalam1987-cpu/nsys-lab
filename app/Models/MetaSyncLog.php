<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MetaSyncLog extends Model
{
    protected $fillable = [
        'sync_type',
        'status',
        'started_at',
        'finished_at',
        'records_processed',
        'message',
        'context',
        'created_by',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
        'context' => 'array',
    ];
}
