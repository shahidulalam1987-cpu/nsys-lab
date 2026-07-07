@extends('layouts.admin')

@section('content')
    <h1>Client Dual Fund Dashboard</h1>
    <p>Track Employee Salary Fund and Facebook Ads Fund separately for every client.</p>
    <p>
        <a class="btn" href="/admin/client-fund/export/csv">Export CSV</a>
        <a class="btn" href="/admin/client-fund/export/excel">Export Excel</a>
    </p>

    <style>
        .client-fund-grid {
            display: grid;
            gap: 12px;
            grid-template-columns: repeat(6, minmax(150px, 1fr));
            margin: 18px 0;
        }

        .client-fund-card {
            background: rgba(255,255,255,.08);
            border: 1px solid rgba(255,255,255,.16);
            border-radius: 10px;
            padding: 14px;
        }

        .client-fund-card p {
            color: #a9b7cf;
            font-size: 13px;
            margin: 0 0 8px;
        }

        .client-fund-card h2 {
            font-size: 20px;
            margin: 0;
        }

        .balance-positive {
            border-color: rgba(34, 197, 94, .5) !important;
            color: #86efac;
        }

        .balance-warning {
            border-color: rgba(245, 158, 11, .6) !important;
            color: #fcd34d;
        }

        .balance-critical {
            border-color: rgba(239, 68, 68, .65) !important;
            color: #fca5a5;
        }

        .alert-badge {
            display: inline-block;
            margin-top: 6px;
            padding: 4px 8px;
            border-radius: 999px;
            background: rgba(239, 68, 68, .18);
            color: #fca5a5;
            font-size: 12px;
            font-weight: 700;
        }

        .client-fund-table-wrap {
            overflow-x: auto;
        }

        .client-fund-table {
            min-width: 1080px;
        }

        .client-fund-table th,
        .client-fund-table td {
            vertical-align: middle;
        }

        .client-fund-table th:not(:first-child),
        .client-fund-table td:not(:first-child) {
            text-align: right;
        }

        .client-fund-table th:last-child,
        .client-fund-table td:last-child {
            text-align: center;
        }

        @media (max-width: 1200px) {
            .client-fund-grid {
                grid-template-columns: repeat(3, minmax(150px, 1fr));
            }
        }

        @media (max-width: 700px) {
            .client-fund-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <div class="client-fund-grid">
        <div class="client-fund-card">
            <p>Salary Fund Received</p>
            <h2>BDT {{ number_format($summary['total_fund_received'], 2) }}</h2>
        </div>
        <div class="client-fund-card">
            <p>Salary Fund Used</p>
            <h2>BDT {{ number_format($summary['total_salary_used'], 2) }}</h2>
        </div>
        <div class="client-fund-card {{ $clientFundDashboardService->balanceClass($summary['available_balance']) }}">
            <p>Salary Fund Balance</p>
            <h2>BDT {{ number_format($summary['available_balance'], 2) }}</h2>
        </div>
        <div class="client-fund-card">
            <p>Ads Fund Received</p>
            <h2>BDT {{ number_format($summary['ads_fund_received'] ?? 0, 2) }}</h2>
        </div>
        <div class="client-fund-card">
            <p>Ads Fund Spent</p>
            <h2>BDT {{ number_format($summary['ads_fund_spent'] ?? 0, 2) }}</h2>
        </div>
        <div class="client-fund-card">
            <p>Combined Client Balance</p>
            <h2>BDT {{ number_format($summary['combined_client_balance'] ?? 0, 2) }}</h2>
        </div>
    </div>

    <div class="card">
        <h2>Client-wise Fund Summary</h2>
        <div class="client-fund-table-wrap">
            <table class="client-fund-table">
                <tr>
                    <th>Client</th>
                    <th>Salary Received</th>
                    <th>Salary Used</th>
                    <th>Salary Balance</th>
                    <th>Ads Received</th>
                    <th>Ads Spent</th>
                    <th>Ads Balance</th>
                    <th>Combined Balance</th>
                    <th>Pending Payments</th>
                    <th>Action</th>
                </tr>
                @forelse($rows as $row)
                    <tr>
                        <td style="text-align:left;">{{ $row['client']->company_name }}</td>
                        <td>BDT {{ number_format($row['fund_received'], 2) }}</td>
                        <td>BDT {{ number_format($row['salary_used'], 2) }}</td>
                        <td class="{{ $row['balance_class'] }}">BDT {{ number_format($row['available_balance'], 2) }}</td>
                        <td>BDT {{ number_format($row['ads_received'] ?? 0, 2) }}</td>
                        <td>BDT {{ number_format($row['ads_spent'] ?? 0, 2) }}</td>
                        <td>BDT {{ number_format($row['ads_balance'] ?? 0, 2) }}</td>
                        <td>BDT {{ number_format($row['combined_balance'] ?? 0, 2) }}</td>
                        <td>
                            BDT {{ number_format($row['pending_payments'], 2) }}
                            <div style="color:#a9b7cf; font-size:12px;">{{ number_format($row['pending_payment_count']) }} pending</div>
                        </td>
                        <td><a class="btn" href="/admin/client-fund/{{ $row['client']->id }}/details">View Details</a></td>
                    </tr>
                @empty
                    <tr><td colspan="10">No client fund data found.</td></tr>
                @endforelse
            </table>
        </div>
    </div>
@endsection
