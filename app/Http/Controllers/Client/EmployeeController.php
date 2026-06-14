<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\SalaryPayment;
use App\Services\SalaryFundService;
use Illuminate\Http\Request;

class EmployeeController extends Controller
{
    public function index()
    {
        $client = Client::where('user_id', auth()->id())->firstOrFail();
        $assignments = $client->employeeAssignments()
            ->with(['employee.payrolls' => function ($query) use ($client) {
                $query->where('client_id', $client->id)
                    ->current()
                    ->latest('salary_month');
            }])
            ->latest('assigned_from')
            ->get();

        return view('client.employees.index', compact('client', 'assignments'));
    }

    public function salaryFund(Request $request, SalaryFundService $salaryFundService)
    {
        $client = Client::where('user_id', auth()->id())->firstOrFail();
        $fund = $salaryFundService->build($client, $request->salary_month);

        return view('client.salary-fund.index', compact('client', 'fund'));
    }

    public function paymentHistory(Request $request)
    {
        $client = Client::where('user_id', auth()->id())->firstOrFail();
        $query = SalaryPayment::where('client_id', $client->id);

        if ($request->status) {
            $query->where('status', $request->status);
        }

        $payments = $query->latest()->get();

        return view('client.salary-payments.index', compact('payments'));
    }

    public function createPayment()
    {
        return view('client.salary-payments.create');
    }

    public function storePayment(Request $request)
    {
        $client = Client::where('user_id', auth()->id())->firstOrFail();

        $data = $request->validate([
            'salary_month' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:1'],
            'payment_method' => ['required', 'string', 'max:100'],
            'transaction_id' => ['required', 'string', 'max:255'],
            'screenshot' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'note' => ['nullable', 'string'],
        ]);

        if ($request->hasFile('screenshot')) {
            $data['screenshot'] = $request->file('screenshot')->store('salary-payment-screenshots', 'public');
        }

        $data['client_id'] = $client->id;
        $data['salary_month'] = date('Y-m-d', strtotime($data['salary_month']));
        $data['status'] = 'pending';

        SalaryPayment::create($data);

        return redirect('/client/salary-payments')->with('success', 'Client fund payment submitted successfully.');
    }
}
