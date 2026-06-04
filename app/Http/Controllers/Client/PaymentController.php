<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Payment;
use App\Models\Invoice;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function index(Request $request)
    {
        $client = Client::where('user_id', auth()->id())->firstOrFail();

        $query = Payment::with('invoice')
            ->where('client_id', $client->id);

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

        return view('client.payments.index', compact('payments'));
    }

    public function create(Request $request)
    {
        $client = Client::where('user_id', auth()->id())->firstOrFail();

        $invoice = null;

        if ($request->invoice_id) {
            $invoice = Invoice::where('client_id', $client->id)
                ->where('id', $request->invoice_id)
                ->whereNotIn('status', ['paid', 'cancelled'])
                ->first();
        }

        return view('client.payments.create', compact('invoice'));
    }

    public function store(Request $request)
    {
        $client = Client::where('user_id', auth()->id())->firstOrFail();

        $request->validate([
            'invoice_id' => 'nullable|exists:invoices,id',
            'amount' => 'required|numeric|min:1',
            'payment_method' => 'required|string|max:100',
            'transaction_id' => 'required|string|max:255',
            'screenshot' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'note' => 'nullable|string',
        ]);

        $invoice = null;

        if ($request->invoice_id) {
            $invoice = Invoice::where('client_id', $client->id)
                ->where('id', $request->invoice_id)
                ->whereNotIn('status', ['paid', 'cancelled'])
                ->firstOrFail();
        }

        $screenshotPath = null;

        if ($request->hasFile('screenshot')) {
            $screenshotPath = $request->file('screenshot')->store('payment-screenshots', 'public');
        }

        Payment::create([
            'client_id' => $client->id,
            'invoice_id' => $invoice ? $invoice->id : null,
            'amount' => $request->amount,
            'payment_method' => $request->payment_method,
            'transaction_id' => $request->transaction_id,
            'screenshot' => $screenshotPath,
            'note' => $request->note,
            'status' => 'pending',
        ]);

        return redirect('/client/payments')
            ->with('success', 'Payment submitted successfully.');
    }
}