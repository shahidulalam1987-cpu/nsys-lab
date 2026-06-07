<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Services\ClientFundDashboardService;
use Illuminate\Http\Request;

class ClientFundController extends Controller
{
    public function dashboard(ClientFundDashboardService $clientFundDashboardService)
    {
        $dashboard = $clientFundDashboardService->dashboard();

        return view('admin.client-fund.dashboard', [
            'rows' => $dashboard['rows'],
            'summary' => $dashboard['summary'],
            'clientFundDashboardService' => $clientFundDashboardService,
        ]);
    }

    public function show(Request $request, Client $client, ClientFundDashboardService $clientFundDashboardService)
    {
        $filters = $request->validate([
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'type' => ['nullable', 'in:Client Fund Received,Employee Salary Paid'],
        ]);
        $details = $clientFundDashboardService->clientDetails($client, $filters);

        return view('admin.client-fund.show', [
            'client' => $client,
            'row' => $details['row'],
            'ledger' => $details['ledger'],
            'filters' => $filters,
            'clientFundDashboardService' => $clientFundDashboardService,
        ]);
    }

    public function exportLedgerCsv(Request $request, Client $client, ClientFundDashboardService $clientFundDashboardService)
    {
        $filters = $request->validate([
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'type' => ['nullable', 'in:Client Fund Received,Employee Salary Paid'],
        ]);
        $rows = $clientFundDashboardService->ledgerExportRows($client, $filters);

        return response()->streamDownload(function () use ($rows) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Date', 'Type', 'Description', 'Credit', 'Debit', 'Running Balance']);

            foreach ($rows as $row) {
                fputcsv($handle, [
                    $row['date'],
                    $row['type'],
                    $row['description'],
                    number_format($row['credit'], 2, '.', ''),
                    number_format($row['debit'], 2, '.', ''),
                    number_format($row['running_balance'], 2, '.', ''),
                ]);
            }

            fclose($handle);
        }, 'client-fund-ledger-' . $client->id . '.csv', ['Content-Type' => 'text/csv']);
    }

    public function exportLedgerExcel(Request $request, Client $client, ClientFundDashboardService $clientFundDashboardService)
    {
        $filters = $request->validate([
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'type' => ['nullable', 'in:Client Fund Received,Employee Salary Paid'],
        ]);

        return response()->view('admin.client-fund.ledger-excel', [
            'client' => $client,
            'rows' => $clientFundDashboardService->ledgerExportRows($client, $filters),
        ], 200, [
            'Content-Type' => 'application/vnd.ms-excel',
            'Content-Disposition' => 'attachment; filename="client-fund-ledger-' . $client->id . '.xls"',
        ]);
    }

    public function exportCsv(ClientFundDashboardService $clientFundDashboardService)
    {
        return response()->streamDownload(function () use ($clientFundDashboardService) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Client', 'Fund Received', 'Salary Used', 'Balance', 'Pending Payments', 'Upcoming Salary', 'Unpaid Salary']);

            foreach ($clientFundDashboardService->exportRows() as $row) {
                fputcsv($handle, [
                    $row['client'],
                    number_format($row['fund_received'], 2, '.', ''),
                    number_format($row['salary_used'], 2, '.', ''),
                    number_format($row['balance'], 2, '.', ''),
                    number_format($row['pending_payments'], 2, '.', ''),
                    number_format($row['upcoming_salary'], 2, '.', ''),
                    number_format($row['unpaid_salary'], 2, '.', ''),
                ]);
            }

            fclose($handle);
        }, 'client-fund-dashboard.csv', ['Content-Type' => 'text/csv']);
    }

    public function exportExcel(ClientFundDashboardService $clientFundDashboardService)
    {
        return response()->view('admin.client-fund.export-excel', [
            'rows' => $clientFundDashboardService->exportRows(),
        ], 200, [
            'Content-Type' => 'application/vnd.ms-excel',
            'Content-Disposition' => 'attachment; filename="client-fund-dashboard.xls"',
        ]);
    }
}
