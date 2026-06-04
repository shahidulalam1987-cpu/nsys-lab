<?php

namespace App\Services;

use App\Models\Client;
use App\Models\DailyReport;
use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ClientLedgerService
{
    public function build(Client $client, array $filters = []): array
    {
        $fromDate = $filters['from_date'] ?? null;
        $toDate = $filters['to_date'] ?? null;
        $clientRate = max((float) ($client->client_rate ?? 0), 0);
        $buyRate = max((float) ($client->buy_rate ?? 0), 0);

        $reports = DailyReport::where('client_id', $client->id)
            ->when($fromDate, fn ($query, $date) => $query->whereDate('report_date', '>=', $date))
            ->when($toDate, fn ($query, $date) => $query->whereDate('report_date', '<=', $date))
            ->orderBy('report_date')
            ->orderBy('id')
            ->get();

        $payments = Payment::with('invoice')
            ->where('client_id', $client->id)
            ->where('status', 'approved')
            ->get()
            ->filter(function (Payment $payment) use ($fromDate, $toDate) {
                $ledgerDate = $this->paymentLedgerDate($payment)->toDateString();

                if ($fromDate && $ledgerDate < $fromDate) {
                    return false;
                }

                if ($toDate && $ledgerDate > $toDate) {
                    return false;
                }

                return true;
            })
            ->sortBy(fn (Payment $payment) => $this->paymentLedgerDate($payment)->format('Y-m-d H:i:s') . '-' . $payment->id)
            ->values();

        $rows = $this->buildRows($reports, $payments, $clientRate);
        $runningBalance = 0;

        $rows = $rows->map(function (array $row) use (&$runningBalance) {
            $runningBalance += $row['debit'] - $row['credit'];
            $row['running_balance'] = $runningBalance;

            return $row;
        });

        $totalSpendUsd = (float) $reports->sum('dollar_spend');
        $totalOrders = (int) $reports->sum('orders');
        $totalDebit = (float) $rows->sum('debit');
        $totalCredit = (float) $rows->sum('credit');
        $netBalance = $totalDebit - $totalCredit;
        $totalCost = $totalSpendUsd * $buyRate;
        $pendingPayment = (float) Payment::where('client_id', $client->id)
            ->where('status', 'pending')
            ->sum('amount');

        return [
            'rows' => $rows,
            'reports' => $reports,
            'payments' => $payments,
            'summary' => [
                'client_rate' => $clientRate,
                'buy_rate' => $buyRate,
                'total_debit' => $totalDebit,
                'total_credit' => $totalCredit,
                'net_balance' => $netBalance,
                'current_due' => max($netBalance, 0),
                'available_balance' => max($netBalance * -1, 0),
                'total_spend_usd' => $totalSpendUsd,
                'total_orders' => $totalOrders,
                'total_revenue' => $totalDebit,
                'total_cost' => $totalCost,
                'profit' => $totalDebit - $totalCost,
                'pending_payment' => $pendingPayment,
            ],
        ];
    }

    private function buildRows(Collection $reports, Collection $payments, float $clientRate): Collection
    {
        $reportRows = $reports->map(function (DailyReport $report) use ($clientRate) {
            $date = Carbon::parse($report->report_date);
            $spendUsd = (float) $report->dollar_spend;

            return [
                'date' => $date->toDateString(),
                'sort_date' => $date->format('Y-m-d 00:00:00'),
                'transaction_type' => 'Ad Spend',
                'page' => $report->page_name,
                'invoice_number' => null,
                'orders' => (int) $report->orders,
                'spend_usd' => $spendUsd,
                'debit' => $spendUsd * $clientRate,
                'credit' => 0,
            ];
        });

        $paymentRows = $payments->map(function (Payment $payment) {
            $date = $this->paymentLedgerDate($payment);

            return [
                'date' => $date->toDateString(),
                'sort_date' => $date->format('Y-m-d H:i:s'),
                'transaction_type' => $payment->invoice_id ? 'Invoice Payment' : 'Payment',
                'page' => '-',
                'invoice_number' => $payment->invoice?->invoice_number,
                'orders' => null,
                'spend_usd' => null,
                'debit' => 0,
                'credit' => (float) $payment->amount,
            ];
        });

        return $reportRows
            ->merge($paymentRows)
            ->sortBy('sort_date')
            ->values();
    }

    private function paymentLedgerDate(Payment $payment): Carbon
    {
        return Carbon::parse($payment->approved_at ?: $payment->created_at);
    }
}
