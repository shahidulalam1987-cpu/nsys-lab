@extends('layouts.admin')

@section('content')
    <h1>Card Transactions</h1>
    <p>Track actual Facebook spend, card fees, and BDT cost using the selected Binance purchase rate.</p>

    <div class="card">
        <h2>Add Card Transaction</h2>
        <form method="POST" action="/admin/facebook-financial/card-transactions" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:12px;">
            @csrf
            <label>Date<br><input type="date" name="transaction_date" value="{{ old('transaction_date', now()->toDateString()) }}" required></label>
            <label>Card<br>
                <select name="facebook_card_id" required>
                    <option value="">Select Card</option>
                    @foreach($cards as $card)
                        <option value="{{ $card->id }}">{{ $card->card_name }} | Balance USD {{ number_format((float) $card->current_balance, 2) }}</option>
                    @endforeach
                </select>
            </label>
            <label>Binance Cost Source<br>
                <select name="binance_purchase_id" required>
                    <option value="">Select Purchase Rate</option>
                    @foreach($purchases as $purchase)
                        <option value="{{ $purchase->id }}">{{ $purchase->purchase_date?->toDateString() }} @ BDT {{ number_format((float) $purchase->buy_rate, 4) }}</option>
                    @endforeach
                </select>
            </label>
            <label>Ad Account<br>
                <select name="ad_account_id">
                    <option value="">No Ad Account</option>
                    @foreach($adAccounts as $account)
                        <option value="{{ $account->id }}">{{ $account->ad_account_name }}</option>
                    @endforeach
                </select>
            </label>
            <label>Client<br>
                <select name="client_id">
                    <option value="">No Client</option>
                    @foreach($clients as $client)
                        <option value="{{ $client->id }}">{{ $client->company_name }} @ {{ number_format((float) $client->client_rate, 4) }}</option>
                    @endforeach
                </select>
            </label>
            <label>Page<br>
                <select name="client_page_id">
                    <option value="">No Page</option>
                    @foreach($pages as $page)
                        <option value="{{ $page->id }}">{{ $page->page_name }}</option>
                    @endforeach
                </select>
            </label>
            <label>Campaign<br>
                <select name="campaign_id">
                    <option value="">No Campaign</option>
                    @foreach($campaigns as $campaign)
                        <option value="{{ $campaign->id }}">{{ $campaign->campaign_name }}</option>
                    @endforeach
                </select>
            </label>
            <label>Spend USD<br><input type="number" step="0.01" min="0" name="spend_usd" required></label>
            <label>Fee USD<br><input type="number" step="0.01" min="0" name="fee_usd" value="0" required></label>
            <label style="grid-column:1/-1;">Notes<br><textarea name="notes" rows="2" style="width:100%;"></textarea></label>
            <button class="btn" type="submit">Save Transaction</button>
        </form>
    </div>

    <div class="card">
        <div class="table-wrap">
            <table>
                <tr>
                    <th>Date</th>
                    <th>Card</th>
                    <th>Ad Account</th>
                    <th>Client</th>
                    <th>Spend USD</th>
                    <th>Fee USD</th>
                    <th>Total Deducted</th>
                    <th>BDT Cost</th>
                    <th>Revenue</th>
                    <th>Profit</th>
                </tr>
                @forelse($transactions as $transaction)
                    <tr>
                        <td>{{ $transaction->transaction_date?->toDateString() }}</td>
                        <td>{{ $transaction->card?->card_name ?: '-' }}</td>
                        <td>{{ $transaction->adAccount?->ad_account_name ?: '-' }}</td>
                        <td>{{ $transaction->client?->company_name ?: '-' }}</td>
                        <td>USD {{ number_format((float) $transaction->spend_usd, 2) }}</td>
                        <td>USD {{ number_format((float) $transaction->fee_usd, 2) }}</td>
                        <td>USD {{ number_format((float) $transaction->total_deducted_usd, 2) }}</td>
                        <td>BDT {{ number_format((float) $transaction->bdt_cost, 2) }}</td>
                        <td>BDT {{ number_format((float) $transaction->client_revenue, 2) }}</td>
                        <td>BDT {{ number_format((float) $transaction->net_profit, 2) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="10">No card transactions found.</td></tr>
                @endforelse
            </table>
        </div>
    </div>
@endsection
