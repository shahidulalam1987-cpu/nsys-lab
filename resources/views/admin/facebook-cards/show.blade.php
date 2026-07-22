@extends('layouts.admin')

@section('content')
    <div style="display:flex;justify-content:space-between;gap:16px;align-items:flex-start;flex-wrap:wrap;">
        <div>
            <h1>Card Details</h1>
            <p>{{ $card->card_name }} balance, assignment, and recent movement history.</p>
        </div>
        <div style="display:flex;gap:8px;flex-wrap:wrap;">
            <a class="btn" href="/admin/facebook-cards">Back to Card Management</a>
            <a class="btn" href="/admin/facebook-cards/{{ $card->id }}/edit">Edit Card</a>
        </div>
    </div>

    <div class="stats-grid">
        <div class="stat-card"><p>Current Balance</p><h2>USD {{ number_format((float) $card->current_balance, 2) }}</h2></div>
        <div class="stat-card"><p>Status</p><h2>{{ $card->statusLabel() }}</h2></div>
        <div class="stat-card"><p>Provider</p><h2>{{ $card->providerLabel() }}</h2></div>
        <div class="stat-card"><p>Assigned Ad Account</p><h2>{{ $card->adAccount?->ad_account_name ?: '-' }}</h2></div>
    </div>

    <div class="card">
        <h2>{{ $card->card_name }}</h2>
        <p><strong>Card Type:</strong> {{ $card->card_type ?: '-' }}</p>
        <p><strong>Last 4:</strong> {{ $card->card_last_four ?: '-' }}</p>
        <p><strong>Provider:</strong> {{ $card->providerLabel() }}</p>
        <p><strong>Currency:</strong> USD</p>
        <p><strong>Assigned Ad Account:</strong> {{ $card->adAccount?->ad_account_name ?: '-' }}</p>
        <p><strong>Status:</strong> <span class="badge {{ $card->statusBadgeClass() }}">{{ $card->statusLabel() }}</span></p>
        <p><strong>Notes:</strong> {{ $card->notes ?: '-' }}</p>
    </div>

    <div class="card table-wrap">
        <h2>Recent Loads</h2>
        <table>
            <tr>
                <th>Date</th>
                <th>Binance Purchase</th>
                <th>USD Loaded</th>
                <th>Fee</th>
                <th>Reference</th>
            </tr>
            @forelse($recentLoads as $load)
                <tr>
                    <td>{{ $load->load_date?->toDateString() }}</td>
                    <td>{{ $load->binancePurchase?->purchase_date?->toDateString() ?: '-' }}</td>
                    <td>USD {{ number_format((float) $load->usd_loaded, 2) }}</td>
                    <td>USD {{ number_format((float) $load->fee_usd, 2) }}</td>
                    <td>{{ $load->transaction_reference ?: '-' }}</td>
                </tr>
            @empty
                <tr><td colspan="5">No recent loads found.</td></tr>
            @endforelse
        </table>
    </div>

    <div class="card table-wrap">
        <h2>Recent Transactions</h2>
        <table>
            <tr>
                <th>Date</th>
                <th>Client</th>
                <th>Campaign</th>
                <th>Total Deducted</th>
                <th>Profit</th>
            </tr>
            @forelse($recentTransactions as $transaction)
                <tr>
                    <td>{{ $transaction->transaction_date?->toDateString() }}</td>
                    <td>{{ $transaction->client?->company_name ?: '-' }}</td>
                    <td>{{ $transaction->campaign?->campaign_name ?: '-' }}</td>
                    <td>USD {{ number_format((float) $transaction->total_deducted_usd, 2) }}</td>
                    <td>BDT {{ number_format((float) $transaction->net_profit, 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="5">No recent transactions found.</td></tr>
            @endforelse
        </table>
    </div>
@endsection
