<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Payment;
use App\Services\ClientLedgerService;
use Barryvdh\DomPDF\Facade\Pdf;

class ExportController extends Controller
{
    public function paymentsCsv()
    {
        $fileName = 'payments-export-' . date('Y-m-d') . '.csv';

        $payments = Payment::with('client')
            ->latest()
            ->get();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$fileName\"",
        ];

        $callback = function () use ($payments) {
            $file = fopen('php://output', 'w');

            fputcsv($file, [
                'ID',
                'Client',
                'Amount',
                'Payment Method',
                'Transaction ID',
                'Status',
                'Reject Reason',
                'Date',
            ]);

            foreach ($payments as $payment) {
                fputcsv($file, [
                    $payment->id,
                    $payment->client->company_name ?? 'N/A',
                    $payment->amount,
                    $payment->payment_method,
                    $payment->transaction_id,
                    $payment->status,
                    $payment->reject_reason,
                    $payment->created_at,
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
    public function dailyReportsCsv()
{
    $fileName = 'daily-reports-export-' . date('Y-m-d') . '.csv';

    $reports = \App\Models\DailyPerformanceReport::with(['campaign.client', 'campaign.page'])
        ->latest('report_date')
        ->get();

    $headers = [
        'Content-Type' => 'text/csv',
        'Content-Disposition' => "attachment; filename=\"$fileName\"",
    ];

    $callback = function () use ($reports) {
        $file = fopen('php://output', 'w');

        fputcsv($file, [
            'ID',
            'Date',
            'Campaign Name',
            'Campaign ID',
            'Client',
            'Page',
            'Spend USD',
            'Messages',
            'Results',
            'Leads',
            'Orders',
            'Reach',
            'Impressions',
            'Clicks',
            'CPM',
            'CPR',
            'CPL',
            'CPP',
            'CPC',
            'Created At',
        ]);

        foreach ($reports as $report) {
            fputcsv($file, [
                $report->id,
                $report->report_date?->toDateString(),
                $report->campaign?->campaign_name,
                $report->campaign?->campaign_id,
                $report->campaign?->client?->company_name,
                $report->campaign?->page?->page_name,
                $report->spend,
                $report->messages,
                $report->results,
                $report->leads,
                $report->orders,
                $report->reach,
                $report->impressions,
                $report->clicks,
                $report->cpm,
                $report->cpr,
                $report->cpl,
                $report->cpp,
                $report->cpc,
                $report->created_at,
            ]);
        }

        fclose($file);
    };

    return response()->stream($callback, 200, $headers);
  }
  public function profitHistoryCsv()
{
    $fileName = 'profit-history-export-' . date('Y-m-d') . '.csv';

    $reports = \App\Models\DailyReport::with('client')
        ->latest()
        ->get();

    $headers = [
        'Content-Type' => 'text/csv',
        'Content-Disposition' => "attachment; filename=\"$fileName\"",
    ];

    $callback = function () use ($reports) {
        $file = fopen('php://output', 'w');

        fputcsv($file, [
            'ID',
            'Client',
            'Report Date',
            'Dollar Spend',
            'Client Rate',
            'Buy Rate',
            'Revenue',
            'Cost',
            'Profit',
        ]);

        foreach ($reports as $report) {
            $clientRate = $report->client->client_rate ?? 0;
            $buyRate = $report->client->buy_rate ?? 0;

            $revenue = $report->dollar_spend * $clientRate;
            $cost = $report->dollar_spend * $buyRate;
            $profit = $revenue - $cost;

            fputcsv($file, [
                $report->id,
                $report->client->company_name ?? 'N/A',
                $report->report_date,
                $report->dollar_spend,
                $clientRate,
                $buyRate,
                $revenue,
                $cost,
                $profit,
            ]);
        }

        fclose($file);
    };

    return response()->stream($callback, 200, $headers);
  }
  public function clientStatementCsv(ClientLedgerService $ledgerService, $id)
{
    $client = Client::findOrFail($id);
    $ledger = $ledgerService->build($client);
    $summary = $ledger['summary'];

    $fileName = 'client-statement-' . $client->id . '-' . date('Y-m-d') . '.csv';

    $headers = [
        'Content-Type' => 'text/csv',
        'Content-Disposition' => "attachment; filename=\"$fileName\"",
    ];

    $callback = function () use ($client, $ledger, $summary) {
        $file = fopen('php://output', 'w');

        fputcsv($file, ['Client Statement']);
        fputcsv($file, ['Client', $client->company_name]);
        fputcsv($file, ['Phone', $client->phone]);
        fputcsv($file, ['Client Rate', $summary['client_rate']]);
        fputcsv($file, ['Buy Rate', $summary['buy_rate']]);
        fputcsv($file, ['Total Debit', $summary['total_debit']]);
        fputcsv($file, ['Total Credit', $summary['total_credit']]);
        fputcsv($file, ['Current Due', $summary['current_due']]);
        fputcsv($file, ['Available Balance', $summary['available_balance']]);
        fputcsv($file, ['Total Spend USD', $summary['total_spend_usd']]);
        fputcsv($file, ['Total Orders', $summary['total_orders']]);
        fputcsv($file, ['Total Revenue', $summary['total_revenue']]);
        fputcsv($file, ['Total Cost', $summary['total_cost']]);
        fputcsv($file, ['Profit', $summary['profit']]);

        fputcsv($file, []);
        fputcsv($file, ['Ledger']);
        fputcsv($file, ['Date', 'Transaction Type', 'Page', 'Invoice', 'Spend USD', 'Orders', 'Debit BDT', 'Credit BDT', 'Running Due Balance BDT']);

        foreach ($ledger['rows'] as $row) {
            fputcsv($file, [
                $row['date'],
                $row['transaction_type'],
                $row['page'],
                $row['invoice_number'],
                $row['spend_usd'],
                $row['orders'],
                $row['debit'],
                $row['credit'],
                $row['running_balance'],
            ]);
        }

        fclose($file);
    };

    return response()->stream($callback, 200, $headers);
  }

  public function clientStatementPdf(ClientLedgerService $ledgerService, $id)
{
    $client = Client::findOrFail($id);
    $ledger = $ledgerService->build($client);

    $pdf = Pdf::loadView('admin.pdf.client-statement', [
        'client' => $client,
        'ledger' => $ledger,
        'summary' => $ledger['summary'],
    ]);

    return $pdf->download(
        'Client-Statement-' . $client->company_name . '.pdf'
    );
  }
}
