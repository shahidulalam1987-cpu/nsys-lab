<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeBonusEarning extends Model
{
    protected $fillable = ['employee_id', 'bonus_rule_id', 'period_start', 'period_end', 'metric_value', 'bonus_amount', 'status', 'approved_by', 'paid_payroll_id', 'note'];

    protected function casts(): array
    {
        return ['period_start' => 'date', 'period_end' => 'date', 'metric_value' => 'decimal:2', 'bonus_amount' => 'decimal:2'];
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function rule()
    {
        return $this->belongsTo(BonusRule::class, 'bonus_rule_id');
    }
}
