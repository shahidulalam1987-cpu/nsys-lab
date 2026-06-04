<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Payment;
use App\Models\DailyReport;

class DashboardController extends Controller
{
    public function index()
    {
        $client = Client::where('user_id', auth()->id())->firstOrFail();

        $today = date('Y-m-d');

        $todayReports = DailyReport::where('client_id', $client->id)
            ->whereDate('report_date', $today)
            ->get();

        $todaySpend = $todayReports->sum('dollar_spend');
        $todayOrders = $todayReports->sum('orders');

        $reports = DailyReport::where('client_id', $client->id)->get();

        $payments = Payment::where('client_id', $client->id)->get();

        $totalSpendUsd = $reports->sum('dollar_spend');
        $totalOrders = $reports->sum('orders');

        $totalSpendBdt = $totalSpendUsd * $client->client_rate;

        $approvedPayments = $payments
            ->where('status', 'approved')
            ->sum('amount');

        $pendingPayments = $payments
            ->where('status', 'pending')
            ->sum('amount');

        $balance = $approvedPayments - $totalSpendBdt;

        $currentDue = $totalSpendBdt - $approvedPayments;

        $avgCostPerOrder = $totalOrders > 0
            ? $totalSpendBdt / $totalOrders
            : 0;

        $paymentCoverage = $totalSpendBdt > 0
            ? ($approvedPayments / $totalSpendBdt) * 100
            : 0;

        $recentReports = DailyReport::where('client_id', $client->id)
            ->latest()
            ->take(5)
            ->get();

        $recentPayments = Payment::with('invoice')
            ->where('client_id', $client->id)
            ->latest()
            ->take(5)
            ->get();

        $monthlyReports = DailyReport::where('client_id', $client->id)
            ->selectRaw('DATE(report_date) as date, SUM(dollar_spend) as spend, SUM(orders) as orders')
            ->groupBy('date')
            ->orderBy('date', 'desc')
            ->take(7)
            ->get()
            ->reverse();

        return view('client.dashboard', compact(
            'client',
            'today',
            'todayReports',
            'todaySpend',
            'todayOrders',
            'totalSpendUsd',
            'totalSpendBdt',
            'totalOrders',
            'approvedPayments',
            'pendingPayments',
            'balance',
            'currentDue',
            'avgCostPerOrder',
            'paymentCoverage',
            'recentReports',
            'recentPayments',
            'monthlyReports'
        ));
    }
}