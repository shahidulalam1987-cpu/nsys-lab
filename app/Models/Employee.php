<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    public const STATUSES = [
        'active' => 'Active-Working',
        'probation' => 'Probation-Trial Period',
        'on_leave' => 'On Leave-Temporary Leave',
        'inactive' => 'Inactive-Not Working',
        'terminated' => 'Terminated-Employment Ended',
    ];

    public const STATUS_FILTERS = [
        'active' => 'Active',
        'probation' => 'Probation',
        'on_leave' => 'On Leave',
        'inactive' => 'Inactive',
        'terminated' => 'Terminated',
    ];

    public const DEPARTMENTS = [
        'Moderator',
        'Customer Care',
        'Sales',
        'Creative',
        'Management',
        'Support',
    ];

    public const ROLES = [
        'Trainee Moderator',
        'Moderator',
        'Senior Moderator',
        'Customer Care',
        'Sales Executive',
        'Graphic Designer',
        'Video Editor',
        'Team Leader',
        'Manager',
    ];

    protected $fillable = [
        'user_id',
        'employee_id',
        'name',
        'mobile',
        'email',
        'address',
        'nid_number',
        'date_of_birth',
        'gender',
        'profile_photo',
        'nid_front_file',
        'nid_back_file',
        'cv_file',
        'appointment_letter_file',
        'agreement_file',
        'department',
        'role',
        'joining_date',
        'confirmation_date',
        'last_working_date',
        'status',
        'salary_type',
        'salary_day',
        'monthly_salary',
        'bank_name',
        'account_name',
        'account_number',
        'branch_name',
        'bkash_number',
        'nagad_number',
        'rocket_number',
        'preferred_payment_method',
        'mobile_banking_info',
        'admin_note',
    ];

    protected function casts(): array
    {
        return [
            'joining_date' => 'date',
            'confirmation_date' => 'date',
            'last_working_date' => 'date',
            'date_of_birth' => 'date',
            'monthly_salary' => 'decimal:2',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function assignments()
    {
        return $this->hasMany(EmployeeAssignment::class);
    }

    public function activeAssignments()
    {
        return $this->hasMany(EmployeeAssignment::class)->where('status', 'active');
    }

    public function salaryDays()
    {
        return $this->hasMany(SalaryDay::class);
    }

    public function payrolls()
    {
        return $this->hasMany(EmployeePayroll::class);
    }

    public function isEligibleForConfirmation(): bool
    {
        return $this->status === 'probation'
            && $this->confirmation_date === null
            && Carbon::parse($this->joining_date)->addDays(7)->lte(now());
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? ucwords(str_replace('_', ' ', $this->status));
    }

    public function nextSalaryDate(): ?Carbon
    {
        if (! $this->salary_day) {
            return null;
        }

        $today = now()->startOfDay();
        $date = $this->salaryDateForMonth($today->copy());

        if ($date->lt($today)) {
            $date = $this->salaryDateForMonth($today->copy()->addMonthNoOverflow());
        }

        return $date;
    }

    private function salaryDateForMonth(Carbon $month): Carbon
    {
        $day = min((int) $this->salary_day, $month->daysInMonth);

        return $month->startOfMonth()->addDays($day - 1);
    }
}
