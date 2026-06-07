<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Services\ClientFundDashboardService;

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

    public function show(Client $client, ClientFundDashboardService $clientFundDashboardService)
    {
        $details = $clientFundDashboardService->clientDetails($client);

        return view('admin.client-fund.show', [
            'client' => $client,
            'row' => $details['row'],
            'ledger' => $details['ledger'],
            'clientFundDashboardService' => $clientFundDashboardService,
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
