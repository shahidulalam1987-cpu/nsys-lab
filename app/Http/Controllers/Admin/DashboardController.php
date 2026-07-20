<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdAccount;
use App\Models\BusinessManager;
use App\Models\Client;
use App\Models\Payment;
use App\Models\DailyReport;
use App\Models\DailyPerformanceReport;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeAttendance;
use App\Models\EmployeePayroll;
use App\Models\CardTransaction;
use App\Models\FundingBalance;
use App\Models\SalaryPayment;
use App\Services\ClientFundDashboardService;
use App\Services\NotificationCenterService;
use App\Services\PayrollCategoryService;

class DashboardController extends Controller
{
    public function index(ClientFundDashboardService $clientFundDashboardService, NotificationCenterService $notificationCenterService, PayrollCategoryService $payrollCategoryService)
    {
        $today = date('Y-m-d');

        $totalClients = Client::count();
        $totalEmployees = Employee::count();
        $totalFacebookOrders = (int) DailyPerformanceReport::sum('orders');
        $todayPerformance = DailyPerformanceReport::whereDate('report_date', $today)->get();
        $todayUsdSpend = (float) $todayPerformance->sum('spend');
        $monthlyPerformance = DailyPerformanceReport::whereMonth('report_date', now()->month)
            ->whereYear('report_date', now()->year)
            ->get();
        $monthlyUsdSpend = (float) $monthlyPerformance->sum('spend');
        $todayCardTransactions = CardTransaction::whereDate('transaction_date', $today)->get();
        $monthlyCardTransactions = CardTransaction::whereMonth('transaction_date', now()->month)
            ->whereYear('transaction_date', now()->year)
            ->get();
        $usdProfitSummary = [
            'target_profit_per_usd' => 15,
            'today_usd_spend' => $todayUsdSpend,
            'today_estimated_profit' => round($todayUsdSpend * 15, 2),
            'monthly_usd_spend' => $monthlyUsdSpend,
            'monthly_estimated_profit' => round($monthlyUsdSpend * 15, 2),
            'average_profit_per_usd' => $monthlyUsdSpend > 0 ? 15 : 0,
            'actual_profit_available' => $monthlyCardTransactions->isNotEmpty(),
            'today_actual_profit' => (float) $todayCardTransactions->sum('net_profit'),
            'monthly_actual_profit' => (float) $monthlyCardTransactions->sum('net_profit'),
            'actual_profit_per_usd' => (float) $monthlyCardTransactions->sum('spend_usd') > 0
                ? round((float) $monthlyCardTransactions->sum('net_profit') / (float) $monthlyCardTransactions->sum('spend_usd'), 2)
                : 0,
        ];
        $clientFundDashboard = $clientFundDashboardService->dashboard();
        $clientFundSummary = $clientFundDashboard['summary'];
        $payrollStages = $payrollCategoryService->employeeStages();
        $dueCategories = [
            PayrollCategoryService::PENDING_WORK_STATUS,
            PayrollCategoryService::SALARY_READY,
            PayrollCategoryService::GENERATED,
            PayrollCategoryService::UNPAID,
            PayrollCategoryService::FINAL_SETTLEMENT_PENDING,
            PayrollCategoryService::FINAL_SETTLEMENT_UNPAID,
        ];
        $dueStages = $payrollStages->filter(fn (array $row) => in_array(data_get($row, 'stage.category'), $dueCategories, true));
        $employeeSalaryDue = (float) $dueStages->sum(fn (array $row) => $this->stageDueAmount($row['stage']));
        $upcomingCycles = $payrollCategoryService->upcomingCycles();
        $unpaidStages = $payrollStages->filter(fn (array $row) => in_array(data_get($row, 'stage.category'), [
            PayrollCategoryService::PENDING_WORK_STATUS,
            PayrollCategoryService::SALARY_READY,
            PayrollCategoryService::GENERATED,
            PayrollCategoryService::UNPAID,
        ], true));
        $finalSettlementStages = $payrollStages->filter(fn (array $row) => in_array(data_get($row, 'stage.category'), [
            PayrollCategoryService::FINAL_SETTLEMENT_PENDING,
            PayrollCategoryService::FINAL_SETTLEMENT_UNPAID,
        ], true));
        $adAccounts = AdAccount::all();
        $paymentIssueAdAccounts = $adAccounts->where('status', 'payment_issue')->count();
        $upcomingBillingAccounts = $adAccounts->filter(fn (AdAccount $account) => $account->billingStatus() === 'upcoming')->count();
        $overdueBillingAccounts = $adAccounts->filter(fn (AdAccount $account) => $account->billingStatus() === 'overdue')->count();
        $lowBalanceAccounts = $adAccounts->filter(fn (AdAccount $account) => in_array($account->balanceStatus(), ['low', 'negative'], true))->count();
        $criticalThresholdAccounts = $adAccounts->filter(fn (AdAccount $account) => in_array($account->thresholdStatus(), ['critical', 'limit_reached'], true))->count();
        $monthPerformance = DailyPerformanceReport::whereMonth('report_date', now()->month)
            ->whereYear('report_date', now()->year)
            ->get();
        $employeeAlerts = [
            'upcoming_count' => $upcomingCycles->count(),
            'upcoming_amount' => (float) $upcomingCycles->sum(fn (array $cycle) => (float) data_get($cycle, 'estimate.estimated_payable_salary', 0)),
            'unpaid_count' => $unpaidStages->count(),
            'unpaid_amount' => (float) $unpaidStages->sum(fn (array $row) => $this->stageDueAmount($row['stage'])),
            'final_settlement_count' => $finalSettlementStages->count(),
            'final_settlement_amount' => (float) $finalSettlementStages->sum(fn (array $row) => $this->stageDueAmount($row['stage'])),
        ];
        $facebookAlerts = [
            'upcoming_billing_accounts' => $upcomingBillingAccounts,
            'overdue_billing_accounts' => $overdueBillingAccounts,
            'payment_issue_accounts' => $paymentIssueAdAccounts,
            'low_balance_accounts' => $lowBalanceAccounts,
            'critical_threshold_accounts' => $criticalThresholdAccounts,
            'monthly_spend' => (float) $monthPerformance->sum('spend'),
            'monthly_transactions' => $monthPerformance->count(),
            'monthly_billing_amount' => (float) $monthPerformance->sum('spend'),
        ];
        $fundingBalances = FundingBalance::all()->keyBy('source');
        $fundingAlerts = [
            'binance_balance' => (float) ($fundingBalances->get('binance')?->current_balance ?? 0),
            'redotpay_balance' => (float) ($fundingBalances->get('redotpay')?->current_balance ?? 0),
            'tavao_balance' => (float) ($fundingBalances->get('tavao')?->current_balance ?? 0),
        ];
        $fundingAlerts['total_available_usd'] = $fundingAlerts['binance_balance']
            + $fundingAlerts['redotpay_balance']
            + $fundingAlerts['tavao_balance'];
        $notificationSummary = $notificationCenterService->readSummary();
        $notificationGroups = $notificationCenterService->readGroupedOpenNotifications();

        return view('admin.dashboard', compact(
            'today',
            'totalClients',
            'totalEmployees',
            'totalFacebookOrders',
            'usdProfitSummary',
            'clientFundSummary',
            'employeeSalaryDue',
            'employeeAlerts',
            'facebookAlerts',
            'fundingAlerts',
            'notificationSummary',
            'notificationGroups'
        ));
    }

