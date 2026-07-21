<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Campaign extends Model
{
    public const OBJECTIVES = [
        'messages' => 'Messages',
        'leads' => 'Leads',
        'sales' => 'Sales',
        'traffic' => 'Traffic',
        'engagement' => 'Engagement',
        'reach' => 'Reach',
        'video_views' => 'Video Views',
        'app_promotion' => 'App Promotion',
        'custom' => 'Custom',
    ];

    public const STATUSES = [
        'draft' => 'Draft',
        'active' => 'Active',
        'paused' => 'Paused',
        'completed' => 'Completed',
        'archived' => 'Archived',
    ];

    protected $fillable = [
        'campaign_name',
        'campaign_id',
        'business_manager_id',
        'ad_account_id',
        'client_id',
        'client_page_id',
        'objective',
        'status',
        'start_date',
        'end_date',
        'daily_budget',
        'lifetime_budget',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'daily_budget' => 'decimal:2',
            'lifetime_budget' => 'decimal:2',
        ];
    }

    public function businessManager()
    {
        return $this->belongsTo(BusinessManager::class);
    }

    public function adAccount()
    {
        return $this->belongsTo(AdAccount::class);
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function page()
    {
        return $this->belongsTo(ClientPage::class, 'client_page_id');
    }

    public function assignments()
    {
        return $this->hasMany(EmployeeAssignment::class);
    }

    public function workStatuses()
    {
        return $this->hasMany(EmployeeWorkStatus::class);
    }

    public function dailyPerformanceReports()
    {
        return $this->hasMany(DailyPerformanceReport::class);
    }

    public function employeeSubmissions()
    {
        return $this->hasMany(EmployeeDailySubmission::class);
    }

    public function marketingOperationsReports()
    {
        return $this->hasMany(MarketingOperationsReport::class);
    }

    public function moderatorReports()
    {
        return $this->hasMany(ModeratorReport::class);
    }

    public function adManagerReports()
    {
        return $this->hasMany(AdManagerReport::class);
    }

    public function operationSummaries()
    {
        return $this->hasMany(PageDailyOperationSummary::class);
    }

    public function performanceVerifications()
    {
        return $this->hasMany(PerformanceVerification::class);
    }

    public function cardTransactions()
    {
        return $this->hasMany(CardTransaction::class);
    }

    public function metaSpendSnapshots()
    {
        return $this->hasMany(MetaSpendSnapshot::class);
    }

    public function performanceSummary()
    {
        $reports = $this->dailyPerformanceReports;

        return [
            'spend' => (float) $reports->sum('spend'),
            'messages' => (int) $reports->sum('messages'),
            'results' => (int) $reports->sum('results'),
            'leads' => (int) $reports->sum('leads'),
            'orders' => (int) $reports->sum('orders'),
            'clicks' => (int) $reports->sum('clicks'),
        ];
    }

    public function objectiveLabel(): string
    {
        return self::OBJECTIVES[$this->objective] ?? ucwords(str_replace('_', ' ', $this->objective));
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? ucwords(str_replace('_', ' ', $this->status));
    }

    public function isEndingSoon(?Carbon $today = null): bool
    {
        if (! $this->end_date || $this->status !== 'active') {
            return false;
        }

        $today = ($today ?: now())->copy()->startOfDay();

        return $this->end_date->betweenIncluded($today, $today->copy()->addDays(7));
    }

    public function totalSpend(): float
    {
        if ($this->relationLoaded('dailyPerformanceReports')) {
            return (float) $this->dailyPerformanceReports->sum('spend');
        }

        return (float) $this->dailyPerformanceReports()->sum('spend');
    }

    public function remainingBudget(): float
    {
        return max((float) $this->lifetime_budget - $this->totalSpend(), 0);
    }

    public function budgetUtilizationPercent(): float
    {
        $budget = (float) $this->lifetime_budget;

        if ($budget <= 0) {
            return 0.0;
        }

        return min(($this->totalSpend() / $budget) * 100, 100);
    }
}
