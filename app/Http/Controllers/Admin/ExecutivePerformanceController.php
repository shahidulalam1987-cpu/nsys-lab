<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DailyPerformanceReport;
use App\Models\EmployeeDailySubmission;
use App\Models\EmployeePayroll;
use App\Services\ClientFundDashboardService;
use App\Services\PerformanceOperationsService;

class ExecutivePerformanceController extends Controller
{
    public function index(PerformanceOperationsService $operations, ClientFundDashboardService $funds)
    {
        $todayReports = DailyPerformanceReport::with('campaign.client')->whereDate('report_date', today())->get();
        $monthReports = DailyPerformanceReport::with('campaign.client')->whereMonth('report_date', now()->month)->whereYear('report_date', now()->year)->get();
        $todayGroups = $operations->verificationGroups(['date_from' => today()->toDateString(), 'date_to' => today()->toDateString()]);
        $kpis = $operations->kpiRows(now()->startOfMonth(), now()->endOfMonth());
        $summary = [
            'today_spend' => (float) $todayReports->sum('spend'), 'today_orders' => (int) $todayReports->sum('orders'),
            'today_cpo' => DailyPerformanceReport::costPer((float) $todayReports->sum('spend'), (int) $todayReports->sum('orders')),
            'today_profit' => (float) $todayReports->sum(fn ($report) => $report->profit()),
            'pending' => EmployeeDailySubmission::where('status', 'pending')->count(),
            'ready' => $todayGroups->where('status', 'ready_to_merge')->count(),
            'month_spend' => (float) $monthReports->sum('spend'), 'month_orders' => (int) $monthReports->sum('orders'),
            'month_profit' => (float) $monthReports->sum(fn ($report) => $report->profit()),
            'salary_paid' => (float) EmployeePayroll::whereMonth('payment_date', now()->month)->whereYear('payment_date', now()->year)->sum('paid_amount'),
        ];
        $summary['net_profit'] = $summary['month_profit'] - $summary['salary_paid'];

        return view('admin.executive-performance.index', [
            'summary' => $summary, 'topModerator' => $kpis->sortByDesc('confirmed_orders')->first(),
            'topAdManager' => $kpis->sortByDesc('approved_spend')->first(), 'clientFund' => $funds->dashboard()['summary'],
            'alerts' => ['high_spend_low_order' => $monthReports->where('orders', 0)->where('spend', '>', 0)->count(), 'pending_approval' => $summary['pending']],
        ]);
    }
}
