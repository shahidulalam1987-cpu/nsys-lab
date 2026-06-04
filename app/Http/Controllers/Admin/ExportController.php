<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
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
            'Page Name',
            'Dollar Spend',
            'Orders',
            'Created At',
        ]);

        foreach ($reports as $report) {
            fputcsv($file, [
                $report->id,
                $report->client->company_name ?? 'N/A',
                $report->report_date,
                $report->page_name,
                $report->dollar_spend,
                $report->orders,
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
  public function clientStatementCsv($id)
{
    $client = \App\Models\Client::findOrFail($id);

    $payments = \App\Models\Payment::where('client_id', $client->id)
        ->latest()
        ->get();

    $reports = \App\Models\DailyReport::where('client_id', $client->id)
        ->latest()
        ->get();

    $fileName = 'client-statement-' . $client->id . '-' . date('Y-m-d') . '.csv';

    $headers = [
        'Content-Type' => 'text/csv',
        'Content-Disposition' => "attachment; filename=\"$fileName\"",
    ];

    $callback = function () use ($client, $payments, $reports) {
        $file = fopen('php://output', 'w');

        $approvedPayment = $payments->where('status', 'approved')->sum('amount');
        $totalDollarSpend = $reports->sum('dollar_spend');
        $totalSpendBdt = $totalDollarSpend * $client->client_rate;
        $balance = $approvedPayment - $totalSpendBdt;

        fputcsv($file, ['Client Statement']);
        fputcsv($file, ['Client', $client->company_name]);
        fputcsv($file, ['Phone', $client->phone]);
        fputcsv($file, ['Client Rate', $client->client_rate]);
        fputcsv($file, ['Buy Rate', $client->buy_rate]);
        fputcsv($file, ['Approved Payment', $approvedPayment]);
        fputcsv($file, ['Total Spend USD', $totalDollarSpend]);
        fputcsv($file, ['Total Spend BDT', $totalSpendBdt]);
        fputcsv($file, ['Balance', $balance]);

        fputcsv($file, []);
        fputcsv($file, ['Payments']);
        fputcsv($file, ['ID', 'Amount', 'Method', 'Transaction ID', 'Status', 'Reject Reason', 'Date']);

        foreach ($payments as $payment) {
            fputcsv($file, [
                $payment->id,
                $payment->amount,
                $payment->payment_method,
                $payment->transaction_id,
                $payment->status,
                $payment->reject_reason,
                $payment->created_at,
            ]);
        }

        fputcsv($file, []);
        fputcsv($file, ['Daily Reports']);
        fputcsv($file, ['ID', 'Date', 'Page', 'Dollar Spend', 'Orders']);

        foreach ($reports as $report) {
            fputcsv($file, [
                $report->id,
                $report->report_date,
                $report->page_name,
                $report->dollar_spend,
                $report->orders,
            ]);
        }

        fclose($file);
    };

    return response()->stream($callback, 200, $headers);
  }

  public function clientStatementPdf($id)
{
    $client = \App\Models\Client::findOrFail($id);

    $payments = \App\Models\Payment::where('client_id', $client->id)
        ->latest()
        ->get();

    $reports = \App\Models\DailyReport::where('client_id', $client->id)
        ->latest()
        ->get();

    $approvedPayment = $payments->where('status', 'approved')->sum('amount');

    $totalDollarSpend = $reports->sum('dollar_spend');

    $totalSpendBdt = $totalDollarSpend * $client->client_rate;

    $balance = $approvedPayment - $totalSpendBdt;

    $pdf = Pdf::loadView('admin.pdf.client-statement', [
        'client' => $client,
        'payments' => $payments,
        'reports' => $reports,
        'approvedPayment' => $approvedPayment,
        'totalDollarSpend' => $totalDollarSpend,
        'totalSpendBdt' => $totalSpendBdt,
        'balance' => $balance,
    ]);

    return $pdf->download(
        'Client-Statement-' . $client->company_name . '.pdf'
    );
  }
}
