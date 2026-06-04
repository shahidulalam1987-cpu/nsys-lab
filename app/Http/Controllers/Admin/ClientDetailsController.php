<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Payment;
use App\Models\DailyReport;

class ClientDetailsController extends Controller
{
    public function show($id)
    {
        $client = Client::findOrFail($id);

        $reports = DailyReport::where('client_id', $client->id)
            ->latest()
            ->get();

        $payments = Payment::where('client_id', $client->id)
            ->latest()
            ->get();

        $approvedPayment = $payments
            ->where('status', 'approved')
            ->sum('amount');

        $pendingPayment = $payments
            ->where('status', 'pending')
            ->sum('amount');

        $totalDollarSpend = $reports->sum('dollar_spend');
        $totalOrders = $reports->sum('orders');

        $totalRevenue = $totalDollarSpend * $client->client_rate;
        $totalCost = $totalDollarSpend * $client->buy_rate;
        $totalProfit = $totalRevenue - $totalCost;

        $balance = $approvedPayment - $totalRevenue;

        return view('admin.clients.show', compact(
            'client',
            'reports',
            'payments',
            'approvedPayment',
            'pendingPayment',
            'totalDollarSpend',
            'totalOrders',
            'totalRevenue',
            'totalCost',
            'totalProfit',
            'balance'
        ));
    }
}