<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeePayroll extends Model
{
    protected $fillable = [
        'employee_id',
        'client_id',
        'salary_month',
        'payable_salary',
        'paid_amount',
        'payment_method',
        'payment_date',
        'status',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'salary_month' => 'date',
            'payable_salary' => 'decimal:2',
            'paid_amount' => 'decimal:2',
            'payment_date' => 'date',
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
