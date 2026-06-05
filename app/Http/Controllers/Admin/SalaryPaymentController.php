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

        return back()->with('success', 'Salary payment approved successfully.');
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

        return back()->with('success', 'Salary payment rejected successfully.');
    }
}
