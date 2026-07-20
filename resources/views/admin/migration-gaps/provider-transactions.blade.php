@extends('layouts.admin')

@section('content')
    <h1>Provider Transactions</h1>
    <p>Manual provider transaction samples for reconciliation visibility. This does not change balances.</p>

    <div class="card">
        <form method="POST" action="/admin/provider-transactions" style="display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px;">
            @csrf
            <label>Provider<br><select name="payment_provider_id"><option value="">None</option>@foreach($providers as $provider)<option value="{{ $provider->id }}">{{ $provider->name }}</option>@endforeach</select></label>
            <label>Card<br><select name="facebook_card_id"><option value="">None</option>@foreach($cards as $card)<option value="{{ $card->id }}">{{ $card->card_name }}</option>@endforeach</select></label>
            <label>Date<br><input type="date" name="transaction_date" value="{{ now()->toDateString() }}" required></label>
            <label>Type<br><input name="transaction_type" value="manual" required></label>
            <label>Amount USD<br><input type="number" step="0.01" name="amount_usd" required></label>
            <label>Fee USD<br><input type="number" step="0.01" name="fee_usd" value="0"></label>
            <label>Reference<br><input name="reference"></label>
            <label>Status<br><select name="status"><option value="posted">Posted</option><option value="pending">Pending</option><option value="failed">Failed</option></select></label>
            <label style="grid-column:span 3;">Notes<br><input name="notes"></label>
            <button class="btn" type="submit">Add Transaction</button>
        </form>
    </div>

    <div class="card table-wrap">
        <table>
            <tr><th>Date</th><th>Provider</th><th>Card</th><th>Type</th><th>Amount</th><th>Fee</th><th>Status</th></tr>
            @forelse($transactions as $transaction)
                <tr>
                    <td>{{ $transaction->transaction_date?->toDateString() }}</td>
                    <td>{{ $transaction->provider?->name ?: '-' }}</td>
                    <td>{{ $transaction->card?->card_name ?: '-' }}</td>
                    <td>{{ $transaction->transaction_type }}</td>
                    <td>USD {{ number_format((float) $transaction->amount_usd, 2) }}</td>
                    <td>USD {{ number_format((float) $transaction->fee_usd, 2) }}</td>
                    <td><span class="badge">{{ ucfirst($transaction->status) }}</span></td>
                </tr>
            @empty
                <tr><td colspan="7">No provider transactions found.</td></tr>
            @endforelse
        </table>
    </div>
@endsection
