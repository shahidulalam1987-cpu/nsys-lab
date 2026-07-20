@extends('layouts.admin')

@section('content')
    <h1>Ad Account Billing History</h1>
    <p>Historical billing records for ad accounts. This is informational and does not affect finance ledgers.</p>

    <div class="card">
        <form method="POST" action="/admin/ad-account-billing-history" style="display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px;">
            @csrf
            <label>Ad Account<br><select name="ad_account_id" required>@foreach($adAccounts as $account)<option value="{{ $account->id }}">{{ $account->ad_account_name }}</option>@endforeach</select></label>
            <label>Billing Date<br><input type="date" name="billing_date" required></label>
            <label>Amount USD<br><input type="number" step="0.01" name="billing_amount_usd" required></label>
            <label>Paid Date<br><input type="date" name="paid_date"></label>
            <label>Status<br><select name="payment_status"><option value="pending">Pending</option><option value="paid">Paid</option><option value="overdue">Overdue</option></select></label>
            <label>Reference<br><input name="reference"></label>
            <label style="grid-column:span 2;">Notes<br><input name="notes"></label>
            <button class="btn" type="submit">Add Billing</button>
        </form>
    </div>

    <div class="card table-wrap">
        <table>
            <tr><th>Billing Date</th><th>Ad Account</th><th>Amount</th><th>Paid Date</th><th>Status</th><th>Reference</th></tr>
            @forelse($history as $row)
                <tr>
                    <td>{{ $row->billing_date?->toDateString() }}</td>
                    <td>{{ $row->adAccount?->ad_account_name ?: '-' }}</td>
                    <td>USD {{ number_format((float) $row->billing_amount_usd, 2) }}</td>
                    <td>{{ $row->paid_date?->toDateString() ?: '-' }}</td>
                    <td><span class="badge">{{ ucfirst($row->payment_status) }}</span></td>
                    <td>{{ $row->reference ?: '-' }}</td>
                </tr>
            @empty
                <tr><td colspan="6">No billing history found.</td></tr>
            @endforelse
        </table>
    </div>
@endsection
