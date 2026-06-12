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
use App\Models\FacebookCard;
use App\Models\SalaryPayment;
use App\Services\ClientFundDashboardService;

class DashboardController extends Controller
{
    public function index(ClientFundDashboardService $clientFundDashboardService)
    {
        $today = date('Y-m-d');

        $totalClients = Client::count();
        $totalEmployees = Employee::count();
        $totalFacebookSpend = (float) DailyPerformanceReport::sum('spend');
        $totalFacebookOrders = (int) DailyPerformanceReport::sum('orders');
        $clientFundDashboard = $clientFundDashboardService->dashboard();
        $clientFundSummary = $clientFundDashboard['summary'];
        $clientFundRows = $clientFundDashboard['rows'];
        $employeeSalaryDue = (float) EmployeePayroll::query()
            ->whereColumn('paid_amount', '<', 'payable_salary')
            ->get()
            ->sum(fn (EmployeePayroll $payroll) => max((float) $payroll->payable_salary - (float) $payroll->paid_amount, 0));
        $employeePayrolls = EmployeePayroll::with(['employee', 'client'])->get();
        $upcomingPayrolls = $employeePayrolls
            ->filter(fn (EmployeePayroll $payroll) => $payroll->matchesStatusFilter('upcoming'));
        $unpaidPayrolls = $employeePayrolls
            ->filter(fn (EmployeePayroll $payroll) => $payroll->matchesStatusFilter('due'));
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
        $employeeAlerts = [
            'upcoming_count' => $upcomingPayrolls->count(),
            'upcoming_amount' => (float) $upcomingPayrolls->sum(fn (EmployeePayroll $payroll) => max((float) $payroll->payable_salary - (float) $payroll->paid_amount, 0)),
            'unpaid_count' => $unpaidPayrolls->count(),
            'unpaid_amount' => (float) $unpaidPayrolls->sum(fn (EmployeePayroll $payroll) => max((float) $payroll->payable_salary - (float) $payroll->paid_amount, 0)),
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
        ];

        return view('admin.dashboard', compact(
            'today',
            'totalClients',
            'totalEmployees',
            'totalFacebookSpend',
            'totalFacebookOrders',
            'clientFundSummary',
            'clientFundRows',
            'employeeSalaryDue',
            'facebookBillingAlerts',
            'employeeAlerts',
            'facebookAlerts',
            'cardAlerts',
            'cards'
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
        $activeEmployees = Employee::where('status', 'active')->count();
        $probationEmployees = Employee::where('status', 'probation')->count();
        $attendanceRecords = EmployeeAttendance::whereMonth('attendance_date', now()->month)
            ->whereYear('attendance_date', now()->year)
            ->count();
        $pendingSalaryPayments = SalaryPayment::where('status', 'pending')->sum('amount');
        $recentEmployees = Employee::latest()->take(5)->get();
        $recentSalaryPayments = SalaryPayment::with('client')->latest()->take(5)->get();

        return view('admin.employee-dashboard', compact(
            'totalEmployees',
            'activeEmployees',
            'probationEmployees',
            'attendanceRecords',
            'pendingSalaryPayments',
            'clientFundSummary',
            'recentEmployees',
            'recentSalaryPayments'
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
