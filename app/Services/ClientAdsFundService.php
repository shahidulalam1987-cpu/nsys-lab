<?php

namespace App\Services;

use App\Models\Client;
use App\Models\ClientFundLedger;
use App\Models\DailyPerformanceReport;
use App\Models\Payment;

class ClientAdsFundService
{
    public function __construct(private ClientFundLedgerService $ledger) {}

    public function creditDeposit(Payment $payment): void
    {
        if ($payment->status !== 'approved' || ! $payment->client) {
            return;
        }

        $this->ledger->creditOnce($payment->client, ClientFundLedger::FUND_FACEBOOK_ADS, (float) $payment->amount, $payment, [
            'reference' => $payment->transaction_id ?: 'payment:' . $payment->id,
            'description' => 'Client Ads Fund Deposit - ' . ($payment->client?->company_name ?: 'Client') . '.',
            'created_by' => auth()->id(),
        ]);
    }

    public function syncPerformanceDebit(DailyPerformanceReport $report): void
    {
        $report->loadMissing('campaign.client', 'campaign.page');
        $client = $report->campaign?->client;

        if (! $client) {
            return;
        }

        $amount = round((float) $report->spend * (float) ($client->client_rate ?? 0), 2);
        $description = 'Facebook Ads Spend - '
            . ($report->campaign?->campaign_name ?: 'Campaign')
            . ' / ' . ($report->campaign?->page?->page_name ?: 'Page')
            . ' / ' . $report->report_date?->toDateString() . '.';

        $this->ledger->syncDebitForSource($client, ClientFundLedger::FUND_FACEBOOK_ADS, $amount, $report, [
            'reference' => 'performance-report:' . $report->id,
            'description' => $description,
            'adjustment_description' => 'Facebook Ads Spend Adjustment - performance report updated.',
            'created_by' => auth()->id(),
            'balance_error' => 'Insufficient Facebook ads fund balance.',
        ]);
    }

    public function summary(Client|int $client): array
    {
        return $this->ledger->totals($client, ClientFundLedger::FUND_FACEBOOK_ADS);
    }

    public function balance(Client|int $client): float
    {
        return $this->ledger->balance($client, ClientFundLedger::FUND_FACEBOOK_ADS);
    }
}
