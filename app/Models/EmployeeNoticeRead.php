<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeNoticeRead extends Model
{
    protected $fillable = [
        'employee_notice_id',
        'employee_id',
        'read_at',
    ];

    protected function casts(): array
    {
        return [
            'read_at' => 'datetime',
        ];
    }
}
