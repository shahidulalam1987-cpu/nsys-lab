@extends('layouts.admin')

@section('content')
    <h1>Balance Sheet</h1>
    <p>Current account balances and loan position.</p>

    <div class="stats-grid">
        <div class="stat-card"><p>Total BDT Balance</p><h2>BDT {{ number_format($summary['total_bdt_balance'], 2) }}</h2></div>
        <div class="stat-card"><p>Total USD Balance</p><h2>USD {{ number_format($summary['total_usd_balance'], 2) }}</h2></div>
        <div class="stat-card"><p>Remaining Payable</p><h2>BDT {{ number_format($summary['total_remaining_payable'], 2) }}</h2></div>
        <div class="stat-card"><p>Remaining Receivable</p><h2>BDT {{ number_format($summary['total_remaining_receivable'], 2) }}</h2></div>
    </div>

    <div class="card">
        <h2>Account Balances</h2>
        <div class="table-wrap">
            <table>
                <tr>
                    <th>Type</th>
                    <th>Account</th>
                    <th>Provider</th>
                    <th>Currency</th>
                    <th>Balance</th>
                    <th>Status</th>
                </tr>
                @forelse($accounts as $account)
                    <tr>
                        <td>{{ $account->typeLabel() }}</td>
                        <td>{{ $account->account_name }}</td>
                        <td>{{ $account->provider_name ?: '-' }}</td>
                        <td>{{ $account->currency }}</td>
                        <td>{{ $account->currency }} {{ number_format((float) $account->current_balance, 2) }}</td>
                        <td>{{ $account->statusLabel() }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6">No accounts found.</td></tr>
                @endforelse
            </table>
        </div>
    </div>
@endsection
