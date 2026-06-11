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
use App\Models\SalaryPayment;
use App\Services\ClientFundDashboardService;

class DashboardController extends Controller
{
    public function index()
    {
        $today = date('Y-m-d');

        $totalClients = Client::count();
        $activeClients = Client::where('status', 'active')->count();

        $totalDollarSpend = DailyReport::sum('dollar_spend');
        $totalOrders = DailyReport::sum('orders');

        $todayDollarSpend = DailyReport::whereDate('report_date', $today)->sum('dollar_spend');
        $todayOrders = DailyReport::whereDate('report_date', $today)->sum('orders');
        $todayPerformanceReports = DailyPerformanceReport::whereDate('report_date', $today)->get();
        $todayPerformanceSpend = (float) $todayPerformanceReports->sum('spend');
        $todayPerformanceMessages = (int) $todayPerformanceReports->sum('messages');
        $todayPerformanceLeads = (int) $todayPerformanceReports->sum('leads');
        $todayPerformanceOrders = (int) $todayPerformanceReports->sum('orders');
        $todayPerformanceResults = (int) $todayPerformanceReports->sum('results');
        $todayPerformanceCpm = DailyPerformanceReport::costPer($todayPerformanceSpend, $todayPerformanceMessages);
        $todayPerformanceCpl = DailyPerformanceReport::costPer($todayPerformanceSpend, $todayPerformanceLeads);
        $todayPerformanceCpp = DailyPerformanceReport::costPer($todayPerformanceSpend, $todayPerformanceOrders);

        $totalApprovedPayments = Payment::where('status', 'approved')->sum('amount');
        $totalPendingPayments = Payment::where('status', 'pending')->sum('amount');
        $adAccounts = AdAccount::all();
        $totalBusinessManagers = BusinessManager::count();
        $totalAdAccounts = $adAccounts->count();
        $activeAdAccounts = $adAccounts->where('status', 'active')->count();
        $paymentIssueAdAccounts = $adAccounts->where('status', 'payment_issue')->count();
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
            'totalClients',
            'activeClients',
            'totalDollarSpend',
            'totalOrders',
            'todayDollarSpend',
            'todayOrders',
            'todayPerformanceSpend',
            'todayPerformanceMessages',
            'todayPerformanceLeads',
            'todayPerformanceOrders',
            'todayPerformanceResults',
            'todayPerformanceCpm',
            'todayPerformanceCpl',
            'todayPerformanceCpp',
            'totalApprovedPayments',
            'totalPendingPayments',
            'totalBusinessManagers',
            'totalAdAccounts',
            'activeAdAccounts',
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
