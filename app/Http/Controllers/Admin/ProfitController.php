<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\DailyReport;

class ProfitController extends Controller
{
    public function index()
    {
        $reports = DailyReport::with('client')
            ->latest()
            ->get();

        $totalProfit = 0;

        foreach ($reports as $report) {

            $clientRate = $report->client->client_rate ?? 0;
            $buyRate = $report->client->buy_rate ?? 0;

            $profitPerDollar = $clientRate - $buyRate;

            $report->profit = $report->dollar_spend * $profitPerDollar;

            $totalProfit += $report->profit;
        }

        return view('admin.profit-history', compact(
            'reports',
            'totalProfit'
        ));
    }
}