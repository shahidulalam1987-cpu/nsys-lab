<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Payment;
use App\Models\DailyReport;
use Illuminate\Http\Request;

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

    public function statement(Request $request)
    {
        $filters = $request->validate([
            'from_date' => ['nullable', 'date'],
            'to_date' => ['nullable', 'date', 'after_or_equal:from_date'],
        ]);

        $client = Client::where('user_id', auth()->id())->firstOrFail();

        $reports = DailyReport::where('client_id', $client->id)
            ->when($filters['from_date'] ?? null, fn ($query, $date) => $query->whereDate('report_date', '>=', $date))
            ->when($filters['to_date'] ?? null, fn ($query, $date) => $query->whereDate('report_date', '<=', $date))
            ->orderBy('report_date')
            ->orderBy('id')
            ->get();

        $payments = Payment::where('client_id', $client->id)
            ->where('status', 'approved')
            ->when($filters['from_date'] ?? null, fn ($query, $date) => $query->whereDate('created_at', '>=', $date))
            ->when($filters['to_date'] ?? null, fn ($query, $date) => $query->whereDate('created_at', '<=', $date))
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();

        $clientRate = max((float) ($client->client_rate ?? 0), 0);

        $ledgerRows = $reports->map(function (DailyReport $report) use ($clientRate) {
            $debit = (float) $report->dollar_spend * $clientRate;

            return [
                'date' => $report->report_date,
                'sort_date' => $report->report_date . ' 00:00:00',
                'transaction_type' => 'Ad Spend',
                'page' => $report->page_name,
                'debit' => $debit,
                'credit' => 0,
            ];
        })->merge($payments->map(function (Payment $payment) {
            return [
                'date' => $payment->created_at->format('Y-m-d'),
                'sort_date' => $payment->created_at->format('Y-m-d H:i:s'),
                'transaction_type' => 'Payment',
                'page' => '-',
                'debit' => 0,
                'credit' => (float) $payment->amount,
            ];
        }))->sortBy('sort_date')->values();

        $runningBalance = 0;
        $ledgerRows = $ledgerRows->map(function (array $row) use (&$runningBalance) {
            $runningBalance += $row['debit'] - $row['credit'];
            $row['running_balance'] = $runningBalance;

            return $row;
        });

        $totalSpend = $ledgerRows->sum('debit');
        $totalPaid = $ledgerRows->sum('credit');
        $currentDue = max($totalSpend - $totalPaid, 0);
        $currentBalance = $totalPaid - $totalSpend;

        return view('client.statement', compact(
            'client',
            'filters',
            'ledgerRows',
            'totalSpend',
            'totalPaid',
            'currentDue',
            'currentBalance'
        ));
    }
}
