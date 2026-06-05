<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalaryDay extends Model
{
    public const REASONS = [
        'active_working',
        'boosting_off',
        'client_issue',
        'business_closed',
        'work_stopped',
        'agency_hold',
    ];

    protected $fillable = [
        'employee_id',
        'client_id',
        'date',
        'is_counted',
        'reason',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'is_counted' => 'boolean',
        ];
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }
}
