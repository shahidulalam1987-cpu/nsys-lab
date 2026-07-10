<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MarketingOperationSetting extends Model
{
    protected $fillable = [
        'key',
        'value',
        'type',
        'description',
        'updated_by',
    ];

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
