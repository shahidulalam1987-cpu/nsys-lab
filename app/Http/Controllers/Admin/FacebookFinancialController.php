<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdAccount;
use App\Models\BinancePurchase;
use App\Models\Campaign;
use App\Models\CardLoad;
use App\Models\CardTransaction;
use App\Models\Client;
use App\Models\ClientPage;
use App\Models\FacebookCard;
use App\Models\FundingBalance;
use App\Models\FundingBalanceHistory;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class FacebookFinancialController extends Controller
{
    public function fundingDashboard()
    {
        $balances = FundingBalance::with('updatedBy')->get()->keyBy('source');
        $balanceRows = collect(FundingBalance::SOURCES)->map(function (string $label, string $source) use ($balances) {
            $balance = $balances->get($source);

            return [
                'source' => $source,
                'label' => $label,
                'balance' => $balance,
                'current_balance' => (float) ($balance?->current_balance ?? 0),
                'last_updated' => $balance?->updated_at,
                'status' => $balance?->statusLabel() ?? 'Not Updated',
                'status_class' => $balance?->statusBadgeClass() ?? 'badge-neutral',
                'is_low' => $balance ? $balance->isLowBalance() : true,
                'limit' => (float) (FundingBalance::LOW_BALANCE_LIMITS[$source] ?? 100),
            ];
        });
        $history = FundingBalanceHistory::with('createdBy')->latest('balance_date')->latest()->take(20)->get();
        $monthTransactions = CardTransaction::whereMonth('transaction_date', now()->month)
            ->whereYear('transaction_date', now()->year)
            ->get();
        $cards = FacebookCard::all();
        $redotPayBalance = (float) $cards->filter(fn (FacebookCard $card) => strcasecmp((string) $card->provider, 'RedotPay') === 0)->sum('current_balance');
        $tavaoBalance = (float) $cards->filter(fn (FacebookCard $card) => strcasecmp((string) $card->provider, 'Tavao') === 0)->sum('current_balance');

        return view('admin.facebook-financial.funding-dashboard', [
            'balanceRows' => $balanceRows,
            'summary' => [
                'binance_balance' => $balanceRows->firstWhere('source', 'binance')['current_balance'] ?? 0,
                'redotpay_balance' => $balanceRows->firstWhere('source', 'redotpay')['current_balance'] ?? 0,
                'tavao_balance' => $balanceRows->firstWhere('source', 'tavao')['current_balance'] ?? 0,
                'total_available_usd' => (float) $balanceRows->sum('current_balance'),
                'low_binance' => $balanceRows->firstWhere('source', 'binance')['is_low'] ?? true,
                'low_redotpay' => $balanceRows->firstWhere('source', 'redotpay')['is_low'] ?? true,
                'low_tavao' => $balanceRows->firstWhere('source', 'tavao')['is_low'] ?? true,
                'monthly_facebook_spend' => (float) $monthTransactions->sum('spend_usd'),
                'monthly_card_fees' => (float) $monthTransactions->sum('fee_usd'),
                'monthly_extra_charges' => (float) $monthTransactions->sum('extra_charge_usd'),
                'monthly_total_deducted' => (float) $monthTransactions->sum('total_deducted_usd'),
                'monthly_revenue' => (float) $monthTransactions->sum('client_revenue'),
                'monthly_actual_cost' => (float) $monthTransactions->sum('bdt_cost'),
                'estimated_profit' => (float) $monthTransactions->sum('net_profit'),
                'redotpay_card_balance' => $redotPayBalance,
                'tavao_card_balance' => $tavaoBalance,
                'total_card_balance' => (float) $cards->sum('current_balance'),
            ],
            'history' => $history,
        ]);
    }

    public function createFundingBalance()
    {
        return view('admin.facebook-financial.funding-balance-form', [
            'sources' => FundingBalance::SOURCES,
        ]);
    }

    public function storeFundingBalance(Request $request)
    {
        $data = $request->validate([
            'source' => ['required', Rule::in(array_keys(FundingBalance::SOURCES))],
            'balance' => ['required', 'numeric', 'min:0'],
            'balance_date' => ['required', 'date'],
            'note' => ['nullable', 'string'],
        ]);

        $balance = FundingBalance::firstOrNew(['source' => $data['source']]);
        $previousBalance = (float) ($balance->current_balance ?? 0);
        $newBalance = round((float) $data['balance'], 2);

        $balance->fill([
            'current_balance' => $newBalance,
            'currency' => FundingBalance::CURRENCY,
            'balance_date' => $data['balance_date'],
            'notes' => $data['note'] ?? null,
            'updated_by' => $request->user()?->id,
        ]);
        $balance->save();

        FundingBalanceHistory::create([
            'funding_balance_id' => $balance->id,
            'source' => $data['source'],
            'previous_balance' => $previousBalance,
            'new_balance' => $newBalance,
            'difference' => round($newBalance - $previousBalance, 2),
            'currency' => FundingBalance::CURRENCY,
            'balance_date' => $data['balance_date'],
            'note' => $data['note'] ?? null,
            'created_by' => $request->user()?->id,
        ]);

        return redirect('/admin/facebook-financial/funding-dashboard')->with('success', 'Funding balance updated successfully.');
    }

    public function fundingHistoryShow(FundingBalanceHistory $history)
    {
        return view('admin.facebook-financial.funding-history-show', [
            'history' => $history->load('createdBy', 'fundingBalance'),
        ]);
    }

    public function binancePurchases()
    {
        $purchases = BinancePurchase::latest('purchase_date')->latest()->get();
        $totalUsd = (float) $purchases->sum('usd_amount');
        $remainingUsd = (float) $purchases->sum('remaining_usd');
        $totalCost = (float) $purchases->sum('total_bdt_cost');

        return view('admin.facebook-financial.binance-purchases', [
            'purchases' => $purchases,
            'summary' => [
                'total_usd' => $totalUsd,
                'remaining_usd' => $remainingUsd,
                'average_buy_rate' => $totalUsd > 0 ? $totalCost / $totalUsd : 0,
                'total_bdt_cost' => $totalCost,
            ],
        ]);
    }

    public function storeBinancePurchase(Request $request)
    {
        BinancePurchase::create($request->validate([
            'purchase_date' => ['required', 'date'],
            'usd_amount' => ['required', 'numeric', 'min:0.01'],
            'buy_rate' => ['required', 'numeric', 'min:0.01'],
            'source' => ['nullable', 'string', 'max:255'],
            'seller_name' => ['nullable', 'string', 'max:255'],
            'reference' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]));

        return redirect('/admin/facebook-financial/binance-purchases')->with('success', 'Binance purchase saved successfully.');
    }

    public function cardLoads()
    {
        return view('admin.facebook-financial.card-loads', [
            'loads' => CardLoad::with(['card', 'binancePurchase'])->latest('load_date')->latest()->get(),
            'cards' => FacebookCard::orderBy('card_name')->get(),
            'purchases' => BinancePurchase::latest('purchase_date')->get(),
        ]);
    }

    public function storeCardLoad(Request $request)
    {
        $data = $request->validate([
            'load_date' => ['required', 'date'],
            'facebook_card_id' => ['required', 'exists:facebook_cards,id'],
            'binance_purchase_id' => ['required', 'exists:binance_purchases,id'],
            'usd_loaded' => ['required', 'numeric', 'min:0.01'],
            'notes' => ['nullable', 'string'],
        ]);

        $purchase = BinancePurchase::findOrFail($data['binance_purchase_id']);
        if ((float) $data['usd_loaded'] > (float) $purchase->remaining_usd) {
            return back()
                ->withInput()
                ->withErrors(['usd_loaded' => 'USD loaded cannot exceed selected Binance available USD.']);
        }

        $load = CardLoad::create($data);
        $load->card->increment('current_balance', (float) $load->usd_loaded);
        $purchase->decrement('remaining_usd', (float) $load->usd_loaded);

        return redirect('/admin/facebook-financial/card-loads')->with('success', 'Card load saved and card balance updated.');
    }

    public function cardTransactions()
    {
        return view('admin.facebook-financial.card-transactions', $this->transactionViewData([
            'transactions' => CardTransaction::with(['card', 'binancePurchase', 'adAccount', 'client', 'page', 'campaign'])
                ->latest('transaction_date')
                ->latest()
                ->get(),
        ]));
    }

    public function storeCardTransaction(Request $request)
    {
        $data = $request->validate([
            'transaction_date' => ['required', 'date'],
            'facebook_card_id' => ['required', 'exists:facebook_cards,id'],
            'binance_purchase_id' => ['required', 'exists:binance_purchases,id'],
            'ad_account_id' => ['nullable', 'exists:ad_accounts,id'],
            'client_id' => ['nullable', 'exists:clients,id'],
            'client_page_id' => ['nullable', 'exists:client_pages,id'],
            'campaign_id' => ['nullable', 'exists:campaigns,id'],
            'spend_usd' => ['required', 'numeric', 'min:0'],
            'fee_usd' => ['required', 'numeric', 'min:0'],
            'extra_charge_usd' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
        ]);

        $purchase = BinancePurchase::findOrFail($data['binance_purchase_id']);
        $client = ! empty($data['client_id']) ? Client::find($data['client_id']) : null;
        if (! $client && ! empty($data['campaign_id'])) {
            $client = Campaign::find($data['campaign_id'])?->client;
            $data['client_id'] = $client?->id;
        }
        $data['extra_charge_usd'] = (float) ($data['extra_charge_usd'] ?? 0);

        $transaction = CardTransaction::create($data + [
            'buy_rate' => (float) $purchase->buy_rate,
            'client_rate' => (float) ($client?->client_rate ?? 0),
        ]);
        $transaction->card->decrement('current_balance', (float) $transaction->total_deducted_usd);

        return redirect('/admin/facebook-financial/card-transactions')->with('success', 'Card transaction saved and card balance updated.');
    }

    public function profitDashboard(Request $request)
    {
        $month = $request->input('month', now()->format('Y-m'));
        $transactions = CardTransaction::with(['card', 'client', 'adAccount', 'page', 'campaign'])
            ->whereMonth('transaction_date', substr($month, 5, 2))
            ->whereYear('transaction_date', substr($month, 0, 4))
            ->get();

        return view('admin.facebook-financial.profit-dashboard', [
            'month' => $month,
            'summary' => $this->summary($transactions),
            'cardRows' => $this->groupSummary($transactions, 'card', 'card_name'),
            'clientRows' => $this->groupSummary($transactions, 'client', 'company_name'),
            'adAccountRows' => $this->groupSummary($transactions, 'adAccount', 'ad_account_name'),
            'pageRows' => $this->groupSummary($transactions, 'page', 'page_name'),
            'campaignRows' => $this->groupSummary($transactions, 'campaign', 'campaign_name'),
        ]);
    }

    private function transactionViewData(array $extra = []): array
    {
        return $extra + [
            'cards' => FacebookCard::orderBy('card_name')->get(),
            'purchases' => BinancePurchase::latest('purchase_date')->get(),
            'adAccounts' => AdAccount::orderBy('ad_account_name')->get(),
            'clients' => Client::orderBy('company_name')->get(),
            'pages' => ClientPage::orderBy('page_name')->get(),
            'campaigns' => Campaign::orderBy('campaign_name')->get(),
        ];
    }

    private function summary($transactions): array
    {
        return [
            'client_revenue' => (float) $transactions->sum('client_revenue'),
            'facebook_spend' => (float) $transactions->sum('spend_usd'),
            'card_fees' => (float) $transactions->sum('fee_usd'),
            'extra_charges' => (float) $transactions->sum('extra_charge_usd'),
            'total_deducted' => (float) $transactions->sum('total_deducted_usd'),
            'actual_bdt_cost' => (float) $transactions->sum('bdt_cost'),
            'net_profit' => (float) $transactions->sum('net_profit'),
            'average_buy_rate' => (float) $transactions->sum('total_deducted_usd') > 0
                ? (float) $transactions->sum('bdt_cost') / (float) $transactions->sum('total_deducted_usd')
                : 0,
            'average_profit_per_usd' => (float) $transactions->sum('spend_usd') > 0
                ? (float) $transactions->sum('net_profit') / (float) $transactions->sum('spend_usd')
                : 0,
        ];
    }

    private function groupSummary($transactions, string $relation, string $labelField)
    {
        return $transactions
            ->groupBy(fn (CardTransaction $transaction) => $transaction->{$relation}?->id ?: 0)
            ->map(function ($rows) use ($relation, $labelField) {
                $first = $rows->first();
                $model = $first->{$relation};

                return $this->summary($rows) + [
                    'name' => $model?->{$labelField} ?: '-',
                    'total_deducted' => (float) $rows->sum('total_deducted_usd'),
                    'balance' => $relation === 'card' ? (float) ($model?->current_balance ?? 0) : null,
                ];
            })
            ->values();
    }
}
