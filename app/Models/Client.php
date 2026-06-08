<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    protected $fillable = [
        'user_id',
        'company_name',
        'phone',
        'client_rate',
        'buy_rate',
        'status',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function dailyReports()
    {
        return $this->hasMany(DailyReport::class);
    }

    public function messages()
    {
        return $this->hasMany(Message::class);
    }

    public function employeeAssignments()
    {
        return $this->hasMany(EmployeeAssignment::class);
    }

    public function pages()
    {
        return $this->hasMany(ClientPage::class);
    }

    public function salaryDays()
    {
        return $this->hasMany(SalaryDay::class);
    }

    public function salaryPayments()
    {
        return $this->hasMany(SalaryPayment::class);
    }

    public function employeePayrolls()
    {
        return $this->hasMany(EmployeePayroll::class);
    }
}
