<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\Client;
use App\Models\ClientPage;
use App\Services\ClientDailyStatementService;
use App\Services\ClientFundDashboardService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

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
            'fund_type' => ['nullable', 'in:employee_salary,facebook_ads'],
            'type' => ['nullable', 'string'],
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

    public function dailyStatement(Request $request, ClientDailyStatementService $statementService)
    {
        $data = $this->dailyStatementData($request);
        $statement = null;

        if ($request->filled(['client_id', 'campaign_id', 'current_total_spend_usd'])) {
            $statement = $statementService->preview($data);
        }

        return view('admin.client-fund.daily-statement', array_merge($this->dailyStatementSharedData(), [
            'filters' => $data,
            'statement' => $statement,
        ]));
    }

    public function saveDailyStatement(Request $request, ClientDailyStatementService $statementService)
    {
        $data = $this->dailyStatementData($request, true);
        $report = $statementService->save($data);

        return redirect('/admin/client-fund/daily-statement?client_id=' . $report->campaign?->client_id . '&campaign_id=' . $report->campaign_id . '&statement_date=' . $report->report_date?->toDateString() . '&current_total_spend_usd=' . $data['current_total_spend_usd'] . '&orders=' . $data['orders'])
            ->with('success', 'Daily statement saved and ads fund ledger updated.');
    }

    public function exportLedgerCsv(Request $request, Client $client, ClientFundDashboardService $clientFundDashboardService)
    {
        $filters = $request->validate([
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'fund_type' => ['nullable', 'in:employee_salary,facebook_ads'],
            'type' => ['nullable', 'string'],
        ]);
        $rows = $clientFundDashboardService->ledgerExportRows($client, $filters);

        return response()->streamDownload(function () use ($rows) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Date', 'Type', 'Reference', 'Description', 'Credit', 'Debit', 'Running Balance']);

            foreach ($rows as $row) {
                fputcsv($handle, [
                    $row['date'],
                    $row['type'],
                    $row['reference'],
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
            'fund_type' => ['nullable', 'in:employee_salary,facebook_ads'],
            'type' => ['nullable', 'string'],
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
            fputcsv($handle, ['Client', 'Salary Received', 'Salary Used', 'Salary Balance', 'Ads Received', 'Ads Spent', 'Ads Balance', 'Combined Balance', 'Pending Payments', 'Upcoming Salary', 'Unpaid Salary']);

            foreach ($clientFundDashboardService->exportRows() as $row) {
                fputcsv($handle, [
                    $row['client'],
                    number_format($row['fund_received'], 2, '.', ''),
                    number_format($row['salary_used'], 2, '.', ''),
                    number_format($row['balance'], 2, '.', ''),
                    number_format($row['ads_received'], 2, '.', ''),
                    number_format($row['ads_spent'], 2, '.', ''),
                    number_format($row['ads_balance'], 2, '.', ''),
                    number_format($row['combined_balance'], 2, '.', ''),
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

    private function dailyStatementData(Request $request, bool $required = false): array
    {
        $data = $request->validate([
            'client_id' => [$required ? 'required' : 'nullable', 'exists:clients,id'],
            'campaign_id' => [$required ? 'required' : 'nullable', 'exists:campaigns,id'],
            'statement_date' => [$required ? 'required' : 'nullable', 'date'],
            'previous_total_spend_usd' => ['nullable', 'numeric', 'min:0'],
            'current_total_spend_usd' => [$required ? 'required' : 'nullable', 'numeric', 'min:0'],
            'orders' => [$required ? 'required' : 'nullable', 'integer', 'min:0'],
            'rate_bdt' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
            'update_existing' => ['nullable', Rule::in(['1', 1, true, 'true'])],
        ]);

        if (($data['client_id'] ?? null) && ($data['campaign_id'] ?? null)) {
            $campaignBelongsToClient = Campaign::whereKey($data['campaign_id'])
                ->where('client_id', $data['client_id'])
                ->exists();

            if (! $campaignBelongsToClient) {
                throw ValidationException::withMessages([
                    'campaign_id' => 'Selected campaign does not belong to this client.',
                ]);
            }
        }

        return array_merge([
            'statement_date' => now()->toDateString(),
            'orders' => 0,
        ], $data);
    }

    private function dailyStatementSharedData(): array
    {
        $campaigns = Campaign::with(['client', 'page'])
            ->orderBy('campaign_name')
            ->get();

        return [
            'clients' => Client::orderBy('company_name')->get(),
            'pages' => ClientPage::orderBy('page_name')->get(),
            'campaigns' => $campaigns,
        ];
    }
}
