<?php

namespace App\Services;

use App\Models\Client;
use App\Models\ClientFundLedger;
use App\Models\ClientPage;
use App\Models\DailyPerformanceReport;
use App\Models\Employee;
use App\Models\EmployeeAssignment;
use App\Models\EmployeeDailySubmission;
use App\Models\EmployeePayroll;
use App\Models\EmployeeWorkStatus;
use App\Models\FacebookCard;
use App\Models\FinanceAccount;
use App\Models\FinanceAccountLedger;
use App\Models\FundingBalance;
use App\Models\SalaryPayment;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ExecutiveDashboardService
{
    public function __construct(
        private ClientFundSummaryService $clientFunds,
        private ClientFundDashboardService $clientFundDashboard,
        private PayrollCategoryService $payrollCategory,
        private PerformanceOperationsService $performanceOperations
    ) {}

    public function build(array $filters = []): array
    {
        [$from, $to, $label] = $this->period($filters);
        $today = now()->startOfDay();
        $monthStart = now()->startOfMonth();
        $monthEnd = now()->endOfMonth();

        $todayReports = $this->performanceReports($today, $today)->get();
        $monthReports = $this->performanceReports($monthStart, $monthEnd)->get();
        $periodReports = $this->performanceReports($from, $to)->get();
        $fundDashboard = $this->clientFunds->dashboard();
        $legacyFundDashboard = $this->clientFundDashboard->dashboard();
        $payrollRows = $this->payrollCategory->employeeStages();

        return [
            'filters' => [
                'period' => $filters['period'] ?? 'today',
                'date_from' => $from->toDateString(),
                'date_to' => $to->toDateString(),
                'label' => $label,
            ],
            'today' => $this->todayKpis($todayReports, $today, $fundDashboard, $payrollRows),
            'month' => $this->monthKpis($monthReports, $monthStart, $monthEnd, $fundDashboard),
            'finance' => $this->financeSummary($fundDashboard, $legacyFundDashboard, $payrollRows),
            'clients' => $this->clientAnalytics($periodReports, $fundDashboard['rows']),
            'pages' => $this->pageAnalytics($periodReports),
            'employees' => $this->employeeAnalytics($from, $to),
            'alerts' => $this->alerts($fundDashboard, $legacyFundDashboard, $payrollRows),
            'trends' => $this->trends(),
            'recent' => $this->recentActivity(),
            'quick_actions' => $this->quickActions(),
            'search' => $this->globalSearchData(),
            'exports' => [
                'csv' => '/admin/executive-performance/export/csv?' . http_build_query($filters),
                'excel' => '/admin/executive-performance/export/excel?' . http_build_query($filters),
                'pdf' => '/admin/executive-performance/export/pdf?' . http_build_query($filters),
            ],
        ];
    }

    public function exportRows(array $filters = []): Collection
    {
        $dashboard = $this->build($filters);

        return collect([
            ['section' => 'Today', 'metric' => 'Total Orders', 'value' => $dashboard['today']['orders']],
            ['section' => 'Today', 'metric' => 'Total Facebook Spend USD', 'value' => $dashboard['today']['spend_usd']],
            ['section' => 'Today', 'metric' => 'Total Facebook Spend BDT', 'value' => $dashboard['today']['spend_bdt']],
            ['section' => 'Today', 'metric' => 'Total Revenue', 'value' => $dashboard['today']['revenue']],
            ['section' => 'Today', 'metric' => 'Estimated Profit', 'value' => $dashboard['today']['profit']],
            ['section' => 'This Month', 'metric' => 'Total Orders', 'value' => $dashboard['month']['orders']],
            ['section' => 'This Month', 'metric' => 'Total Spend USD', 'value' => $dashboard['month']['spend_usd']],
            ['section' => 'This Month', 'metric' => 'Total Revenue', 'value' => $dashboard['month']['revenue']],
            ['section' => 'This Month', 'metric' => 'Net Profit', 'value' => $dashboard['month']['net_profit']],
            ['section' => 'Finance', 'metric' => 'Salary Fund Balance', 'value' => $dashboard['finance']['salary_fund_balance']],
            ['section' => 'Finance', 'metric' => 'Ads Fund Balance', 'value' => $dashboard['finance']['ads_fund_balance']],
            ['section' => 'Finance', 'metric' => 'Finance Account Balance', 'value' => $dashboard['finance']['finance_account_balance']],
        ]);
    }

    private function todayKpis(Collection $reports, Carbon $today, array $fundDashboard, Collection $payrollRows): array
    {
        $clientPayments = (float) SalaryPayment::where('status', 'approved')
            ->whereDate('approved_at', $today->toDateString())
            ->sum('amount');

        return [
            'orders' => (int) $reports->sum('orders'),
            'spend_usd' => (float) $reports->sum('spend'),
            'spend_bdt' => (float) $reports->sum(fn (DailyPerformanceReport $report) => $report->actualCost()),
            'revenue' => (float) $reports->sum(fn (DailyPerformanceReport $report) => $report->clientRevenue()),
            'profit' => (float) $reports->sum(fn (DailyPerformanceReport $report) => $report->profit()),
            'payroll_paid' => (float) EmployeePayroll::where('payroll_status', 'paid')
                ->whereDate('payment_confirmed_at', $today->toDateString())
                ->sum('paid_amount'),
            'client_payments_received' => $clientPayments,
            'salary_fund_balance' => (float) $fundDashboard['summary']['salary_balance'],
            'ads_fund_balance' => (float) $fundDashboard['summary']['ads_balance'],
            'finance_account_balance' => (float) FinanceAccount::sum('current_balance'),
            'pending_approvals' => $this->pendingApprovalCount($payrollRows),
        ];
    }

    private function monthKpis(Collection $reports, Carbon $from, Carbon $to, array $fundDashboard): array
    {
        $payrollPaid = (float) EmployeePayroll::where('payroll_status', 'paid')
            ->whereBetween('payment_confirmed_at', [$from, $to])
            ->sum('paid_amount');

        return [
            'orders' => (int) $reports->sum('orders'),
            'spend_usd' => (float) $reports->sum('spend'),
            'spend_bdt' => (float) $reports->sum(fn (DailyPerformanceReport $report) => $report->actualCost()),
            'revenue' => (float) $reports->sum(fn (DailyPerformanceReport $report) => $report->clientRevenue()),
            'profit' => (float) $reports->sum(fn (DailyPerformanceReport $report) => $report->profit()),
            'net_profit' => round((float) $reports->sum(fn (DailyPerformanceReport $report) => $report->profit()) - $payrollPaid, 2),
            'payroll_paid' => $payrollPaid,
            'salary_fund_received' => (float) ClientFundLedger::where('fund_type', ClientFundLedger::FUND_EMPLOYEE_SALARY)->where('direction', ClientFundLedger::DIRECTION_CREDIT)->whereBetween('created_at', [$from, $to])->sum('amount_bdt'),
            'salary_fund_used' => (float) ClientFundLedger::where('fund_type', ClientFundLedger::FUND_EMPLOYEE_SALARY)->where('direction', ClientFundLedger::DIRECTION_DEBIT)->whereBetween('created_at', [$from, $to])->sum('amount_bdt'),
            'ads_fund_received' => (float) ClientFundLedger::where('fund_type', ClientFundLedger::FUND_FACEBOOK_ADS)->where('direction', ClientFundLedger::DIRECTION_CREDIT)->whereBetween('created_at', [$from, $to])->sum('amount_bdt'),
            'ads_fund_used' => (float) ClientFundLedger::where('fund_type', ClientFundLedger::FUND_FACEBOOK_ADS)->where('direction', ClientFundLedger::DIRECTION_DEBIT)->whereBetween('created_at', [$from, $to])->sum('amount_bdt'),
            'new_clients' => Client::whereBetween('created_at', [$from, $to])->count(),
            'new_employees' => Employee::whereBetween('created_at', [$from, $to])->count(),
            'current_salary_fund_balance' => (float) $fundDashboard['summary']['salary_balance'],
            'current_ads_fund_balance' => (float) $fundDashboard['summary']['ads_balance'],
        ];
    }

    private function financeSummary(array $fundDashboard, array $legacyFundDashboard, Collection $payrollRows): array
    {
        $funding = FundingBalance::all();
        $cards = FacebookCard::all();
        $financeAccounts = FinanceAccount::all();

        return [
            'finance_accounts' => $financeAccounts->count(),
            'finance_account_balance' => (float) $financeAccounts->sum('current_balance'),
            'binance_balance' => (float) $funding->where('source', 'binance')->sum('current_balance'),
            'facebook_card_balance' => (float) $cards->sum('current_balance'),
            'salary_fund_balance' => (float) $fundDashboard['summary']['salary_balance'],
            'ads_fund_balance' => (float) $fundDashboard['summary']['ads_balance'],
            'outstanding_client_due' => abs((float) $fundDashboard['rows']->sum(fn (array $row) => min((float) $row['funds']['combined_balance'], 0))),
            'outstanding_salary_due' => (float) $legacyFundDashboard['summary']['unpaid_salary_due'],
        ];
    }

    private function clientAnalytics(Collection $reports, Collection $fundRows): array
    {
        $byClient = $reports->groupBy(fn (DailyPerformanceReport $report) => $report->campaign?->client_id ?: 0)
            ->map(function (Collection $items) {
                $sample = $items->first();
                $spend = (float) $items->sum('spend');
                $orders = (int) $items->sum('orders');

                return [
                    'client' => $sample?->campaign?->client,
                    'spend' => $spend,
                    'orders' => $orders,
                    'revenue' => (float) $items->sum(fn (DailyPerformanceReport $report) => $report->clientRevenue()),
                    'profit' => (float) $items->sum(fn (DailyPerformanceReport $report) => $report->profit()),
                    'cpo' => DailyPerformanceReport::costPer($spend, $orders),
                ];
            })
            ->filter(fn (array $row) => $row['client'])
            ->values();

        $balances = $fundRows->map(fn (array $row) => [
            'client' => $row['client'],
            'balance' => (float) $row['funds']['combined_balance'],
            'due' => abs(min((float) $row['funds']['combined_balance'], 0)),
        ]);

        return [
            'highest_spend' => $byClient->sortByDesc('spend')->take(5)->values(),
            'highest_profit' => $byClient->sortByDesc('profit')->take(5)->values(),
            'lowest_balance' => $balances->sortBy('balance')->take(5)->values(),
            'highest_due' => $balances->sortByDesc('due')->take(5)->values(),
            'highest_orders' => $byClient->sortByDesc('orders')->take(5)->values(),
        ];
    }

    private function pageAnalytics(Collection $reports): array
    {
        $rows = $reports->groupBy(fn (DailyPerformanceReport $report) => $report->campaign?->client_page_id ?: 0)
            ->map(function (Collection $items) {
                $sample = $items->first();
                $spend = (float) $items->sum('spend');
                $orders = (int) $items->sum('orders');
                $revenue = (float) $items->sum(fn (DailyPerformanceReport $report) => $report->clientRevenue());

                return [
                    'page' => $sample?->campaign?->page,
                    'orders' => $orders,
                    'spend' => $spend,
                    'cpo' => DailyPerformanceReport::costPer($spend, $orders),
                    'revenue' => $revenue,
                    'profit' => (float) $items->sum(fn (DailyPerformanceReport $report) => $report->profit()),
                    'conversion' => $spend > 0 ? round($orders / $spend, 2) : 0,
                ];
            })
            ->filter(fn (array $row) => $row['page'])
            ->values();

        return [
            'top' => $rows->sortByDesc('orders')->take(8)->values(),
            'lowest' => $rows->sortBy([['orders', 'asc'], ['spend', 'desc']])->take(8)->values(),
        ];
    }

    private function employeeAnalytics(Carbon $from, Carbon $to): array
    {
        $kpis = $this->performanceOperations->kpiRows($from, $to);

        return [
            'top_moderator' => $kpis->sortByDesc('confirmed_orders')->first(),
            'top_ad_manager' => $kpis->sortByDesc('approved_spend')->first(),
            'top_performer' => $kpis->sortByDesc('profit_contribution')->first(),
            'lowest_performer' => $kpis->sortBy('approval_rate')->first(),
            'approval_rate' => round((float) $kpis->avg('approval_rate'), 2),
            'average_orders' => round((float) $kpis->avg('average_orders'), 2),
            'average_spend' => round((float) $kpis->avg('average_spend'), 2),
        ];
    }

    private function alerts(array $fundDashboard, array $legacyFundDashboard, Collection $payrollRows): array
    {
        $today = now()->startOfDay();

        return [
            'negative_salary_fund' => $fundDashboard['rows']->filter(fn (array $row) => (float) $row['funds']['salary']['balance'] < 0)->count(),
            'negative_ads_fund' => $fundDashboard['rows']->filter(fn (array $row) => (float) $row['funds']['ads']['balance'] < 0)->count(),
            'low_finance_accounts' => FinanceAccount::where('status', 'active')->where('current_balance', '<', 1000)->count(),
            'upcoming_salary' => $this->payrollCategory->upcomingCycles($today)->count(),
            'unpaid_salary' => $payrollRows->whereIn('stage.category', [PayrollCategoryService::UNPAID, PayrollCategoryService::FINAL_SETTLEMENT_UNPAID])->count(),
            'pending_daily_performance_merge' => $this->performanceOperations->verificationGroups(['date_from' => $today->copy()->subDays(30)->toDateString(), 'date_to' => $today->toDateString()])->where('status', 'ready_to_merge')->count(),
            'pending_client_payments' => SalaryPayment::where('status', 'pending')->count(),
            'pending_payroll_approval' => EmployeePayroll::where('payroll_status', 'generated')->count(),
            'missing_work_status' => $payrollRows->where('stage.category', PayrollCategoryService::PENDING_WORK_STATUS)->count(),
            'assignment_expired' => EmployeeAssignment::where('status', 'active')->whereNotNull('assigned_to')->whereDate('assigned_to', '<', $today->toDateString())->count(),
            'outstanding_salary_due' => (float) $legacyFundDashboard['summary']['unpaid_salary_due'],
        ];
    }

    private function trends(): array
    {
        $start = now()->subDays(29)->startOfDay();
        $end = now()->endOfDay();
        $reports = $this->performanceReports($start, $end)->get()->groupBy(fn (DailyPerformanceReport $report) => $report->report_date->toDateString());
        $fundLedgers = ClientFundLedger::whereBetween('created_at', [$start, $end])->get()->groupBy(fn (ClientFundLedger $ledger) => $ledger->created_at->toDateString());
        $payrollLedgers = FinanceAccountLedger::where('transaction_type', 'salary_payment')->whereBetween('created_at', [$start, $end])->get()->groupBy(fn (FinanceAccountLedger $ledger) => $ledger->created_at->toDateString());
        $clientPaymentLedgers = FinanceAccountLedger::where('transaction_type', 'client_payment')->whereBetween('created_at', [$start, $end])->get()->groupBy(fn (FinanceAccountLedger $ledger) => $ledger->created_at->toDateString());

        return collect(range(0, 29))->map(function (int $offset) use ($start, $reports, $fundLedgers, $payrollLedgers, $clientPaymentLedgers) {
            $date = $start->copy()->addDays($offset)->toDateString();
            $dailyReports = $reports->get($date, collect());
            $dailyFunds = $fundLedgers->get($date, collect());

            return [
                'date' => $date,
                'orders' => (int) $dailyReports->sum('orders'),
                'spend' => (float) $dailyReports->sum('spend'),
                'revenue' => (float) $dailyReports->sum(fn (DailyPerformanceReport $report) => $report->clientRevenue()),
                'profit' => (float) $dailyReports->sum(fn (DailyPerformanceReport $report) => $report->profit()),
                'salary_fund' => (float) $dailyFunds->where('fund_type', ClientFundLedger::FUND_EMPLOYEE_SALARY)->sum('amount_bdt'),
                'ads_fund' => (float) $dailyFunds->where('fund_type', ClientFundLedger::FUND_FACEBOOK_ADS)->sum('amount_bdt'),
                'client_payments' => (float) $clientPaymentLedgers->get($date, collect())->sum('amount'),
                'payroll_payments' => (float) $payrollLedgers->get($date, collect())->sum('amount'),
            ];
        })->values()->all();
    }

    private function recentActivity(): array
    {
        return [
            'client_payment' => SalaryPayment::with('client')->latest()->first(),
            'salary_payment' => EmployeePayroll::with('employee')->where('payroll_status', 'paid')->latest('payment_confirmed_at')->first(),
            'payroll' => EmployeePayroll::with('employee')->latest()->first(),
            'finance_transaction' => FinanceAccountLedger::with('account')->latest()->first(),
            'daily_performance_merge' => DailyPerformanceReport::with('campaign.client')->whereNotNull('merged_at')->latest('merged_at')->first(),
            'employee_submission' => EmployeeDailySubmission::with('employee')->latest()->first(),
            'assignment' => EmployeeAssignment::with(['employee', 'client'])->latest()->first(),
        ];
    }

    private function quickActions(): array
    {
        return [
            ['label' => 'Receive Client Payment', 'url' => '/admin/salary-payments/create'],
            ['label' => 'Approve Payroll', 'url' => '/admin/payroll?status=generated'],
            ['label' => 'Performance Verification', 'url' => '/admin/performance-verification'],
            ['label' => 'Finance Accounts', 'url' => '/admin/finance/accounts'],
            ['label' => 'Client Management', 'url' => '/admin/clients'],
            ['label' => 'Employees', 'url' => '/admin/employees'],
            ['label' => 'Assignments', 'url' => '/admin/assignments'],
        ];
    }

    private function globalSearchData(): array
    {
        return [
            'clients' => Client::orderBy('company_name')->limit(8)->get(['id', 'company_name']),
            'employees' => Employee::orderBy('name')->limit(8)->get(['id', 'employee_id', 'name']),
            'pages' => ClientPage::orderBy('page_name')->limit(8)->get(['id', 'page_name']),
            'campaigns' => \App\Models\Campaign::orderBy('campaign_name')->limit(8)->get(['id', 'campaign_name', 'campaign_id']),
            'finance_accounts' => FinanceAccount::orderBy('account_name')->limit(8)->get(['id', 'account_name', 'currency']),
        ];
    }

    private function performanceReports(Carbon $from, Carbon $to)
    {
        return DailyPerformanceReport::with(['campaign.client', 'campaign.page'])
            ->whereDate('report_date', '>=', $from->toDateString())
            ->whereDate('report_date', '<=', $to->toDateString());
    }

    private function pendingApprovalCount(Collection $payrollRows): int
    {
        return EmployeeDailySubmission::where('status', 'pending')->count()
            + SalaryPayment::where('status', 'pending')->count()
            + EmployeePayroll::where('payroll_status', 'generated')->count()
            + $payrollRows->where('stage.category', PayrollCategoryService::SALARY_READY)->count();
    }

    private function period(array $filters): array
    {
        $period = $filters['period'] ?? 'today';

        return match ($period) {
            'yesterday' => [now()->subDay()->startOfDay(), now()->subDay()->endOfDay(), 'Yesterday'],
            'this_week' => [now()->startOfWeek(), now()->endOfWeek(), 'This Week'],
            'this_month' => [now()->startOfMonth(), now()->endOfMonth(), 'This Month'],
            'last_month' => [now()->subMonthNoOverflow()->startOfMonth(), now()->subMonthNoOverflow()->endOfMonth(), 'Last Month'],
            'custom' => [
                Carbon::parse($filters['date_from'] ?? now()->toDateString())->startOfDay(),
                Carbon::parse($filters['date_to'] ?? now()->toDateString())->endOfDay(),
                'Custom',
            ],
            default => [now()->startOfDay(), now()->endOfDay(), 'Today'],
        };
    }
}
