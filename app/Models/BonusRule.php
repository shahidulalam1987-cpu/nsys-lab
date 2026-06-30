<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BonusRule extends Model
{
    protected $fillable = ['name', 'applies_to_type', 'employee_id', 'department_id', 'role_id', 'metric', 'comparison', 'threshold', 'bonus_amount', 'bonus_type', 'period_type', 'status', 'created_by'];

    protected function casts(): array
    {
        return ['threshold' => 'decimal:2', 'bonus_amount' => 'decimal:2'];
    }

    public function earnings()
    {
        return $this->hasMany(EmployeeBonusEarning::class);
    }
}
