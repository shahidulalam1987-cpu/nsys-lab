@extends('layouts.admin')

@section('content')
    <style>
        .finance-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 22px;
        }

        .finance-header p {
            max-width: 760px;
        }

        .finance-source-note {
            border: 1px solid rgba(56, 189, 248, .35);
            background: rgba(14, 165, 233, .09);
            color: #bae6fd;
            padding: 14px 16px;
            border-radius: 14px;
            margin-bottom: 18px;
        }

        .finance-actions {
            display: grid;
            grid-template-columns: repeat(6, minmax(0, 1fr));
            gap: 10px;
        }

        .finance-actions .btn {
            text-align: center;
        }

        .finance-muted {
            color: #94a3b8;
            font-size: 13px;
            margin-top: 4px;
        }

        .finance-account-name {
            color: #e5f2ff;
            font-weight: 800;
        }

        @media (max-width: 1180px) {
            .finance-actions {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }
        }

        @media (max-width: 760px) {
            .finance-header {
                display: block;
            }

            .finance-actions {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <div class="finance-header">
        <div>
            <h1>Finance Dashboard</h1>
            <p>Account balances and operating finance summary for NSYS Agency.</p>
        </div>
    </div>

    <div class="finance-source-note">
        Balances are based on finance accounts and ledger-backed updates. USD assets also include tracked Binance remaining USD and card balances.
    </div>

    <div class="stats-grid">
        <div class="stat-card"><p>Total BDT Balance</p><h2>BDT {{ number_format($summary['total_bdt_balance'], 2) }}</h2></div>
        <div class="stat-card"><p>Total USD Balance</p><h2>USD {{ number_format($summary['total_usd_balance'], 2) }}</h2></div>
        <div class="stat-card"><p>Total USD Assets</p><h2>USD {{ number_format($summary['total_usd_assets'], 2) }}</h2></div>
        <div class="stat-card"><p>Salary Paid This Month</p><h2>BDT {{ number_format($summary['salary_paid_this_month'], 2) }}</h2></div>
        <div class="stat-card"><p>Client Salary Payments This Month</p><h2>BDT {{ number_format($summary['client_payments_this_month'], 2) }}</h2></div>
        <div class="stat-card"><p>Upcoming Salary Liability</p><h2>BDT {{ number_format($summary['upcoming_salary_liability'], 2) }}</h2></div>
    </div>

    <div class="card">
        <h2>Quick Actions</h2>
        <div class="finance-actions">
            <a class="btn" href="/admin/finance/accounts">Finance Accounts</a>
            <a class="btn" href="/admin/facebook-financial/funding-dashboard">Funding Dashboard</a>
            <a class="btn" href="/admin/facebook-cards">Card Management</a>
            <a class="btn" href="/admin/finance/reports/reconciliation">Reconciliation</a>
            <a class="btn" href="/admin/finance/reports/balance-sheet">Finance Reports</a>
            <a class="btn" href="/admin/payroll/payment-report">Salary Payments</a>
        </div>
    </div>

    <div class="card table-wrap">
        <h2>Recent Accounts</h2>
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
                        <div class="finance-account-name">{{ $account->account_name }}</div>
                        <div class="finance-muted">{{ $account->account_number ?: 'No account number' }}</div>
                    </td>
                    <td>{{ $account->typeLabel() }}</td>
                    <td>{{ $account->provider_name ?: '-' }}</td>
                    <td>{{ $account->currency }}</td>
                    <td>{{ $account->currency }} {{ number_format((float) $account->current_balance, 2) }}</td>
                    <td><span class="badge {{ $statusClass }}">{{ $account->statusLabel() }}</span></td>
                </tr>
            @empty
                <tr><td colspan="6">No finance accounts found.</td></tr>
            @endforelse
        </table>
    </div>
@endsection
