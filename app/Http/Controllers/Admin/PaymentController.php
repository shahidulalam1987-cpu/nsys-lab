<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Client;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function index(Request $request)
    {
        $query = Payment::with(['client', 'invoice']);

        if ($request->client_id) {
            $query->where('client_id', $request->client_id);
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->payment_method) {
            $query->where('payment_method', $request->payment_method);
        }

        if ($request->from_date) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }

        if ($request->to_date) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        $payments = $query->latest()->get();
        $clients = Client::orderBy('company_name')->get();

        return view('admin.payments.index', compact('payments', 'clients'));
    }

    public function pending()
    {
        $payments = Payment::with(['client', 'invoice'])
            ->where('status', 'pending')
            ->latest()
            ->get();

        return view('admin.payments.pending', compact('payments'));
    }

    public function approve($id)
    {
        $payment = Payment::with('invoice')->findOrFail($id);

        $payment->status = 'approved';
        $payment->reject_reason = null;
        $payment->approved_at = now();
        $payment->save();

        if ($payment->invoice) {
            $payment->invoice->update([
                'status' => 'paid',
            ]);
        }

        return redirect('/admin/payments/pending')
            ->with('success', 'Payment approved successfully.');
    }

    public function reject(Request $request, $id)
    {
        $request->validate([
            'reject_reason' => 'required|string|max:1000',
        ]);

        $payment = Payment::with('invoice')->findOrFail($id);

        $payment->status = 'rejected';
        $payment->reject_reason = $request->reject_reason;
        $payment->save();

        if ($payment->invoice) {
            $payment->invoice->update([
                'status' => 'sent',
            ]);
        }

        return redirect('/admin/payments/pending')
            ->with('success', 'Payment rejected successfully.');
    }
}