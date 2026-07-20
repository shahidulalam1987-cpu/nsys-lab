<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\User;
use App\Services\ClientFundDashboardService;
use Illuminate\Http\Request;

class ClientController extends Controller
{
    public function index(Request $request, ClientFundDashboardService $clientFundDashboardService)
    {
        $query = Client::query();

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
        $fundRows = $clientFundDashboardService->dashboard()['rows']
            ->keyBy(fn (array $row) => $row['client']->id);

        return view('admin.clients.index', compact('clients', 'fundRows'));
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
        $hasHistory = $client->payments()->exists()
            || $client->dailyReports()->exists()
            || $client->messages()->exists()
            || $client->employeeAssignments()->exists()
            || $client->pages()->exists()
            || $client->adAccounts()->exists()
            || $client->campaigns()->exists()
            || $client->salaryDays()->exists()
            || $client->salaryPayments()->exists()
            || $client->employeePayrolls()->exists();

        if ($hasHistory) {
            return redirect('/admin/clients')
                ->withErrors(['client' => 'This client has operational or financial history and cannot be deleted. Set the client inactive instead.']);
        }

        $client->delete();

        return redirect('/admin/clients');
    }
}
