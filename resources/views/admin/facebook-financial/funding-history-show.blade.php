@extends('layouts.admin')

@section('content')
    <h1>Funding History Details</h1>
    <p>Balance update record for {{ $history->sourceLabel() }}.</p>

    <div class="card">
        <div class="table-wrap">
            <table>
                <tr><th>Source</th><td>{{ $history->sourceLabel() }}</td></tr>
                <tr><th>Date</th><td>{{ $history->balance_date?->toDateString() }}</td></tr>
                <tr><th>Previous Balance</th><td>USD {{ number_format((float) $history->previous_balance, 2) }}</td></tr>
                <tr><th>New Balance</th><td>USD {{ number_format((float) $history->new_balance, 2) }}</td></tr>
                <tr><th>Difference</th><td>USD {{ number_format((float) $history->difference, 2) }}</td></tr>
                <tr><th>Updated By</th><td>{{ $history->createdBy?->name ?: '-' }}</td></tr>
                <tr><th>Note</th><td>{{ $history->note ?: '-' }}</td></tr>
            </table>
        </div>
    </div>

    <a class="btn" href="/admin/facebook-financial/funding-dashboard">Back to Funding Dashboard</a>
@endsection
