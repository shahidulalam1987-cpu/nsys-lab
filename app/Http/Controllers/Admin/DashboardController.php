<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Payment;
use App\Models\DailyReport;

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

        $totalApprovedPayments = Payment::where('status', 'approved')->sum('amount');
        $totalPendingPayments = Payment::where('status', 'pending')->sum('amount');

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

        return view('admin.dashboard', compact(
            'today',
            'totalClients',
            'activeClients',
            'totalDollarSpend',
            'totalOrders',
            'todayDollarSpend',
            'todayOrders',
            'totalApprovedPayments',
            'totalPendingPayments',
            'totalRevenue',
            'totalCost',
            'totalProfit',
            'totalBalance',
            'recentPayments',
            'recentReports'
        ));
    }
}