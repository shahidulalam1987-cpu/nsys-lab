<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\User;
use Illuminate\Http\Request;

class ClientController extends Controller
{
    public function index(Request $request)
    {
        $query = Client::with(['payments', 'dailyReports']);

        if ($request->company_name) {
            $query->where('company_name', 'like', '%' . $request->company_name . '%');
        }

        if ($request->phone) {
            $query->where('phone', 'like', '%' . $request->phone . '%');
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }

        $clients = $query->latest()->get();

        foreach ($clients as $client) {
            $totalPayment = $client->payments
                ->where('status', 'approved')
                ->sum('amount');

            $totalDollarSpend = $client->dailyReports->sum('dollar_spend');
            $totalOrders = $client->dailyReports->sum('orders');

            $totalSpendBdt = $totalDollarSpend * $client->client_rate;
            $totalProfit = $totalDollarSpend * ($client->client_rate - $client->buy_rate);
            $balance = $totalPayment - $totalSpendBdt;

            $client->total_payment = $totalPayment;
            $client->total_dollar_spend = $totalDollarSpend;
            $client->total_orders = $totalOrders;
            $client->total_spend_bdt = $totalSpendBdt;
            $client->total_profit = $totalProfit;
            $client->balance = $balance;
        }

        return view('admin.clients.index', compact('clients'));
    }

    public function create()
    {
        $users = User::where('role', 'client')->get();

        return view('admin.clients.create', compact('users'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id|unique:clients,user_id',
            'company_name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:50',
            'client_rate' => 'required|numeric|min:0',
            'buy_rate' => 'required|numeric|min:0',
            'status' => 'required|in:active,pending,inactive',
        ]);

        Client::create($request->only([
            'user_id',
            'company_name',
            'phone',
            'client_rate',
            'buy_rate',
            'status',
        ]));

        return redirect('/admin/clients');
    }

    public function destroy(Client $client)
    {
        $client->delete();

        return redirect('/admin/clients');
    }
}