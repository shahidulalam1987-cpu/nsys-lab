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

    public function adAccounts()
    {
        return $this->hasMany(AdAccount::class);
    }

    public function campaigns()
    {
        return $this->hasMany(Campaign::class);
    }

    public function dailyPerformanceReports()
    {
        return $this->hasManyThrough(DailyPerformanceReport::class, Campaign::class);
    }

    public function salaryDays()
    {
        return $this->hasMany(SalaryDay::class);
    }

    public function salaryPayments()
    {
        return $this->hasMany(SalaryPayment::class);
    }

    public function fundLedgers()
    {
        return $this->hasMany(ClientFundLedger::class);
    }

    public function employeePayrolls()
    {
        return $this->hasMany(EmployeePayroll::class);
    }

    public function salary_fund_balance(): float
    {
        return app(\App\Services\ClientSalaryFundService::class)->balance($this);
    }

    public function ads_fund_balance(): float
    {
        return app(\App\Services\ClientAdsFundService::class)->balance($this);
    }

    public function total_client_balance(): float
    {
        return round($this->salary_fund_balance() + $this->ads_fund_balance(), 2);
    }

    public function salaryFundBalance(): float
    {
        return $this->salary_fund_balance();
    }

    public function adsFundBalance(): float
    {
        return $this->ads_fund_balance();
    }

    public function totalClientBalance(): float
    {
        return $this->total_client_balance();
    }
}
