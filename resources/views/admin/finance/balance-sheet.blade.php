@extends('layouts.admin')

@section('content')
    <style>
        .finance-report-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 22px;
        }

        .finance-report-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            justify-content: flex-end;
        }

        .finance-report-note {
            border: 1px solid rgba(56, 189, 248, .35);
            background: rgba(14, 165, 233, .09);
            color: #bae6fd;
            padding: 14px 16px;
            border-radius: 14px;
            margin-bottom: 18px;
        }

        .finance-report-filter {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 12px;
            align-items: end;
        }

        .finance-report-filter label {
            display: grid;
            gap: 8px;
            font-weight: 800;
        }

        .finance-report-filter select {
            width: 100%;
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

        @media (max-width: 1100px) {
            .finance-report-filter {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 760px) {
            .finance-report-header {
                display: block;
            }

            .finance-report-actions {
                justify-content: flex-start;
                margin-top: 12px;
            }

            .finance-report-filter {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <div class="finance-report-header">
        <div>
            <h1>Finance Reports</h1>
            <p>Balance Sheet: current account balances for NSYS finance operations.</p>
        </div>
        <div class="finance-report-actions">
            <a class="btn" href="/admin/finance/accounts">Finance Accounts</a>
            <a class="btn" href="/admin/finance/reports/reconciliation">Reconciliation</a>
        </div>
    </div>

    <div class="finance-report-note">
        This report is a read-only account balance snapshot. Balance changes must be made through Finance Accounts and ledger-backed adjustments.
    </div>

    <div class="stats-grid">
        <div class="stat-card"><p>Total BDT Balance</p><h2>BDT {{ number_format($reportSummary['bdt_balance'], 2) }}</h2></div>
        <div class="stat-card"><p>Total USD Balance</p><h2>USD {{ number_format($reportSummary['usd_balance'], 2) }}</h2></div>
        <div class="stat-card"><p>Active Accounts</p><h2>{{ number_format($reportSummary['active_accounts']) }}</h2></div>
        <div class="stat-card"><p>Inactive Accounts</p><h2>{{ number_format($reportSummary['inactive_accounts']) }}</h2></div>
    </div>

    <div class="card">
        <h2>Filters</h2>
        <form class="finance-report-filter" method="GET" action="/admin/finance/reports/balance-sheet">
            <label>
                Account Type
                <select name="account_type">
                    <option value="">All Types</option>
                    @foreach($types as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['account_type'] ?? '') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>
            <label>
                Currency
                <select name="currency">
                    <option value="">All Currency</option>
                    @foreach($currencies as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['currency'] ?? '') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>
            <label>
                Status
                <select name="status">
                    <option value="">All Status</option>
                    @foreach($statuses as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>
            <div>
                <button class="btn" type="submit">Filter</button>
                <a href="/admin/finance/reports/balance-sheet" style="margin-left:12px;">Reset</a>
            </div>
        </form>
    </div>

    <div class="card table-wrap">
        <h2>Account Balances</h2>
        <table>
            <tr>
                <th>Account</th>
                <th>Type</th>
                <th>Provider</th>
                <th>Currency</th>
                <th>Balance</th>
                <th>Status</th>
            </tr>
            @forelse($accounts as $account)
                @php
                    $statusClass = [
                        'active' => 'badge-success',
                        'inactive' => 'badge-neutral',
                    ][$account->status] ?? 'badge-neutral';
                @endphp
                <tr>
                    <td>
                        <div class="account-name">{{ $account->account_name }}</div>
                        <div class="muted-small">{{ $account->account_number ?: 'No account number' }}</div>
                    </td>
                    <td>{{ $account->typeLabel() }}</td>
                    <td>{{ $account->provider_name ?: '-' }}</td>
                    <td>{{ $account->currency }}</td>
                    <td>{{ $account->currency }} {{ number_format((float) $account->current_balance, 2) }}</td>
                    <td><span class="badge {{ $statusClass }}">{{ $account->statusLabel() }}</span></td>
                </tr>
            @empty
                <tr><td colspan="6">No accounts found.</td></tr>
            @endforelse
        </table>
    </div>
@endsection
