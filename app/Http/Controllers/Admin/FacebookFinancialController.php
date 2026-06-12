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
use Illuminate\Http\Request;

class FacebookFinancialController extends Controller
{
    public function binancePurchases()
    {
        $purchases = BinancePurchase::latest('purchase_date')->latest()->get();
        $totalUsd = (float) $purchases->sum('usd_amount');
        $totalCost = (float) $purchases->sum('total_bdt_cost');

        return view('admin.facebook-financial.binance-purchases', [
            'purchases' => $purchases,
            'summary' => [
                'total_usd' => $totalUsd,
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

        $load = CardLoad::create($data);
        $load->card->increment('current_balance', (float) $load->usd_loaded);

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
            'notes' => ['nullable', 'string'],
        ]);

        $purchase = BinancePurchase::findOrFail($data['binance_purchase_id']);
        $client = ! empty($data['client_id']) ? Client::find($data['client_id']) : null;
        if (! $client && ! empty($data['campaign_id'])) {
            $client = Campaign::find($data['campaign_id'])?->client;
            $data['client_id'] = $client?->id;
        }

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
            'actual_bdt_cost' => (float) $transactions->sum('bdt_cost'),
            'net_profit' => (float) $transactions->sum('net_profit'),
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
