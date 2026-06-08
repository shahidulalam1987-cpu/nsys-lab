<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeePayrollAudit extends Model
{
    protected $fillable = [
        'employee_payroll_id',
        'user_id',
        'action',
        'note',
    ];

    public function payroll()
    {
        return $this->belongsTo(EmployeePayroll::class, 'employee_payroll_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
