@extends('layouts.admin')

@section('content')
    <h1>Card Load History</h1>
    <p>Track USD moved from Binance purchases into Facebook payment cards.</p>

    <div class="card">
        <h2>Load Card</h2>
        <form method="POST" action="/admin/facebook-financial/card-loads" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:12px;">
            @csrf
            <label>Date<br><input type="date" name="load_date" value="{{ old('load_date', now()->toDateString()) }}" required></label>
            <label>Card<br>
                <select name="facebook_card_id" required>
                    <option value="">Select Card</option>
                    @foreach($cards as $card)
                        <option value="{{ $card->id }}">{{ $card->provider ?: 'Other' }} - {{ $card->card_name }} ({{ $card->card_last_four ?: 'No Last 4' }})</option>
                    @endforeach
                </select>
            </label>
            <label>Binance Purchase<br>
                <select name="binance_purchase_id" required>
                    <option value="">Select Purchase</option>
                    @foreach($purchases as $purchase)
                        <option value="{{ $purchase->id }}">{{ $purchase->purchase_date?->toDateString() }} | Available USD {{ number_format((float) $purchase->remaining_usd, 2) }} @ BDT {{ number_format((float) $purchase->buy_rate, 4) }}</option>
                    @endforeach
                </select>
            </label>
            <label>USD Loaded<br><input type="number" step="0.01" min="0.01" name="usd_loaded" required></label>
            <label>Fee USD<br><input type="number" step="0.01" min="0" name="fee_usd" value="{{ old('fee_usd', 0) }}"></label>
            <label>Reference<br><input type="text" name="transaction_reference" value="{{ old('transaction_reference') }}"></label>
            <label style="grid-column:1/-1;">Notes<br><textarea name="notes" rows="2" style="width:100%;"></textarea></label>
            <button class="btn" type="submit">Save Load</button>
        </form>
    </div>

    <div class="card">
        <div class="table-wrap">
            <table>
                <tr>
                    <th>Date</th>
                    <th>Provider</th>
                    <th>Card</th>
                    <th>Binance Purchase</th>
                    <th>Buy Rate</th>
                    <th>USD Loaded</th>
                    <th>Fee</th>
                    <th>Notes</th>
                </tr>
                @forelse($loads as $load)
                    <tr>
                        <td>{{ $load->load_date?->toDateString() }}</td>
                        <td>{{ $load->card?->provider ?: '-' }}</td>
                        <td>{{ $load->card?->card_name ?: '-' }}</td>
                        <td>{{ $load->binancePurchase?->purchase_date?->toDateString() }}</td>
                        <td>BDT {{ number_format((float) $load->binancePurchase?->buy_rate, 4) }}</td>
                        <td>USD {{ number_format((float) $load->usd_loaded, 2) }}</td>
                        <td>USD {{ number_format((float) $load->fee_usd, 2) }}</td>
                        <td>{{ $load->notes ?: '-' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="8">No card loads found.</td></tr>
                @endforelse
            </table>
        </div>
    </div>
@endsection
