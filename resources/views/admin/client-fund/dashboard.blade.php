@extends('layouts.admin')

@section('content')
    <style>
        .fund-header {
            align-items: flex-start;
            display: flex;
            gap: 14px;
            justify-content: space-between;
            margin-bottom: 16px;
        }

        .fund-header p {
            margin: 4px 0 0;
        }

        .fund-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .fund-grid {
            display: grid;
            gap: 14px;
            grid-template-columns: repeat(4, minmax(0, 1fr));
        }

        .fund-table-wrap {
            overflow-x: auto;
        }

        .fund-table {
            min-width: 1120px;
        }

        .fund-table th,
        .fund-table td {
            vertical-align: middle;
        }

        .fund-table th:not(:first-child),
        .fund-table td:not(:first-child) {
            text-align: right;
        }

        .fund-table th:last-child,
        .fund-table td:last-child {
            text-align: center;
        }

        .fund-client-name {
            color: var(--text);
            font-weight: 800;
        }

        .fund-meta {
            color: var(--muted);
            font-size: 12px;
            margin-top: 4px;
        }

        .balance-positive {
            color: #86efac;
        }

        .balance-warning {
            color: #fcd34d;
        }

        .balance-critical {
            color: #fca5a5;
        }

        @media (max-width: 980px) {
            .fund-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 680px) {
            .fund-header {
                display: block;
            }

            .fund-actions {
                margin-top: 12px;
            }

            .fund-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>

    @php
        $balanceLabel = fn ($amount) => $amount < 0 ? 'Due' : 'Available';
    @endphp

    <div class="fund-header">
        <div>
            <h1>Client Funds</h1>
            <p>Track Employee Salary Fund and Facebook Ads Fund separately for every client.</p>
        </div>
        <div class="fund-actions">
            <a class="btn" href="/admin/salary-payments/create">Receive Payment</a>
            <a class="btn" href="/admin/salary-payments/pending">Pending Payments</a>
            <a class="btn" href="/admin/client-fund/daily-statement">Daily Statement</a>
            <a class="btn" href="/admin/client-fund/export/csv">Export CSV</a>
            <a class="btn" href="/admin/client-fund/export/excel">Export Excel</a>
        </div>
    </div>

    <div class="fund-grid">
        <div class="stat-card {{ $clientFundDashboardService->balanceClass($summary['available_balance']) }}">
            <p>Salary Fund Balance</p>
            <h2>BDT {{ number_format($summary['available_balance'], 2) }}</h2>
            <p>{{ $balanceLabel($summary['available_balance']) }}</p>
        </div>
        <div class="stat-card {{ $clientFundDashboardService->balanceClass($summary['ads_fund_balance'] ?? 0) }}">
            <p>Ads Fund Balance</p>
            <h2>BDT {{ number_format($summary['ads_fund_balance'] ?? 0, 2) }}</h2>
            <p>{{ $balanceLabel($summary['ads_fund_balance'] ?? 0) }}</p>
        </div>
        <div class="stat-card {{ $clientFundDashboardService->balanceClass($summary['combined_client_balance'] ?? 0) }}">
            <p>Combined Client Balance</p>
            <h2>BDT {{ number_format($summary['combined_client_balance'] ?? 0, 2) }}</h2>
            <p>{{ $balanceLabel($summary['combined_client_balance'] ?? 0) }}</p>
        </div>
        <div class="stat-card">
            <p>Pending Payments</p>
            <h2>BDT {{ number_format($summary['pending_client_payments'] ?? 0, 2) }}</h2>
            <p>{{ number_format($summary['pending_client_payment_count'] ?? 0) }} Pending</p>
        </div>
    </div>

    <div class="fund-grid">
        <div class="stat-card">
            <p>Salary Fund Received</p>
            <h2>BDT {{ number_format($summary['total_fund_received'], 2) }}</h2>
        </div>
        <div class="stat-card">
            <p>Salary Fund Used</p>
            <h2>BDT {{ number_format($summary['total_salary_used'], 2) }}</h2>
        </div>
        <div class="stat-card">
            <p>Ads Fund Received</p>
            <h2>BDT {{ number_format($summary['ads_fund_received'] ?? 0, 2) }}</h2>
        </div>
        <div class="stat-card">
            <p>Ads Fund Spent</p>
            <h2>BDT {{ number_format($summary['ads_fund_spent'] ?? 0, 2) }}</h2>
        </div>
    </div>

    <div class="card">
        <h2>Client-wise Fund Summary</h2>
        <div class="fund-table-wrap">
            <table class="fund-table">
                <thead>
                    <tr>
                        <th rowspan="2">Client</th>
                        <th colspan="3">Employee Salary Fund</th>
                        <th colspan="3">Facebook Ads Fund</th>
                        <th rowspan="2">Combined Balance</th>
                        <th rowspan="2">Pending Payments</th>
                        <th rowspan="2">Action</th>
                    </tr>
                    <tr>
                        <th>Received</th>
                        <th>Used</th>
                        <th>Balance</th>
                        <th>Received</th>
                        <th>Spent</th>
                        <th>Balance</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rows as $row)
                        <tr>
                            <td style="text-align:left;">
                                <div class="fund-client-name">{{ $row['client']->company_name }}</div>
                                <div class="fund-meta">{{ $row['combined_balance'] < 0 ? 'Due' : 'Available' }}</div>
                            </td>
                            <td>BDT {{ number_format($row['fund_received'], 2) }}</td>
                            <td>BDT {{ number_format($row['salary_used'], 2) }}</td>
                            <td class="{{ $row['balance_class'] }}">BDT {{ number_format($row['available_balance'], 2) }}</td>
                            <td>BDT {{ number_format($row['ads_received'] ?? 0, 2) }}</td>
                            <td>BDT {{ number_format($row['ads_spent'] ?? 0, 2) }}</td>
                            <td class="{{ $clientFundDashboardService->balanceClass($row['ads_balance'] ?? 0) }}">BDT {{ number_format($row['ads_balance'] ?? 0, 2) }}</td>
                            <td class="{{ $clientFundDashboardService->balanceClass($row['combined_balance'] ?? 0) }}">BDT {{ number_format($row['combined_balance'] ?? 0, 2) }}</td>
                            <td>
                                BDT {{ number_format($row['pending_payments'], 2) }}
                                <div class="fund-meta">{{ number_format($row['pending_payment_count']) }} pending</div>
                            </td>
                            <td><a class="btn" href="/admin/client-fund/{{ $row['client']->id }}/details">View Details</a></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10">No client fund data found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
