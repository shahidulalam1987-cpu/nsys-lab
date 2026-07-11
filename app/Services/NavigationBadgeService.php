<?php

namespace App\Services;

use App\Models\AdAccount;
use App\Models\AutomationTask;
use App\Models\BugReport;
use App\Models\EmployeeDailySubmission;
use App\Models\FacebookCard;
use App\Models\FundingBalance;
use App\Models\SalaryPayment;
use App\Models\SystemNotification;

class NavigationBadgeService
{
    private array $counts = [];

    public function __construct(private ClientFundDashboardService $clientFundDashboard)
    {
    }

    public function count(?string $key): int
    {
        if (! $key) {
            return 0;
        }

        return $this->counts[$key] ??= $this->resolve($key);
    }

    private function resolve(string $key): int
    {
        return match ($key) {
            'notifications_unread' => SystemNotification::query()
                ->where('status', 'unread')
                ->count(),
            'open_bugs' => BugReport::query()
                ->where('status', 'open')
                ->count(),
            'upcoming_salary' => $this->clientFundBadges()['upcoming_salary_count'],
            'unpaid_salary' => $this->clientFundBadges()['unpaid_salary_count'],
            'pending_client_payments' => $this->clientFundBadges()['pending_payment_count'],
            'automation_pending' => AutomationTask::query()
                ->where('status', 'pending')
                ->count(),
            'pending_employee_submissions' => EmployeeDailySubmission::query()
                ->where('status', 'pending')
                ->count(),
            'ready_to_merge' => $this->readyToMergeCount(),
            'low_card_balance' => FacebookCard::query()
                ->where('status', 'active')
                ->where('current_balance', '<', 100)
                ->count(),
            'low_funding_balance' => FundingBalance::query()
                ->where(function ($query) {
                    $query->where(fn ($inner) => $inner->where('source', 'binance')->where('current_balance', '<', 200))
                        ->orWhere(fn ($inner) => $inner->whereIn('source', ['redotpay', 'tavao'])->where('current_balance', '<', 100));
                })
                ->count(),
            'ad_account_billing' => AdAccount::query()
                ->whereNotNull('monthly_billing_date')
                ->where(function ($query) {
                    $query->where('monthly_billing_date', '<', today()->day)
                        ->orWhereBetween('monthly_billing_date', [today()->day, today()->copy()->addDays(5)->day]);
                })
                ->count(),
            default => 0,
        };
    }

    private function clientFundBadges(): array
    {
        return $this->counts['client_fund_badges'] ??= $this->clientFundDashboard->sidebarBadges();
    }

    private function readyToMergeCount(): int
    {
        return EmployeeDailySubmission::query()
            ->where('status', 'approved')
            ->whereNotNull('campaign_id')
            ->get(['submission_date', 'client_id', 'page_id', 'campaign_id', 'submission_type'])
            ->groupBy(fn (EmployeeDailySubmission $submission) => implode(':', [
                $submission->submission_date?->toDateString(),
                $submission->client_id,
                $submission->page_id,
                $submission->campaign_id,
            ]))
            ->filter(fn ($group) => $group->pluck('submission_type')->unique()->count() === 2)
            ->count();
    }
}
