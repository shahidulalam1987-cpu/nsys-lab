<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeAttendance extends Model
{
    public const STATUSES = [
        'present' => 'Present',
        'absent' => 'Absent',
        'on_leave' => 'On Leave',
        'client_issue' => 'Client Issue',
        'boosting_off' => 'Boosting OFF',
        'sick_leave' => 'Sick Leave',
        'holiday' => 'Holiday',
    ];

    public const WORKING_STATUSES = ['present'];

    protected $fillable = [
        'employee_id',
        'client_id',
        'shift_id',
        'attendance_date',
        'check_in_at',
        'is_late',
        'check_out_at',
        'status',
        'is_working_day',
        'note',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'attendance_date' => 'date',
            'check_in_at' => 'datetime',
            'check_out_at' => 'datetime',
            'is_working_day' => 'boolean',
            'is_late' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (EmployeeAttendance $attendance) {
            if (! $attendance->isDirty('is_working_day')) {
                $attendance->is_working_day = self::isWorkingStatus($attendance->status);
            }
        });
    }

    public static function isWorkingStatus(string $status): bool
    {
        return in_array($status, self::WORKING_STATUSES, true);
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

    public function shift()
    {
        return $this->belongsTo(Shift::class);
    }
}
