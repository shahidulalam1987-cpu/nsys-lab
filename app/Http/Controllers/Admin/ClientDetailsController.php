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
        $employeeAssignments = $client->employeeAssignments()
            ->with('employee')
            ->latest('assigned_from')
            ->get();
        $assignedEmployees = $employeeAssignments
            ->pluck('employee')
            ->filter()
            ->unique('id');
        $employeeSummary = [
            'total' => $assignedEmployees->count(),
            'active' => $assignedEmployees->where('status', 'active')->count(),
            'probation' => $assignedEmployees->where('status', 'probation')->count(),
            'on_leave' => $assignedEmployees->where('status', 'on_leave')->count(),
            'inactive' => $assignedEmployees->where('status', 'inactive')->count(),
            'terminated' => $assignedEmployees->where('status', 'terminated')->count(),
        ];

        return view('admin.clients.show', compact(
            'client',
            'ledger',
            'summary',
            'reports',
            'payments',
            'employeeAssignments',
            'employeeSummary'
        ));
    }
}
