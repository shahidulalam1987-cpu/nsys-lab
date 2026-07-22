@extends('layouts.admin')

@section('content')
    @php
        $difference = (float) $history->difference;
        $differenceClass = $difference >= 0 ? 'badge-success' : 'badge-danger';
    @endphp

    <div style="display:flex;justify-content:space-between;gap:16px;align-items:flex-start;flex-wrap:wrap;">
        <div>
            <h1>Funding History Details</h1>
            <p>Balance update record for {{ $history->sourceLabel() }}.</p>
        </div>
        <a class="btn" href="/admin/facebook-financial/funding-dashboard">Back to Funding Dashboard</a>
    </div>

    <div class="stats-grid">
        <div class="stat-card"><p>Previous Balance</p><h2>USD {{ number_format((float) $history->previous_balance, 2) }}</h2></div>
        <div class="stat-card"><p>New Balance</p><h2>USD {{ number_format((float) $history->new_balance, 2) }}</h2></div>
        <div class="stat-card"><p>Difference</p><h2>{{ $difference >= 0 ? '+' : '-' }} USD {{ number_format(abs($difference), 2) }}</h2></div>
    </div>

    <div class="card table-wrap">
        <h2>Update Details</h2>
        <table>
            <tr><th>Source</th><td>{{ $history->sourceLabel() }}</td></tr>
            <tr><th>Date</th><td>{{ $history->balance_date?->toDateString() }}</td></tr>
            <tr><th>Previous Balance</th><td>USD {{ number_format((float) $history->previous_balance, 2) }}</td></tr>
            <tr><th>New Balance</th><td>USD {{ number_format((float) $history->new_balance, 2) }}</td></tr>
            <tr><th>Difference</th><td><span class="badge {{ $differenceClass }}">{{ $difference >= 0 ? '+' : '-' }} USD {{ number_format(abs($difference), 2) }}</span></td></tr>
            <tr><th>Updated By</th><td>{{ $history->createdBy?->name ?: '-' }}</td></tr>
            <tr><th>Note</th><td>{{ $history->note ?: '-' }}</td></tr>
        </table>
    </div>
@endsection
