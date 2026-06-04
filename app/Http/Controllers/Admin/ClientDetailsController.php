<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Payment;
use App\Services\ClientLedgerService;

class ClientDetailsController extends Controller
{
    public function show(ClientLedgerService $ledgerService, $id)
    {
        $client = Client::findOrFail($id);
        $ledger = $ledgerService->build($client);
        $summary = $ledger['summary'];

        $reports = $ledger['reports']->sortByDesc('report_date');
        $payments = Payment::with('invoice')
            ->where('client_id', $client->id)
            ->latest()
            ->get();

        return view('admin.clients.show', compact(
            'client',
            'ledger',
            'summary',
            'reports',
            'payments'
        ));
    }
}
