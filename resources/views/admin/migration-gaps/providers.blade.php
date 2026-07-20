@extends('layouts.admin')

@section('content')
    <h1>Payment Providers</h1>
    <p>Provider master data for RedotPay, Tevau, Binance, and future payment sources.</p>

    <div class="card">
        <form method="POST" action="/admin/payment-providers" style="display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px;">
            @csrf
            <label>Code<br><input name="provider_code" required></label>
            <label>Name<br><input name="name" required></label>
            <label>Type<br><input name="provider_type" value="card_wallet" required></label>
            <label>Currency<br><select name="currency"><option>USD</option><option>BDT</option></select></label>
            <label>Status<br><select name="status"><option value="active">Active</option><option value="inactive">Inactive</option></select></label>
            <label>Notes<br><input name="notes"></label>
            <button class="btn" type="submit">Add Provider</button>
        </form>
    </div>

    <div class="card table-wrap">
        <table>
            <tr><th>Provider</th><th>Code</th><th>Type</th><th>Currency</th><th>Status</th><th>Transactions</th></tr>
            @forelse($providers as $provider)
                <tr>
                    <td>{{ $provider->name }}</td>
                    <td>{{ $provider->provider_code }}</td>
                    <td>{{ $provider->provider_type }}</td>
                    <td>{{ $provider->currency }}</td>
                    <td><span class="badge">{{ ucfirst($provider->status) }}</span></td>
                    <td>{{ $provider->transactions_count }}</td>
                </tr>
            @empty
                <tr><td colspan="6">No providers found.</td></tr>
            @endforelse
        </table>
    </div>
@endsection
