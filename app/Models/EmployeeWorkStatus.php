<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeWorkStatus extends Model
{
    public const STATUSES = [
        'working' => 'Working',
        'half_day' => 'Half Day',
        'absent' => 'Absent',
        'on_leave' => 'On Leave',
        'sick_leave' => 'Sick Leave',
        'client_issue' => 'Client Issue',
        'boosting_off' => 'Boosting OFF',
        'agency_closed' => 'Agency Closed',
    ];

    public const SALARY_COUNT_VALUES = [
        'working' => 1.0,
        'half_day' => 0.5,
        'absent' => 0.0,
        'on_leave' => 0.0,
        'sick_leave' => 0.0,
        'client_issue' => 0.0,
        'boosting_off' => 0.0,
        'agency_closed' => 0.0,
    ];

    protected $fillable = [
        'employee_id',
        'client_id',
        'work_date',
        'status',
        'salary_count_value',
        'note',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'work_date' => 'date',
            'salary_count_value' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (EmployeeWorkStatus $workStatus) {
            if (! $workStatus->isDirty('salary_count_value')) {
                $workStatus->salary_count_value = self::salaryCountFor($workStatus->status);
            }
        });
    }

    public static function salaryCountFor(string $status): float
    {
        return self::SALARY_COUNT_VALUES[$status] ?? 0.0;
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? ucwords(str_replace('_', ' ', $this->status));
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
