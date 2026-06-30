<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeTarget extends Model
{
    protected $fillable = ['employee_id', 'department_id', 'role_id', 'target_type', 'target_value', 'period_type', 'start_date', 'end_date', 'status', 'created_by'];

    protected function casts(): array
    {
        return ['target_value' => 'decimal:2', 'start_date' => 'date', 'end_date' => 'date'];
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function role()
    {
        return $this->belongsTo(EmployeeRole::class, 'role_id');
    }
}
