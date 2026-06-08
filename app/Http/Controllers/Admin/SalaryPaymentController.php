<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\SalaryPayment;
use Illuminate\Http\Request;

class SalaryPaymentController extends Controller
{
    public function index(Request $request)
    {
        $query = SalaryPayment::with('client');

        if ($request->client_id) {
            $query->where('client_id', $request->client_id);
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }

        $payments = $query->latest()->get();
        $clients = Client::orderBy('company_name')->get();

        return view('admin.salary-payments.index', compact('payments', 'clients'));
    }

    public function create()
    {
        $clients = Client::orderBy('company_name')->get();

        return view('admin.salary-payments.create', compact('clients'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'client_id' => ['required', 'exists:clients,id'],
            'amount' => ['required', 'numeric', 'min:1'],
            'payment_method' => ['required', 'string', 'max:100'],
            'transaction_id' => ['required', 'string', 'max:255'],
            'payment_date' => ['required', 'date'],
            'screenshot' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'note' => ['nullable', 'string'],
            'status' => ['required', 'in:pending,approved'],
        ]);

        if ($request->hasFile('screenshot')) {
            $data['screenshot'] = $request->file('screenshot')->store('salary-payment-screenshots', 'public');
        }

        $data['salary_month'] = date('Y-m-d', strtotime($data['payment_date']));
        unset($data['payment_date']);

        if ($data['status'] === 'approved') {
            $data['approved_at'] = now();
        }

        SalaryPayment::create($data);

        return redirect('/admin/salary-payments')->with('success', 'Client fund payment saved successfully.');
    }

    public function pending()
    {
        $payments = SalaryPayment::with('client')
            ->where('status', 'pending')
            ->latest()
            ->get();

        return view('admin.salary-payments.pending', compact('payments'));
    }

    public function approve($id)
    {
        $payment = SalaryPayment::findOrFail($id);

        $payment->update([
            'status' => 'approved',
            'approved_at' => now(),
            'rejected_at' => null,
            'reject_reason' => null,
        ]);

        return back()->with('success', 'Client payment approved successfully.');
    }

    public function reject(Request $request, $id)
    {
        $request->validate([
            'reject_reason' => ['required', 'string', 'max:1000'],
        ]);

        $payment = SalaryPayment::findOrFail($id);

        $payment->update([
            'status' => 'rejected',
            'rejected_at' => now(),
            'reject_reason' => $request->reject_reason,
        ]);

        return back()->with('success', 'Client payment rejected successfully.');
    }

    public function destroy(SalaryPayment $payment)
    {
        $payment->delete();

        return redirect('/admin/salary-payments')->with('success', 'Client payment record deleted successfully.');
    }
}