    public function facebookDashboard()
    {
        $today = date('Y-m-d');

        $totalDollarSpend = DailyReport::sum('dollar_spend');
        $totalOrders = DailyReport::sum('orders');

        $todayDollarSpend = DailyReport::whereDate('report_date', $today)->sum('dollar_spend');
        $todayOrders = DailyReport::whereDate('report_date', $today)->sum('orders');
        $todayPerformanceReports = DailyPerformanceReport::whereDate('report_date', $today)->get();
        $todayPerformanceSpend = (float) $todayPerformanceReports->sum('spend');
        $todayPerformanceOrders = (int) $todayPerformanceReports->sum('orders');
        $todayPerformanceCpp = DailyPerformanceReport::costPer($todayPerformanceSpend, $todayPerformanceOrders);

        $totalApprovedPayments = Payment::where('status', 'approved')->sum('amount');
        $totalPendingPayments = Payment::where('status', 'pending')->sum('amount');
        $adAccounts = AdAccount::all();
        $totalBusinessManagers = BusinessManager::count();
        $totalAdAccounts = $adAccounts->count();
        $activeAdAccounts = $adAccounts->where('status', 'active')->count();
        $paymentIssueAdAccounts = $adAccounts->where('status', 'payment_issue')->count();
        $activeCampaigns = \App\Models\Campaign::where('status', 'active')->count();
        $totalThreshold = (float) $adAccounts->sum('threshold_amount');
        $remainingThreshold = (float) $adAccounts->sum(fn (AdAccount $account) => $account->remaining_threshold);
        $adAccountCurrentBalance = (float) $adAccounts->sum('current_balance');
        $upcomingBillingAccounts = $adAccounts->filter(fn (AdAccount $account) => $account->billingStatus() === 'upcoming')->count();
        $criticalAdAccounts = $adAccounts->filter(fn (AdAccount $account) => in_array($account->thresholdStatus(), ['critical', 'limit_reached'], true)
            || in_array($account->balanceStatus(), ['low', 'negative'], true)
            || $account->billingStatus() === 'overdue'
            || $account->status === 'payment_issue'
        )->count();

        $reports = DailyReport::with('client')->get();

        $totalRevenue = 0;
        $totalCost = 0;
        $totalProfit = 0;

        foreach ($reports as $report) {
            $clientRate = $report->client->client_rate ?? 0;
            $buyRate = $report->client->buy_rate ?? 0;

            $revenue = $report->dollar_spend * $clientRate;
            $cost = $report->dollar_spend * $buyRate;
            $profit = $revenue - $cost;

            $totalRevenue += $revenue;
            $totalCost += $cost;
            $totalProfit += $profit;
        }

        $totalBalance = $totalApprovedPayments - $totalRevenue;

        $recentPayments = Payment::with('client')
            ->latest()
            ->take(5)
            ->get();

        $recentReports = DailyReport::with('client')
            ->latest()
            ->take(5)
            ->get();
        $recentPerformanceReports = DailyPerformanceReport::with(['campaign.client', 'campaign.page'])
            ->latest('report_date')
            ->take(5)
            ->get();

        return view('admin.dashboard', compact(
            'today',
            'totalDollarSpend',
            'totalOrders',
            'todayDollarSpend',
            'todayOrders',
            'todayPerformanceSpend',
            'todayPerformanceOrders',
            'todayPerformanceCpp',
            'totalApprovedPayments',
            'totalPendingPayments',
            'totalBusinessManagers',
            'totalAdAccounts',
            'activeAdAccounts',
            'activeCampaigns',
            'paymentIssueAdAccounts',
            'totalThreshold',
            'remainingThreshold',
            'adAccountCurrentBalance',
            'upcomingBillingAccounts',
            'criticalAdAccounts',
            'totalRevenue',
            'totalCost',
            'totalProfit',
            'totalBalance',
            'recentPayments',
            'recentReports',
            'recentPerformanceReports'
        ));
    }

