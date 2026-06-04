<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class InvoiceController extends Controller
{
    public function index()
    {
        $invoices = Invoice::with('client')
            ->latest()
            ->get();

        return view('admin.invoices.index', compact('invoices'));
    }

    public function create()
    {
        $clients = Client::where('status', 'active')->get();

        return view('admin.invoices.create', compact('clients'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'client_id' => 'required|exists:clients,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'amount' => 'required|numeric|min:1',
            'issue_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:issue_date',
            'status' => 'required|in:draft,sent,paid,overdue,cancelled',
        ]);

        $invoiceNumber = 'INV-' . date('Ymd') . '-' . str_pad(Invoice::count() + 1, 4, '0', STR_PAD_LEFT);

        Invoice::create([
            'client_id' => $request->client_id,
            'invoice_number' => $invoiceNumber,
            'title' => $request->title,
            'description' => $request->description,
            'amount' => $request->amount,
            'issue_date' => $request->issue_date,
            'due_date' => $request->due_date,
            'status' => $request->status,
        ]);

        return redirect('/admin/invoices')
            ->with('success', 'Invoice created successfully.');
    }
    public function downloadPdf($id)
 {
    $invoice = Invoice::with('client')->findOrFail($id);

    $pdf = Pdf::loadView('admin.pdf.invoice', compact('invoice'));

    return $pdf->download($invoice->invoice_number . '.pdf');
  }
 public function updateStatus($id, $status)
{
    if (!in_array($status, ['draft', 'sent', 'paid', 'overdue', 'cancelled'])) {
        abort(404);
    }

    $invoice = Invoice::findOrFail($id);

    $invoice->update([
        'status' => $status,
    ]);

    if ($status === 'paid') {
        $exists = \App\Models\Payment::where('transaction_id', $invoice->invoice_number)
            ->where('client_id', $invoice->client_id)
            ->exists();

        if (!$exists) {
            \App\Models\Payment::create([
                'client_id' => $invoice->client_id,
                'amount' => $invoice->amount,
                'payment_method' => 'Invoice',
                'transaction_id' => $invoice->invoice_number,
                'screenshot' => null,
                'note' => 'Auto payment generated from invoice.',
                'status' => 'approved',
                'reject_reason' => null,
            ]);
        }
    }

    return redirect('/admin/invoices')
        ->with('success', 'Invoice status updated successfully.');
   }
}