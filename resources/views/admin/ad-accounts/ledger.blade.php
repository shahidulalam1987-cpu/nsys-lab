@extends('layouts.admin')

@section('content')
    <h1>Ad Account Financial Ledger</h1>
    <p>Track threshold updates, balance adjustments, billing payments, credits, debits, and status changes.</p>

    <div class="card">
        <form method="GET" action="/admin/ad-account-ledger" style="display:flex;gap:10px;align-items:end;flex-wrap:wrap;">
            <label>Ad Account<br>
                <select name="ad_account_id">
                    <option value="">All Ad Accounts</option>
                    @foreach($adAccounts as $account)
                        <option value="{{ $account->id }}" @selected(($filters['ad_account_id'] ?? '') == $account->id)>{{ $account->ad_account_name }}</option>
                    @endforeach
                </select>
            </label>
            <label>Transaction Type<br>
                <select name="transaction_type">
                    <option value="">All Types</option>
                    @foreach($transactionTypes as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['transaction_type'] ?? '') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>
            <button class="btn" type="submit">Filter</button>
            <a href="/admin/ad-account-ledger">Reset</a>
        </form>
    </div>

    <div class="card">
        <div class="table-wrap">
            <table>
                <tr>
                    <th>Date</th>
                    <th>Ad Account</th>
                    <th>Transaction Type</th>
                    <th>Amount</th>
                    <th>Previous Value</th>
                    <th>New Value</th>
                    <th>Created By</th>
                    <th>Actions</th>
                </tr>
                @forelse($ledgers as $ledger)
                    <tr>
                        <td>{{ $ledger->transaction_date?->toDateString() }}</td>
                        <td>{{ $ledger->adAccount?->ad_account_name ?: '-' }}</td>
                        <td>{{ $ledger->typeLabel() }}</td>
                        <td>USD {{ number_format((float) $ledger->amount, 2) }}</td>
                        <td>{{ $ledger->previous_value !== null ? 'USD ' . number_format((float) $ledger->previous_value, 2) : '-' }}</td>
                        <td>{{ $ledger->new_value !== null ? 'USD ' . number_format((float) $ledger->new_value, 2) : '-' }}</td>
                        <td>{{ $ledger->creator?->name ?: '-' }}</td>
                        <td><a href="/admin/ad-account-ledger/{{ $ledger->id }}">View</a></td>
                    </tr>
                @empty
                    <tr><td colspan="8">No ledger records found.</td></tr>
                @endforelse
            </table>
        </div>
        {{ $ledgers->links() }}
    </div>
@endsection