    public function employeeDepartment(PayrollCategoryService $payrollCategoryService)
    {
        $totalEmployees = Employee::count();
        $clientAssignedEmployees = Employee::where('employee_type', 'client_assigned')->count();
        $agencyInternalEmployees = Employee::where('employee_type', 'agency_internal')->count();
        $departmentCounts = Department::query()
            ->withCount('employees')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->filter(fn (Department $department) => $department->employees_count > 0)
            ->pluck('employees_count', 'name');
        $attendanceRecords = EmployeeAttendance::whereMonth('attendance_date', now()->month)
            ->whereYear('attendance_date', now()->year)
            ->count();
        $recentEmployees = Employee::with(['departmentRecord', 'roleRecord'])->latest()->take(5)->get();
        $upcomingStages = $payrollCategoryService->upcomingCycles();
        $payrollStages = $payrollCategoryService->employeeStages();
        $unpaidStages = $payrollStages->filter(fn (array $row) => in_array(data_get($row, 'stage.category'), [
            PayrollCategoryService::PENDING_WORK_STATUS,
            PayrollCategoryService::SALARY_READY,
            PayrollCategoryService::GENERATED,
            PayrollCategoryService::UNPAID,
        ], true));
        $finalSettlementStages = $payrollStages->filter(fn (array $row) => in_array(data_get($row, 'stage.category'), [
            PayrollCategoryService::FINAL_SETTLEMENT_PENDING,
            PayrollCategoryService::FINAL_SETTLEMENT_UNPAID,
        ], true));
        $employeeDashboardAlerts = [
            'upcoming_count' => $upcomingStages->count(),
            'upcoming_amount' => (float) $upcomingStages->sum(fn (array $row) => (float) data_get($row, 'estimate.estimated_payable_salary', 0)),
            'unpaid_count' => $unpaidStages->count(),
            'unpaid_amount' => (float) $unpaidStages->sum(fn (array $row) => $this->stageDueAmount($row['stage'])),
            'final_settlement_count' => $finalSettlementStages->count(),
            'final_settlement_amount' => (float) $finalSettlementStages->sum(fn (array $row) => $this->stageDueAmount($row['stage'])),
        ];

        return view('admin.employee-dashboard', compact(
            'totalEmployees',
            'clientAssignedEmployees',
            'agencyInternalEmployees',
            'departmentCounts',
            'attendanceRecords',
            'recentEmployees',
            'employeeDashboardAlerts'
        ));
    }

    public function clientDepartment(ClientFundDashboardService $clientFundDashboardService)
    {
        $clientFundDashboard = $clientFundDashboardService->dashboard();
        $clientFundSummary = $clientFundDashboard['summary'];
        $totalClients = Client::count();
        $recentClients = Client::latest()->take(5)->get();
        $recentClientPayments = SalaryPayment::with('client')->latest()->take(5)->get();

        return view('admin.client-dashboard', compact(
            'clientFundSummary',
            'totalClients',
            'recentClients',
            'recentClientPayments'
        ));
    }

    private function stageDueAmount(array $stage): float
    {
        $payroll = data_get($stage, 'payroll');

        return $payroll
            ? max((float) $payroll->payable_salary - (float) $payroll->paid_amount, 0)
            : (float) data_get($stage, 'estimate.estimated_payable_salary', 0);
    }
}
