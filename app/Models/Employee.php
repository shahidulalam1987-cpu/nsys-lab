<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    protected $fillable = [
        'user_id',
        'employee_id',
        'name',
        'mobile',
        'department',
        'role',
        'joining_date',
        'confirmation_date',
        'last_working_date',
        'status',
        'salary_type',
        'monthly_salary',
        'bank_name',
        'account_name',
        'account_number',
        'mobile_banking_info',
    ];

    protected function casts(): array
    {
        return [
            'joining_date' => 'date',
            'confirmation_date' => 'date',
            'last_working_date' => 'date',
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
}
