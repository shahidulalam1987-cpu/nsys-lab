<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdAccount;
use App\Models\BusinessManager;
use App\Models\Client;
use App\Models\Payment;
use App\Models\DailyReport;
use App\Models\DailyPerformanceReport;
use App\Models\Employee;
use App\Models\EmployeeAttendance;
use App\Models\EmployeePayroll;
use App\Models\CardTransaction;
use App\Models\FacebookCard;
use App\Models\FundingBalance;
use App\Models\SalaryPayment;
use App\Services\ClientFundDashboardService;
use App\Services\NotificationCenterService;

class DashboardController extends Controller
{
    public function index(ClientFundDashboardService $clientFundDashboardService, NotificationCenterService $notificationCenterService)
    {
        $today = date('Y-m-d');

        $totalClients = Client::count();
        $totalEmployees = Employee::count();
        $clientAssignedEmployees = Employee::where('employee_type', 'client_assigned')->count();
        $agencyInternalEmployees = Employee::where('employee_type', 'agency_internal')->count();
        $employeeDepartmentCounts = Employee::selectRaw('department, COUNT(*) as total')
            ->groupBy('department')
            ->pluck('total', 'department');
        $totalFacebookSpend = (float) DailyPerformanceReport::sum('spend');
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
        $netAvailableFund = (float) ($clientFundSummary['available_balance'] ?? 0) - (float) ($clientFundSummary['upcoming_salary'] ?? 0);
        $clientFundRows = $clientFundDashboard['rows'];
        $employeeSalaryDue = (float) EmployeePayroll::current()
            ->whereColumn('paid_amount', '<', 'payable_salary')
            ->get()
            ->sum(fn (EmployeePayroll $payroll) => max((float) $payroll->payable_salary - (float) $payroll->paid_amount, 0));
        $employeePayrolls = EmployeePayroll::current()->with(['employee', 'client'])->get();
        $upcomingPayrolls = $employeePayrolls
            ->filter(fn (EmployeePayroll $payroll) => $payroll->matchesStatusFilter('upcoming') && $payroll->employee?->status !== 'terminated');
        $unpaidPayrolls = $employeePayrolls
            ->filter(fn (EmployeePayroll $payroll) => $payroll->matchesStatusFilter('due') && $payroll->employee?->status !== 'terminated');
        $finalSettlementPayrolls = $employeePayrolls
            ->filter(fn (EmployeePayroll $payroll) => $payroll->isFinalSettlementDue());
        $adAccounts = AdAccount::all();
        $facebookBillingAlerts = $adAccounts
            ->filter(fn (AdAccount $account) => in_array($account->billingStatus(), ['upcoming', 'overdue'], true))
            ->count();
        $paymentIssueAdAccounts = $adAccounts->where('status', 'payment_issue')->count();
        $upcomingBillingAccounts = $adAccounts->filter(fn (AdAccount $account) => $account->billingStatus() === 'upcoming')->count();
        $overdueBillingAccounts = $adAccounts->filter(fn (AdAccount $account) => $account->billingStatus() === 'overdue')->count();
        $lowBalanceAccounts = $adAccounts->filter(fn (AdAccount $account) => in_array($account->balanceStatus(), ['low', 'negative'], true))->count();
        $criticalThresholdAccounts = $adAccounts->filter(fn (AdAccount $account) => in_array($account->thresholdStatus(), ['critical', 'limit_reached'], true))->count();
        $monthPerformance = DailyPerformanceReport::whereMonth('report_date', now()->month)
            ->whereYear('report_date', now()->year)
            ->get();
        $cards = FacebookCard::with('adAccount')->latest()->get();
        $totalCardBalance = (float) $cards->sum('current_balance');
        $lowBalanceCards = $cards->filter(fn (FacebookCard $card) => $card->effectiveStatus() === 'low_balance')->count();
        $disabledCards = $cards->where('status', 'disabled')->count();
        $negativeBalanceCards = $cards->filter(fn (FacebookCard $card) => (float) $card->current_balance < 0)->count();
        $highFeeTransactions = CardTransaction::where('fee_usd', '>=', 5)->count();
        $employeeAlerts = [
            'upcoming_count' => $upcomingPayrolls->count(),
            'upcoming_amount' => (float) $upcomingPayrolls->sum(fn (EmployeePayroll $payroll) => max((float) $payroll->payable_salary - (float) $payroll->paid_amount, 0)),
            'unpaid_count' => $unpaidPayrolls->count(),
            'unpaid_amount' => (float) $unpaidPayrolls->sum(fn (EmployeePayroll $payroll) => max((float) $payroll->payable_salary - (float) $payroll->paid_amount, 0)),
            'final_settlement_count' => $finalSettlementPayrolls->count(),
            'final_settlement_amount' => (float) $finalSettlementPayrolls->sum(fn (EmployeePayroll $payroll) => max((float) $payroll->payable_salary - (float) $payroll->paid_amount, 0)),
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
        $cardAlerts = [
            'total_balance' => $totalCardBalance,
            'low_balance_cards' => $lowBalanceCards,
            'disabled_cards' => $disabledCards,
            'negative_balance_cards' => $negativeBalanceCards,
            'high_fee_transactions' => $highFeeTransactions,
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
        $notificationSummary = $notificationCenterService->summary();
        $notificationGroups = $notificationCenterService->groupedOpenNotifications();

        return view('admin.dashboard', compact(
            'today',
            'totalClients',
            'totalEmployees',
            'clientAssignedEmployees',
            'agencyInternalEmployees',
            'employeeDepartmentCounts',
            'totalFacebookSpend',
            'totalFacebookOrders',
            'usdProfitSummary',
            'clientFundSummary',
            'clientFundRows',
            'netAvailableFund',
            'employeeSalaryDue',
            'facebookBillingAlerts',
            'employeeAlerts',
            'facebookAlerts',
            'cardAlerts',
            'fundingAlerts',
            'cards',
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

    public function tiktokPlaceholder()
    {
        return view('admin.tiktok.placeholder');
    }

    public function employeeDepartment(ClientFundDashboardService $clientFundDashboardService)
    {
        $clientFundDashboard = $clientFundDashboardService->dashboard();
        $clientFundSummary = $clientFundDashboard['summary'];
        $totalEmployees = Employee::count();
        $clientAssignedEmployees = Employee::where('employee_type', 'client_assigned')->count();
        $agencyInternalEmployees = Employee::where('employee_type', 'agency_internal')->count();
        $departmentCounts = Employee::selectRaw('department, COUNT(*) as total')
            ->groupBy('department')
            ->pluck('total', 'department');
        $activeEmployees = Employee::where('status', 'active')->count();
        $probationEmployees = Employee::where('status', 'probation')->count();
        $attendanceRecords = EmployeeAttendance::whereMonth('attendance_date', now()->month)
            ->whereYear('attendance_date', now()->year)
            ->count();
        $pendingSalaryPayments = SalaryPayment::where('status', 'pending')->sum('amount');
        $recentEmployees = Employee::latest()->take(5)->get();
        $recentSalaryPayments = SalaryPayment::with('client')->latest()->take(5)->get();
        $employeePayrolls = EmployeePayroll::current()->with('employee')->get();
        $employeeDashboardAlerts = [
            'upcoming_count' => $employeePayrolls->filter(fn (EmployeePayroll $payroll) => $payroll->matchesStatusFilter('upcoming') && $payroll->employee?->status !== 'terminated')->count(),
            'unpaid_count' => $employeePayrolls->filter(fn (EmployeePayroll $payroll) => $payroll->matchesStatusFilter('due') && $payroll->employee?->status !== 'terminated')->count(),
            'final_settlement_count' => $employeePayrolls->filter(fn (EmployeePayroll $payroll) => $payroll->isFinalSettlementDue())->count(),
            'final_settlement_amount' => (float) $employeePayrolls->filter(fn (EmployeePayroll $payroll) => $payroll->isFinalSettlementDue())
                ->sum(fn (EmployeePayroll $payroll) => max((float) $payroll->payable_salary - (float) $payroll->paid_amount, 0)),
        ];

        return view('admin.employee-dashboard', compact(
            'totalEmployees',
            'clientAssignedEmployees',
            'agencyInternalEmployees',
            'departmentCounts',
            'activeEmployees',
            'probationEmployees',
            'attendanceRecords',
            'pendingSalaryPayments',
            'clientFundSummary',
            'recentEmployees',
            'recentSalaryPayments',
            'employeeDashboardAlerts'
        ));
    }

    public function clientDepartment(ClientFundDashboardService $clientFundDashboardService)
    {
        $clientFundDashboard = $clientFundDashboardService->dashboard();
        $clientFundSummary = $clientFundDashboard['summary'];
        $totalClients = Client::count();
        $activeClients = Client::where('status', 'active')->count();
        $pendingClientPayments = SalaryPayment::where('status', 'pending')->sum('amount');
        $recentClients = Client::latest()->take(5)->get();
        $recentClientPayments = SalaryPayment::with('client')->latest()->take(5)->get();

        return view('admin.client-dashboard', compact(
            'clientFundSummary',
            'totalClients',
            'activeClients',
            'pendingClientPayments',
            'recentClients',
            'recentClientPayments'
        ));
    }
}
