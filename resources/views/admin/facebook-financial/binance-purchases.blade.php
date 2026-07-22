@extends('layouts.admin')

@section('content')
    <h1>Binance Purchases</h1>
    <p>Track every USD purchase with its actual BDT buy rate. No fixed buy rate is used.</p>

    @include('admin.facebook-cards.partials.tabs')

    <div class="stats-grid">
        <div class="stat-card"><p>Total USD Purchased</p><h2>USD {{ number_format($summary['total_usd'], 2) }}</h2></div>
        <div class="stat-card"><p>Available Binance USD</p><h2>USD {{ number_format($summary['remaining_usd'], 2) }}</h2></div>
        <div class="stat-card"><p>Average Buy Rate</p><h2>BDT {{ number_format($summary['average_buy_rate'], 4) }}</h2></div>
        <div class="stat-card"><p>Total BDT Cost</p><h2>BDT {{ number_format($summary['total_bdt_cost'], 2) }}</h2></div>
    </div>

    <div class="card">
        <h2>Add Binance Purchase</h2>
        <form method="POST" action="/admin/facebook-financial/binance-purchases" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(210px,1fr));gap:12px;">
            @csrf
            <label>Purchase Date<br><input type="date" name="purchase_date" value="{{ old('purchase_date', now()->toDateString()) }}" required></label>
            <label>Pay From BDT Account<br>
                <select name="finance_account_id" required>
                    <option value="">Select Account</option>
                    @foreach($financeAccounts as $account)
                        <option value="{{ $account->id }}">{{ $account->account_name }} - BDT {{ number_format((float) $account->current_balance, 2) }}</option>
                    @endforeach
                </select>
            </label>
            <label>USD Amount<br><input type="number" step="0.01" min="0.01" name="usd_amount" value="{{ old('usd_amount') }}" required></label>
            <label>Buy Rate (BDT)<br><input type="number" step="0.0001" min="0.01" name="buy_rate" value="{{ old('buy_rate') }}" required></label>
            <label>Source<br><input type="text" name="source" value="{{ old('source') }}"></label>
            <label>Seller Name<br><input type="text" name="seller_name" value="{{ old('seller_name') }}"></label>
            <label>Reference<br><input type="text" name="reference" value="{{ old('reference') }}"></label>
            <label style="grid-column:1/-1;">Notes<br><textarea name="notes" rows="2" style="width:100%;">{{ old('notes') }}</textarea></label>
            <button class="btn" type="submit">Save Purchase</button>
        </form>
    </div>

    <div class="card">
        <div class="table-wrap">
            <table>
                <tr>
                    <th>Purchase Date</th>
                    <th>USD Amount</th>
                    <th>Available USD</th>
                    <th>Buy Rate</th>
                    <th>Total Cost</th>
                    <th>Seller</th>
                    <th>Reference</th>
                </tr>
                @forelse($purchases as $purchase)
                    <tr>
                        <td>{{ $purchase->purchase_date?->toDateString() }}</td>
                        <td>USD {{ number_format((float) $purchase->usd_amount, 2) }}</td>
                        <td>USD {{ number_format((float) $purchase->remaining_usd, 2) }}</td>
                        <td>BDT {{ number_format((float) $purchase->buy_rate, 4) }}</td>
                        <td>BDT {{ number_format((float) $purchase->total_bdt_cost, 2) }}</td>
                        <td>{{ $purchase->seller_name ?: '-' }}</td>
                        <td>{{ $purchase->reference ?: '-' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7">No Binance purchases found.</td></tr>
                @endforelse
            </table>
        </div>
    </div>
@endsection
