<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClientPage extends Model
{
    public const PLATFORMS = ['Facebook', 'Instagram', 'TikTok', 'Website', 'Other'];

    protected $fillable = [
        'client_id',
        'business_manager_id',
        'ad_account_id',
        'page_name',
        'page_id',
        'page_url',
        'platform',
        'status',
        'note',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function businessManager()
    {
        return $this->belongsTo(BusinessManager::class);
    }

    public function adAccount()
    {
        return $this->belongsTo(AdAccount::class);
    }

    public function campaigns()
    {
        return $this->hasMany(Campaign::class);
    }

    public function assignments()
    {
        return $this->hasMany(EmployeeAssignment::class);
    }

    public function workStatuses()
    {
        return $this->hasMany(EmployeeWorkStatus::class);
    }

    public function employeeSubmissions()
    {
        return $this->hasMany(EmployeeDailySubmission::class, 'page_id');
    }

    public function marketingOperationsReports()
    {
        return $this->hasMany(MarketingOperationsReport::class, 'page_id');
    }

    public function moderatorReports()
    {
        return $this->hasMany(ModeratorReport::class, 'page_id');
    }

    public function adManagerReports()
    {
        return $this->hasMany(AdManagerReport::class, 'page_id');
    }

    public function auditorReports()
    {
        return $this->hasMany(AuditorReport::class, 'page_id');
    }

    public function monitorReports()
    {
        return $this->hasMany(MonitorReport::class, 'page_id');
    }

    public function operationSummaries()
    {
        return $this->hasMany(PageDailyOperationSummary::class, 'page_id');
    }

    public function performanceVerifications()
    {
        return $this->hasMany(PerformanceVerification::class, 'page_id');
    }

    public function adAccountMappings()
    {
        return $this->hasMany(AdAccountPage::class);
    }

    public function cardTransactions()
    {
        return $this->hasMany(CardTransaction::class);
    }

    public function datasets()
    {
        return $this->hasMany(Dataset::class);
    }

    public function metaSpendSnapshots()
    {
        return $this->hasMany(MetaSpendSnapshot::class);
    }

    public function dailyPerformanceReports()
    {
        return $this->hasManyThrough(DailyPerformanceReport::class, Campaign::class);
    }
}
