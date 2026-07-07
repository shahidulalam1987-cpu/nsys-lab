<?php

namespace App\Services;

use App\Models\Client;
use App\Models\ClientFundLedger;

class ClientFundSummaryService
{
    public function __construct(private ClientFundLedgerService $ledger) {}

    public function forClient(Client|int $client): array
    {
        $salary = $this->ledger->totals($client, ClientFundLedger::FUND_EMPLOYEE_SALARY);
        $ads = $this->ledger->totals($client, ClientFundLedger::FUND_FACEBOOK_ADS);

        return [
            'salary' => $salary,
            'ads' => $ads,
            'combined_balance' => round($salary['balance'] + $ads['balance'], 2),
        ];
    }

    public function allClients()
    {
        return Client::orderBy('company_name')
            ->get()
            ->map(fn (Client $client) => [
                'client' => $client,
                'funds' => $this->forClient($client),
            ]);
    }

    public function dashboard(): array
    {
        $rows = $this->allClients();

        return [
            'rows' => $rows,
            'summary' => [
                'salary_received' => (float) $rows->sum('funds.salary.received'),
                'salary_used' => (float) $rows->sum('funds.salary.used'),
                'salary_balance' => (float) $rows->sum('funds.salary.balance'),
                'ads_received' => (float) $rows->sum('funds.ads.received'),
                'ads_spent' => (float) $rows->sum('funds.ads.used'),
                'ads_balance' => (float) $rows->sum('funds.ads.balance'),
                'combined_balance' => (float) $rows->sum('funds.combined_balance'),
            ],
        ];
    }
}
