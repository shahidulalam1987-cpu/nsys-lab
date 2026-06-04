<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\User;
use App\Services\ClientLedgerService;
use Illuminate\Http\Request;

class ClientController extends Controller
{
    public function index(Request $request, ClientLedgerService $ledgerService)
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
            $summary = $ledgerService->build($client)['summary'];

            $client->total_payment = $summary['total_credit'];
            $client->total_dollar_spend = $summary['total_spend_usd'];
            $client->total_orders = $summary['total_orders'];
            $client->total_spend_bdt = $summary['total_debit'];
            $client->total_profit = $summary['profit'];
            $client->current_due = $summary['current_due'];
            $client->available_balance = $summary['available_balance'];
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
