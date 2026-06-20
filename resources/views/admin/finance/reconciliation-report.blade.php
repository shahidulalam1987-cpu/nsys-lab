@extends('layouts.admin')

@section('content')
    <h1>Finance Reconciliation Report</h1>
    <p>Compare each Finance Account balance with its latest immutable ledger balance.</p>

    <div class="stats-grid">
        <div class="stat-card"><p>Total Accounts</p><h2>{{ $rows->count() }}</h2></div>
        <div class="stat-card"><p>Balance Mismatches</p><h2>{{ $mismatchCount }}</h2></div>
    </div>

    <div class="card table-wrap">
        <table>
            <tr><th>Account</th><th>Currency</th><th>Account Balance</th><th>Last Ledger Balance</th><th>Difference</th><th>Last Ledger</th><th>Status</th></tr>
            @forelse($rows as $row)
                <tr>
                    <td>{{ $row['account']->account_name }}</td>
                    <td>{{ $row['account']->currency }}</td>
                    <td>{{ $row['account']->currency }} {{ number_format($row['current_balance'], 2) }}</td>
                    <td>{{ $row['account']->currency }} {{ number_format($row['ledger_balance'], 2) }}</td>
                    <td>{{ $row['account']->currency }} {{ number_format($row['difference'], 2) }}</td>
                    <td>{{ $row['last_ledger_at']?->format('Y-m-d H:i') ?: '-' }}</td>
                    <td><span class="badge {{ $row['difference'] == 0 ? 'badge-success' : 'badge-danger' }}">{{ $row['difference'] == 0 ? 'Matched' : 'Mismatch' }}</span></td>
                </tr>
            @empty
                <tr><td colspan="7">No finance accounts found.</td></tr>
            @endforelse
        </table>
    </div>
@endsection
