<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Payment;
use App\Models\DailyReport;
use App\Models\DailyPerformanceReport;
use App\Models\SalaryPayment;
use App\Services\ClientLedgerService;
use App\Services\SalaryFundService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(ClientLedgerService $ledgerService)
    {
        $client = Client::where('user_id', auth()->id())->firstOrFail();
        $ledger = $ledgerService->build($client);
        $summary = $ledger['summary'];

        $today = date('Y-m-d');

        $modernQuery = $client->dailyPerformanceReports()->with(['campaign.page']);
        $todayReports = (clone $modernQuery)->whereDate('report_date', $today)->get();

        $todaySpend = (float) $todayReports->sum('spend');
        $todayOrders = $todayReports->sum('orders');
        $todayCostPerOrder = DailyPerformanceReport::costPer($todaySpend, (int) $todayOrders);

        $payments = Payment::where('client_id', $client->id)->get();

        $pendingPayments = $payments
            ->where('status', 'pending')
            ->sum('amount');

        $modernReports = (clone $modernQuery)->get();
        $totalSpendUsd = (float) $modernReports->sum('spend');
        $totalOrders = (int) $modernReports->sum('orders');
        $totalSpendBdt = $summary['total_debit'];
        $approvedPayments = $summary['total_credit'];
        $currentDue = $summary['current_due'];
        $availableBalance = $summary['available_balance'];

        $avgCostPerOrder = DailyPerformanceReport::costPer($totalSpendUsd, $totalOrders);

        $paymentCoverage = $totalSpendBdt > 0
            ? ($approvedPayments / $totalSpendBdt) * 100
            : 0;

        $recentReports = (clone $modernQuery)
            ->latest('report_date')
            ->take(5)
            ->get();

        $legacyReports = DailyReport::where('client_id', $client->id)
            ->latest('report_date')
            ->take(5)
            ->get();

        $recentPayments = Payment::with('invoice')
            ->where('client_id', $client->id)
            ->latest()
            ->take(5)
            ->get();

        $monthlyReports = $modernReports->groupBy(fn ($report) => $report->report_date->toDateString())
            ->map(fn ($reports, $date) => (object) [
                'date' => $date,
                'spend' => (float) $reports->sum('spend'),
                'orders' => (int) $reports->sum('orders'),
            ])->sortBy('date')->take(-7)->values();
        $pagePerformance = $modernReports->groupBy(fn ($report) => $report->campaign?->page?->page_name ?: 'No Page')
            ->map(fn ($reports, $label) => $this->performanceRow($label, $reports))->values();
        $campaignPerformance = $modernReports->groupBy(fn ($report) => $report->campaign?->campaign_name ?: 'No Campaign')
            ->map(fn ($reports, $label) => $this->performanceRow($label, $reports))->values();

        return view('client.dashboard', compact(
            'client',
            'today',
            'todayReports',
            'todaySpend',
            'todayOrders',
            'todayCostPerOrder',
            'totalSpendUsd',
            'totalSpendBdt',
            'totalOrders',
            'approvedPayments',
            'pendingPayments',
            'currentDue',
            'availableBalance',
            'avgCostPerOrder',
            'paymentCoverage',
            'recentReports',
            'legacyReports',
            'recentPayments',
            'monthlyReports',
            'pagePerformance',
            'campaignPerformance'
        ));
    }

    public function performanceReport(Request $request)
    {
        $filters = $request->validate([
            'from_date' => ['nullable', 'date'],
            'to_date' => ['nullable', 'date', 'after_or_equal:from_date'],
            'page_id' => ['nullable', 'integer'],
            'campaign_id' => ['nullable', 'integer'],
        ]);
        $client = Client::where('user_id', auth()->id())->firstOrFail();
        $reports = $client->dailyPerformanceReports()
            ->with(['campaign.page'])
            ->when($filters['from_date'] ?? null, fn ($query, $date) => $query->whereDate('report_date', '>=', $date))
            ->when($filters['to_date'] ?? null, fn ($query, $date) => $query->whereDate('report_date', '<=', $date))
            ->when($filters['page_id'] ?? null, fn ($query, $id) => $query->whereHas('campaign', fn ($campaign) => $campaign->where('client_page_id', $id)))
            ->when($filters['campaign_id'] ?? null, fn ($query, $id) => $query->where('daily_performance_reports.campaign_id', $id))
            ->latest('daily_performance_reports.report_date')
            ->get();

        return view('client.performance-report', [
            'client' => $client,
            'reports' => $reports,
            'filters' => $filters,
            'pages' => $client->pages()->orderBy('page_name')->get(),
            'campaigns' => $client->campaigns()->orderBy('campaign_name')->get(),
        ]);
    }

    public function statement(Request $request, ClientLedgerService $ledgerService)
    {
        $filters = $request->validate([
            'from_date' => ['nullable', 'date'],
            'to_date' => ['nullable', 'date', 'after_or_equal:from_date'],
        ]);

        $client = Client::where('user_id', auth()->id())->firstOrFail();

        $ledger = $ledgerService->build($client, $filters);
        $ledgerRows = $ledger['rows'];
        $summary = $ledger['summary'];
        $totalSpend = $summary['total_debit'];
        $totalPaid = $summary['total_credit'];
        $currentDue = $summary['current_due'];
        $availableBalance = $summary['available_balance'];

        return view('client.statement', compact(
            'client',
            'filters',
            'ledgerRows',
            'totalSpend',
            'totalPaid',
            'currentDue',
            'availableBalance'
        ));
    }

    public function employeeDepartment(Request $request, SalaryFundService $salaryFundService)
    {
        $client = Client::where('user_id', auth()->id())->firstOrFail();
        $assignments = $client->employeeAssignments()
            ->with('employee')
            ->latest('assigned_from')
            ->get();
        $fund = $salaryFundService->build($client, $request->salary_month);
        $recentSalaryPayments = SalaryPayment::where('client_id', $client->id)
            ->latest()
            ->take(5)
            ->get();

        return view('client.employee-dashboard', compact(
            'client',
            'assignments',
            'fund',
            'recentSalaryPayments'
        ));
    }

    private function performanceRow(string $label, $reports): array
    {
        $spend = (float) $reports->sum('spend');
        $orders = (int) $reports->sum('orders');

        return ['label' => $label, 'spend' => $spend, 'orders' => $orders, 'cpp' => DailyPerformanceReport::costPer($spend, $orders)];
    }
}
