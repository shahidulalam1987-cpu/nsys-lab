<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'role_name',
        'module',
        'action',
        'description',
        'old_value',
        'new_value',
        'ip_address',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'old_value' => 'array',
            'new_value' => 'array',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
