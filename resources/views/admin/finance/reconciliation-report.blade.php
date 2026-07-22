@extends('layouts.admin')

@section('content')
    <style>
        .reconciliation-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 22px;
        }

        .reconciliation-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            justify-content: flex-end;
        }

        .reconciliation-note {
            border: 1px solid rgba(56, 189, 248, .35);
            background: rgba(14, 165, 233, .09);
            color: #bae6fd;
            padding: 14px 16px;
            border-radius: 14px;
            margin-bottom: 18px;
        }

        .reconciliation-filter {
            display: flex;
            gap: 10px;
            align-items: end;
            flex-wrap: wrap;
        }

        .reconciliation-filter label {
            display: grid;
            gap: 8px;
            font-weight: 800;
        }

        .difference-ok {
            color: #22c55e;
            font-weight: 800;
        }

        .difference-bad {
            color: #ef4444;
            font-weight: 800;
        }

        .account-name {
            color: #e5f2ff;
            font-weight: 800;
        }

        .muted-small {
            color: #94a3b8;
            font-size: 13px;
            margin-top: 4px;
        }

        @media (max-width: 760px) {
            .reconciliation-header {
                display: block;
            }

            .reconciliation-actions {
                justify-content: flex-start;
                margin-top: 12px;
            }
        }
    </style>

    <div class="reconciliation-header">
        <div>
            <h1>Reconciliation</h1>
            <p>Compare each finance account balance with its latest immutable ledger balance.</p>
        </div>
        <div class="reconciliation-actions">
            <a class="btn" href="/admin/finance/accounts">Finance Accounts</a>
            <a class="btn" href="/admin/finance/reports/balance-sheet">Finance Reports</a>
        </div>
    </div>

    <div class="reconciliation-note">
        This is a read-only audit view. Reconciliation does not change balances, ledgers, payrolls, client funds, or card records.
    </div>

    <div class="stats-grid">
        <div class="stat-card"><p>Total Accounts</p><h2>{{ number_format($summary['total_accounts']) }}</h2></div>
        <div class="stat-card"><p>Matched</p><h2>{{ number_format($summary['matched']) }}</h2></div>
        <div class="stat-card"><p>Mismatched</p><h2>{{ number_format($summary['mismatched']) }}</h2></div>
        <div class="stat-card"><p>No Ledger</p><h2>{{ number_format($summary['no_ledger']) }}</h2></div>
    </div>

    <div class="card">
        <h2>Filters</h2>
        <form class="reconciliation-filter" method="GET" action="/admin/finance/reports/reconciliation">
            <label>
                Status
                <select name="status">
                    <option value="all" @selected($filter === 'all')>All Accounts</option>
                    <option value="mismatch" @selected($filter === 'mismatch')>Mismatched Only</option>
                </select>
            </label>
            <button class="btn" type="submit">Filter</button>
            <a href="/admin/finance/reports/reconciliation">Reset</a>
        </form>
    </div>

    <div class="card table-wrap">
        <h2>Account Balance Audit</h2>
        <table>
            <tr>
                <th>Account</th>
                <th>Currency</th>
                <th>Account Balance</th>
                <th>Last Ledger Balance</th>
                <th>Difference</th>
                <th>Last Ledger</th>
                <th>Status</th>
            </tr>
            @forelse($rows as $row)
                @php
                    $difference = (float) $row['difference'];
                    $differenceClass = $difference == 0.0 ? 'difference-ok' : 'difference-bad';
                @endphp
                <tr>
                    <td>
                        <div class="account-name">{{ $row['account']->account_name }}</div>
                        <div class="muted-small">{{ $row['account']->typeLabel() }}</div>
                    </td>
                    <td>{{ $row['account']->currency }}</td>
                    <td>{{ $row['account']->currency }} {{ number_format($row['current_balance'], 2) }}</td>
                    <td>
                        @if($row['has_ledger'])
                            {{ $row['account']->currency }} {{ number_format($row['ledger_balance'], 2) }}
                        @else
                            <span class="badge badge-neutral">No Ledger</span>
                        @endif
                    </td>
                    <td><span class="{{ $differenceClass }}">{{ $row['account']->currency }} {{ number_format($difference, 2) }}</span></td>
                    <td>{{ $row['last_ledger_at']?->format('Y-m-d H:i') ?: '-' }}</td>
                    <td><span class="badge {{ $difference == 0.0 ? 'badge-success' : 'badge-danger' }}">{{ $difference == 0.0 ? 'Matched' : 'Mismatch' }}</span></td>
                </tr>
            @empty
                <tr><td colspan="7">No finance accounts found.</td></tr>
            @endforelse
        </table>
    </div>
@endsection
