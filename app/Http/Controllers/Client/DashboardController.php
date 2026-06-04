<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Payment;
use App\Models\DailyReport;
use App\Services\ClientLedgerService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(ClientLedgerService $ledgerService)
    {
        $client = Client::where('user_id', auth()->id())->firstOrFail();
        $ledger = $ledgerService->build($client);
        $summary = $ledger['summary'];

        $today = date('Y-m-d');

        $todayReports = DailyReport::where('client_id', $client->id)
            ->whereDate('report_date', $today)
            ->get();

        $todaySpend = $todayReports->sum('dollar_spend');
        $todayOrders = $todayReports->sum('orders');

        $payments = Payment::where('client_id', $client->id)->get();

        $pendingPayments = $payments
            ->where('status', 'pending')
            ->sum('amount');

        $totalSpendUsd = $summary['total_spend_usd'];
        $totalOrders = $summary['total_orders'];
        $totalSpendBdt = $summary['total_debit'];
        $approvedPayments = $summary['total_credit'];
        $currentDue = $summary['current_due'];
        $availableBalance = $summary['available_balance'];

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
            'currentDue',
            'availableBalance',
            'avgCostPerOrder',
            'paymentCoverage',
            'recentReports',
            'recentPayments',
            'monthlyReports'
        ));
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
}
