<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Invoice;
use Illuminate\Support\Facades\Auth;
use Barryvdh\DomPDF\Facade\Pdf;

class InvoiceController extends Controller
{
    public function index()
    {
        $client = Client::where('user_id', Auth::id())->first();

        if (!$client) {
            abort(403);
        }

        $invoices = Invoice::where('client_id', $client->id)
            ->latest()
            ->get();

        return view('client.invoices.index', compact(
            'client',
            'invoices'
        ));
    }

    public function downloadPdf($id)
    {
    $client = Client::where('user_id', auth()->id())->firstOrFail();

    $invoice = Invoice::with('client')
        ->where('client_id', $client->id)
        ->where('id', $id)
        ->firstOrFail();

    $pdf = Pdf::loadView('admin.pdf.invoice', compact('invoice'));

    return $pdf->download($invoice->invoice_number . '.pdf');
    }
}