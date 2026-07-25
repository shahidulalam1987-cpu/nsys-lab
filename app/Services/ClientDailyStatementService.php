<?php

namespace App\Services;

use App\Models\Campaign;
use App\Models\Client;
use App\Models\ClientFundLedger;
use App\Models\DailyPerformanceReport;
use App\Models\MetaSpendSnapshot;
use App\Models\SalaryPayment;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

class ClientDailyStatementService
{
    public function __construct(
        private ClientFundLedgerService $ledger,
        private ClientAdsFundService $adsFund
    ) {}

    public function preview(array $data): array
    {
        $client = Client::findOrFail($data['client_id']);
        $campaign = Campaign::with(['client', 'page'])->findOrFail($data['campaign_id']);
        $date = Carbon::parse($data['statement_date'])->startOfDay();
        $rate = round((float) ($data['rate_bdt'] ?? $client->client_rate ?? 0), 2);
        $currentTotalSpend = round((float) $data['current_total_spend_usd'], 2);
        $previousSnapshot = $this->previousSnapshot($campaign, $date);
        $previousTotalSpend = array_key_exists('previous_total_spend_usd', $data) && $data['previous_total_spend_usd'] !== null && $data['previous_total_spend_usd'] !== ''
            ? round((float) $data['previous_total_spend_usd'], 2)
            : round((float) ($previousSnapshot?->spend_usd ?? $campaign->dailyPerformanceReports()->whereDate('report_date', '<', $date)->sum('spend')), 2);
        $todaySpendUsd = round($currentTotalSpend - $previousTotalSpend, 2);
        $todaySpendBdt = round($todaySpendUsd * $rate, 2);
        $creditToday = $this->creditToday($client, $date);
        $openingBalance = $this->openingAdsBalance($client, $date);
        $openingDue = max($openingBalance * -1, 0);
        $openingAdvance = max($openingBalance, 0);
        $balanceAfterCredit = round($openingBalance + $creditToday, 2);
        $remainingPreviousDue = max($balanceAfterCredit * -1, 0);
        $remainingPreviousAdvance = max($balanceAfterCredit, 0);
        $closingBalance = round($balanceAfterCredit - $todaySpendBdt, 2);
        $finalDue = max($closingBalance * -1, 0);
        $finalAdvance = max($closingBalance, 0);

        return [
            'client' => $client,
            'campaign' => $campaign,
            'date' => $date,
            'previous_snapshot' => $previousSnapshot,
            'previous_total_spend_usd' => $previousTotalSpend,
            'current_total_spend_usd' => $currentTotalSpend,
            'today_spend_usd' => $todaySpendUsd,
            'orders' => (int) ($data['orders'] ?? 0),
            'rate_bdt' => $rate,
            'today_spend_bdt' => $todaySpendBdt,
            'opening_balance' => $openingBalance,
            'opening_due' => $openingDue,
            'opening_advance' => $openingAdvance,
            'credit_today' => $creditToday,
            'remaining_previous_due' => $remainingPreviousDue,
            'remaining_previous_advance' => $remainingPreviousAdvance,
            'closing_balance' => $closingBalance,
            'final_due' => $finalDue,
            'final_advance' => $finalAdvance,
            'whatsapp_message' => $this->whatsAppMessage($client, $campaign, $date, [
                'previous_total_spend_usd' => $previousTotalSpend,
                'current_total_spend_usd' => $currentTotalSpend,
                'today_spend_usd' => $todaySpendUsd,
                'orders' => (int) ($data['orders'] ?? 0),
                'rate_bdt' => $rate,
                'today_spend_bdt' => $todaySpendBdt,
                'opening_due' => $openingDue,
                'opening_advance' => $openingAdvance,
                'credit_today' => $creditToday,
                'remaining_previous_due' => $remainingPreviousDue,
                'remaining_previous_advance' => $remainingPreviousAdvance,
                'final_due' => $finalDue,
                'final_advance' => $finalAdvance,
            ]),
        ];
    }

