@extends('layouts.admin')

@section('content')
    <style>
        .fund-detail-header {
            align-items: flex-start;
            display: flex;
            gap: 14px;
            justify-content: space-between;
            margin-bottom: 16px;
        }

        .fund-detail-header p {
            margin: 4px 0 0;
        }

        .fund-actions,
        .ledger-actions,
        .ledger-filter-form {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .ledger-filter-form {
            align-items: end;
        }

        .ledger-filter-form label {
            color: var(--muted);
            display: grid;
            font-size: 12px;
            font-weight: 700;
            gap: 6px;
        }

        .fund-grid {
            display: grid;
            gap: 14px;
            grid-template-columns: repeat(4, minmax(0, 1fr));
        }

        .ledger-wrap {
            overflow-x: auto;
        }

        .ledger-table {
            min-width: 980px;
        }

        .ledger-table th:nth-child(n+5),
        .ledger-table td:nth-child(n+5) {
            text-align: right;
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

        .ledger-description {
            color: var(--text);
            font-weight: 700;
        }

        .ledger-reference {
            color: var(--muted);
            font-size: 12px;
            margin-top: 3px;
        }

        @media (max-width: 980px) {
            .fund-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 680px) {
            .fund-detail-header {
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

    <div class="fund-detail-header">
        <div>
            <h1>Client Fund Details</h1>
            <p>{{ $client->company_name }}</p>
        </div>
        <div class="fund-actions">
            <a class="btn" href="/admin/client-fund">Client Funds</a>
            <a class="btn" href="/admin/clients/{{ $client->id }}">Client Profile</a>
            <a class="btn" href="/admin/salary-payments/create">Receive Payment</a>
        </div>
    </div>

    <div class="fund-grid">
        <div class="stat-card {{ $row['balance_class'] }}">
            <p>Employee Salary Fund Balance</p>
            <h2>BDT {{ number_format($row['available_balance'], 2) }}</h2>
            <p>{{ $balanceLabel($row['available_balance']) }}</p>
        </div>
        <div class="stat-card {{ $clientFundDashboardService->balanceClass($row['ads_balance'] ?? 0) }}">
            <p>Facebook Ads Fund Balance</p>
            <h2>BDT {{ number_format($row['ads_balance'] ?? 0, 2) }}</h2>
            <p>{{ $balanceLabel($row['ads_balance'] ?? 0) }}</p>
        </div>
        <div class="stat-card {{ $clientFundDashboardService->balanceClass($row['combined_balance'] ?? 0) }}">
            <p>Combined Balance</p>
            <h2>BDT {{ number_format($row['combined_balance'] ?? 0, 2) }}</h2>
            <p>{{ $balanceLabel($row['combined_balance'] ?? 0) }}</p>
        </div>
        <div class="stat-card">
            <p>Pending Payments</p>
            <h2>BDT {{ number_format($row['pending_payments'], 2) }}</h2>
            <p>{{ number_format($row['pending_payment_count']) }} Pending</p>
        </div>
    </div>

    <div class="fund-grid">
        <div class="stat-card">
            <p>Salary Fund Received</p>
            <h2>BDT {{ number_format($row['fund_received'], 2) }}</h2>
        </div>
        <div class="stat-card">
            <p>Salary Fund Used</p>
            <h2>BDT {{ number_format($row['salary_used'], 2) }}</h2>
        </div>
        <div class="stat-card">
            <p>Ads Fund Received</p>
            <h2>BDT {{ number_format($row['ads_received'] ?? 0, 2) }}</h2>
        </div>
        <div class="stat-card">
            <p>Ads Fund Spent</p>
            <h2>BDT {{ number_format($row['ads_spent'] ?? 0, 2) }}</h2>
        </div>
    </div>

    <div class="card">
        <h2>Transaction Ledger</h2>
        <form class="ledger-filter-form" method="GET" action="/admin/client-fund/{{ $client->id }}/details">
            <label>
                From Date
                <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}">
            </label>
            <label>
                To Date
                <input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}">
            </label>
            <label>
                Fund Type
                <select name="fund_type">
                    <option value="">All Funds</option>
                    <option value="employee_salary" {{ ($filters['fund_type'] ?? '') === 'employee_salary' ? 'selected' : '' }}>Employee Salary Fund</option>
                    <option value="facebook_ads" {{ ($filters['fund_type'] ?? '') === 'facebook_ads' ? 'selected' : '' }}>Facebook Ads Fund</option>
                </select>
            </label>
            <button class="btn" type="submit">Filter</button>
            <a class="btn sidebar-muted" href="/admin/client-fund/{{ $client->id }}/details">Reset</a>
            <a class="btn" href="/admin/client-fund/{{ $client->id }}/details/export/csv?{{ http_build_query($filters ?? []) }}">Export CSV</a>
            <a class="btn" href="/admin/client-fund/{{ $client->id }}/details/export/excel?{{ http_build_query($filters ?? []) }}">Export Excel</a>
        </form>

        <div class="ledger-wrap">
            <table class="ledger-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Fund / Direction</th>
                        <th>Reference</th>
                        <th>Description</th>
                        <th>Credit</th>
                        <th>Debit</th>
                        <th>Running Balance</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($ledger as $entry)
                        <tr>
                            <td>{{ $entry['date'] }}</td>
                            <td>{{ $entry['type'] }}</td>
                            <td>{{ $entry['reference'] ?: '-' }}</td>
                            <td>
                                <div class="ledger-description">{{ $entry['description'] }}</div>
                                <div class="ledger-reference">{{ $entry['fund_type'] === 'facebook_ads' ? 'Facebook Ads Fund' : 'Employee Salary Fund' }}</div>
                            </td>
                            <td>{{ $entry['credit'] > 0 ? 'BDT ' . number_format($entry['credit'], 2) : '-' }}</td>
                            <td>{{ $entry['debit'] > 0 ? 'BDT ' . number_format($entry['debit'], 2) : '-' }}</td>
                            <td>BDT {{ number_format($entry['running_balance'], 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7">No client fund transactions found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