    public function save(array $data): DailyPerformanceReport
    {
        $preview = $this->preview($data);
        if ($preview['today_spend_usd'] < 0) {
            throw ValidationException::withMessages([
                'current_total_spend_usd' => 'Current total spend cannot be less than previous total spend.',
            ]);
        }

        $campaign = $preview['campaign'];
        $date = $preview['date'];

        return \DB::transaction(function () use ($preview, $campaign, $date, $data) {
            MetaSpendSnapshot::updateOrCreate([
                'campaign_id' => $campaign->id,
                'snapshot_date' => $date->toDateString(),
                'source' => 'daily_closing',
            ], [
                'ad_account_id' => $campaign->ad_account_id,
                'client_id' => $campaign->client_id,
                'client_page_id' => $campaign->client_page_id,
                'spend_usd' => $preview['current_total_spend_usd'],
                'orders' => $preview['orders'],
                'raw_payload' => [
                    'previous_total_spend_usd' => $preview['previous_total_spend_usd'],
                    'today_spend_usd' => $preview['today_spend_usd'],
                    'rate_bdt' => $preview['rate_bdt'],
                    'today_spend_bdt' => $preview['today_spend_bdt'],
                    'final_due' => $preview['final_due'],
                    'final_advance' => $preview['final_advance'],
                ],
            ]);

            $report = DailyPerformanceReport::where('campaign_id', $campaign->id)
                ->whereDate('report_date', $date)
                ->lockForUpdate()
                ->first();

            if ($report && empty($data['update_existing'])) {
                throw ValidationException::withMessages([
                    'campaign_id' => 'Daily performance already exists for this campaign/date. Tick Update Existing to replace it.',
                ]);
            }

            $report ??= new DailyPerformanceReport([
                'campaign_id' => $campaign->id,
                'report_date' => $date->toDateString(),
            ]);
            $report->fill([
                'spend' => $preview['today_spend_usd'],
                'orders' => $preview['orders'],
                'status' => 'admin_approved',
                'notes' => trim(collect([
                    $data['notes'] ?? null,
                    'Created from Client Daily WhatsApp Statement.',
                ])->filter()->implode("\n")),
            ])->save();

            $this->adsFund->syncPerformanceDebit($report);

            return $report;
        });
    }

    private function previousSnapshot(Campaign $campaign, Carbon $date): ?MetaSpendSnapshot
    {
        return MetaSpendSnapshot::where('campaign_id', $campaign->id)
            ->whereDate('snapshot_date', '<', $date)
            ->orderByDesc('snapshot_date')
            ->orderByDesc('id')
            ->first();
    }

    private function openingAdsBalance(Client $client, Carbon $date): float
    {
        return round((float) ClientFundLedger::where('client_id', $client->id)
            ->where('fund_type', ClientFundLedger::FUND_FACEBOOK_ADS)
            ->where('created_at', '<', $date)
            ->selectRaw("COALESCE(SUM(CASE WHEN direction = 'credit' THEN amount_bdt ELSE -amount_bdt END), 0) as balance")
            ->value('balance'), 2);
    }

    private function creditToday(Client $client, Carbon $date): float
    {
        return round((float) ClientFundLedger::where('client_id', $client->id)
            ->where('fund_type', ClientFundLedger::FUND_FACEBOOK_ADS)
            ->where('direction', ClientFundLedger::DIRECTION_CREDIT)
            ->where(function ($query) use ($date) {
                $query->whereDate('created_at', $date)
                    ->orWhere(function ($inner) use ($date) {
                        $inner->where('source_type', SalaryPayment::class)
                            ->whereIn('source_id', SalaryPayment::query()
                                ->whereDate('salary_month', $date)
                                ->select('id'));
                    });
            })
            ->sum('amount_bdt'), 2);
    }

    private function whatsAppMessage(Client $client, Campaign $campaign, Carbon $date, array $values): string
    {
        $fmt = fn (float $amount) => number_format($amount, 2, '.', '');

        return implode("\n", [
            'Client: ' . $client->company_name,
            'Campaign: ' . $campaign->campaign_name,
            '==' . $fmt($values['previous_total_spend_usd']) . '-' . $fmt($values['current_total_spend_usd']) . '=' . $fmt($values['today_spend_usd']) . '===',
            '',
            'Date: ' . $date->format('d F Y'),
            'Order: ' . number_format($values['orders']),
            'Ad Spend: $' . $fmt($values['today_spend_usd']),
            'Rate: ' . $fmt($values['rate_bdt']) . ' BDT/USD',
            "Today's Spend:",
            '$' . $fmt($values['today_spend_usd']) . ' x ' . $fmt($values['rate_bdt']) . ' = ' . $fmt($values['today_spend_bdt']) . ' BDT',
            '======================',
            'Previous Due:',
            'Date: ' . $date->copy()->subDay()->format('d F Y'),
            'Total Due: ' . $fmt($values['opening_due']) . ' BDT',
            'Previous Advance: ' . $fmt($values['opening_advance']) . ' BDT',
            'Credited:',
            $fmt($values['credit_today']) . ' BDT',
            'Remaining Previous Due:',
            $fmt($values['remaining_previous_due']) . ' BDT',
            'Remaining Previous Advance:',
            $fmt($values['remaining_previous_advance']) . ' BDT',
            '======================',
            "Today's Total Due:",
            "Today's Spend: " . $fmt($values['today_spend_bdt']) . ' BDT',
            'Previous Due: ' . $fmt($values['remaining_previous_due']) . ' + ' . $fmt($values['today_spend_bdt']) . ' = ' . $fmt($values['final_due']) . ' BDT',
            'Total Due: ' . $fmt($values['final_due']) . ' BDT',
            'Final Advance: ' . $fmt($values['final_advance']) . ' BDT',
            '======================',
            'Final Total Due: ' . $fmt($values['final_due']) . ' BDT',
        ]);
    }
}
