@extends('layouts.admin')

@section('content')
@php
    $today = $dashboard['today'];
    $month = $dashboard['month'];
    $finance = $dashboard['finance'];
    $alerts = $dashboard['alerts'];
    $filters = $dashboard['filters'];
    $employees = $dashboard['employees'];
@endphp

<style>
    .executive-header {
        align-items: flex-start;
        display: flex;
        gap: 14px;
        justify-content: space-between;
        margin-bottom: 18px;
    }

    .executive-toolbar,
    .executive-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
    }

    .executive-filter {
        align-items: end;
        display: grid;
        gap: 8px;
        grid-template-columns: repeat(5, minmax(130px, 1fr));
        margin-top: 12px;
    }

    .executive-filter label {
        color: var(--muted);
        display: grid;
        font-size: 12px;
        gap: 4px;
    }

    .kpi-grid {
        display: grid;
        gap: 12px;
        grid-template-columns: repeat(5, minmax(150px, 1fr));
        margin: 16px 0 22px;
    }

    .mini-grid {
        display: grid;
        gap: 12px;
        grid-template-columns: repeat(4, minmax(160px, 1fr));
    }

    .kpi-card {
        background: rgba(255,255,255,.08);
        border: 1px solid rgba(255,255,255,.16);
        border-radius: 10px;
        padding: 14px;
    }

    .kpi-card p {
        font-size: 12px;
        margin: 0 0 8px;
    }

    .kpi-card h2,
    .kpi-card h3 {
        margin: 0;
    }

    .executive-section {
        margin-top: 22px;
    }

    .two-column {
        display: grid;
        gap: 14px;
        grid-template-columns: 1fr 1fr;
    }

    .table-scroll {
        overflow-x: auto;
    }

    .compact-table {
        min-width: 720px;
    }

    .alert-list {
        display: grid;
        gap: 10px;
        grid-template-columns: repeat(3, minmax(160px, 1fr));
    }

    .alert-item {
        border-left: 4px solid var(--warning);
    }

    .alert-item.critical {
        border-left-color: var(--danger);
    }

    .trend-row {
        display: grid;
        gap: 8px;
        grid-template-columns: 120px repeat(4, 1fr);
        padding: 8px 0;
        border-bottom: 1px solid var(--line);
    }

    @media (max-width: 1180px) {
        .kpi-grid,
        .mini-grid,
        .alert-list {
            grid-template-columns: repeat(2, minmax(150px, 1fr));
        }

        .two-column {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 700px) {
        .executive-header {
            display: block;
        }

        .executive-filter,
        .kpi-grid,
        .mini-grid,
        .alert-list {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="executive-header">
    <div>
        <h1>Executive Dashboard</h1>
        <p>Agency owner business intelligence overview. Data is read-only and sourced from existing ledgers, payroll, client fund, and performance records.</p>
    </div>
    <div class="executive-actions">
        <a class="btn" href="{{ $dashboard['exports']['csv'] }}">CSV</a>
        <a class="btn" href="{{ $dashboard['exports']['excel'] }}">Excel</a>
        <a class="btn" href="{{ $dashboard['exports']['pdf'] }}" target="_blank">PDF</a>
    </div>
</div>

<div class="card">
    <form class="executive-filter" method="GET" action="/admin/executive-performance">
        <label>
            Period
            <select name="period">
                @foreach(['today' => 'Today', 'yesterday' => 'Yesterday', 'this_week' => 'This Week', 'this_month' => 'This Month', 'last_month' => 'Last Month', 'custom' => 'Custom Date Range'] as $value => $label)
                    <option value="{{ $value }}" {{ $filters['period'] === $value ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </label>
        <label>
            From
            <input type="date" name="date_from" value="{{ $filters['date_from'] }}">
        </label>
        <label>
            To
            <input type="date" name="date_to" value="{{ $filters['date_to'] }}">
        </label>
        <button class="btn" type="submit">Apply Filter</button>
        <a href="/admin/executive-performance">Reset</a>
    </form>
</div>

<div class="executive-section">
    <h2>Today</h2>
    <div class="kpi-grid">
        <div class="kpi-card"><p>Total Orders</p><h2>{{ number_format($today['orders']) }}</h2></div>
        <div class="kpi-card"><p>Total Facebook Spend</p><h2>USD {{ number_format($today['spend_usd'], 2) }}</h2><p>BDT {{ number_format($today['spend_bdt'], 2) }}</p></div>
        <div class="kpi-card"><p>Total Revenue</p><h2>BDT {{ number_format($today['revenue'], 2) }}</h2></div>
        <div class="kpi-card"><p>Estimated Profit</p><h2>BDT {{ number_format($today['profit'], 2) }}</h2></div>
        <div class="kpi-card"><p>Confirmed Payroll Paid</p><h2>BDT {{ number_format($today['payroll_paid'], 2) }}</h2></div>
        <div class="kpi-card"><p>Client Payments Received</p><h2>BDT {{ number_format($today['client_payments_received'], 2) }}</h2></div>
        <div class="kpi-card"><p>Employee Salary Fund Balance</p><h2>BDT {{ number_format($today['salary_fund_balance'], 2) }}</h2></div>
        <div class="kpi-card"><p>Facebook Ads Fund Balance</p><h2>BDT {{ number_format($today['ads_fund_balance'], 2) }}</h2></div>
        <div class="kpi-card"><p>Finance Account Balance</p><h2>BDT {{ number_format($today['finance_account_balance'], 2) }}</h2></div>
        <div class="kpi-card"><p>Pending Approvals</p><h2>{{ number_format($today['pending_approvals']) }}</h2></div>
    </div>
</div>

<div class="executive-section">
    <h2>This Month</h2>
    <div class="kpi-grid">
        <div class="kpi-card"><p>Total Orders</p><h2>{{ number_format($month['orders']) }}</h2></div>
        <div class="kpi-card"><p>Total Spend</p><h2>USD {{ number_format($month['spend_usd'], 2) }}</h2><p>BDT {{ number_format($month['spend_bdt'], 2) }}</p></div>
        <div class="kpi-card"><p>Total Revenue</p><h2>BDT {{ number_format($month['revenue'], 2) }}</h2></div>
        <div class="kpi-card"><p>Estimated Profit</p><h2>BDT {{ number_format($month['profit'], 2) }}</h2></div>
        <div class="kpi-card"><p>Net Profit</p><h2>BDT {{ number_format($month['net_profit'], 2) }}</h2></div>
        <div class="kpi-card"><p>Payroll Paid</p><h2>BDT {{ number_format($month['payroll_paid'], 2) }}</h2></div>
        <div class="kpi-card"><p>Salary Fund Received</p><h2>BDT {{ number_format($month['salary_fund_received'], 2) }}</h2></div>
        <div class="kpi-card"><p>Salary Fund Used</p><h2>BDT {{ number_format($month['salary_fund_used'], 2) }}</h2></div>
        <div class="kpi-card"><p>Ads Fund Received</p><h2>BDT {{ number_format($month['ads_fund_received'], 2) }}</h2></div>
        <div class="kpi-card"><p>Ads Fund Used</p><h2>BDT {{ number_format($month['ads_fund_used'], 2) }}</h2></div>
        <div class="kpi-card"><p>New Clients</p><h2>{{ number_format($month['new_clients']) }}</h2></div>
        <div class="kpi-card"><p>New Employees</p><h2>{{ number_format($month['new_employees']) }}</h2></div>
    </div>
</div>

<div class="executive-section">
    <h2>Finance Summary</h2>
    <div class="mini-grid">
        <div class="kpi-card"><p>Finance Accounts</p><h3>{{ number_format($finance['finance_accounts']) }}</h3></div>
        <div class="kpi-card"><p>Current Balance</p><h3>BDT {{ number_format($finance['finance_account_balance'], 2) }}</h3></div>
        <div class="kpi-card"><p>Binance Balance</p><h3>USD {{ number_format($finance['binance_balance'], 2) }}</h3></div>
        <div class="kpi-card"><p>Facebook Card Balance</p><h3>USD {{ number_format($finance['facebook_card_balance'], 2) }}</h3></div>
        <div class="kpi-card"><p>Salary Fund Balance</p><h3>BDT {{ number_format($finance['salary_fund_balance'], 2) }}</h3></div>
        <div class="kpi-card"><p>Ads Fund Balance</p><h3>BDT {{ number_format($finance['ads_fund_balance'], 2) }}</h3></div>
        <div class="kpi-card"><p>Outstanding Client Due</p><h3>BDT {{ number_format($finance['outstanding_client_due'], 2) }}</h3></div>
        <div class="kpi-card"><p>Outstanding Salary Due</p><h3>BDT {{ number_format($finance['outstanding_salary_due'], 2) }}</h3></div>
    </div>
</div>

<div class="executive-section two-column">
    <div class="card">
        <h2>Client Analytics</h2>
        <div class="table-scroll">
            <table class="compact-table">
                <tr><th>Type</th><th>Client</th><th>Spend</th><th>Profit/Due</th><th>Orders</th></tr>
                @foreach(['highest_spend' => 'Highest Spend', 'highest_profit' => 'Highest Profit', 'highest_due' => 'Highest Due', 'highest_orders' => 'Highest Orders'] as $key => $label)
                    @foreach($dashboard['clients'][$key] as $row)
                        <tr>
                            <td>{{ $label }}</td>
                            <td>{{ $row['client']->company_name ?? '-' }}</td>
                            <td>{{ isset($row['spend']) ? 'USD ' . number_format($row['spend'], 2) : '-' }}</td>
                            <td>{{ isset($row['profit']) ? 'BDT ' . number_format($row['profit'], 2) : 'BDT ' . number_format($row['due'] ?? 0, 2) }}</td>
                            <td>{{ number_format($row['orders'] ?? 0) }}</td>
                        </tr>
                    @endforeach
                @endforeach
            </table>
        </div>
    </div>

    <div class="card">
        <h2>Page Analytics</h2>
        <div class="table-scroll">
            <table class="compact-table">
                <tr><th>Page</th><th>Orders</th><th>Spend</th><th>CPO</th><th>Profit</th></tr>
                @forelse($dashboard['pages']['top'] as $row)
                    <tr>
                        <td>{{ $row['page']->page_name ?? '-' }}</td>
                        <td>{{ number_format($row['orders']) }}</td>
                        <td>USD {{ number_format($row['spend'], 2) }}</td>
                        <td>USD {{ number_format($row['cpo'], 2) }}</td>
                        <td>BDT {{ number_format($row['profit'], 2) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5">No page performance found.</td></tr>
                @endforelse
            </table>
        </div>
    </div>
</div>

<div class="executive-section two-column">
    <div class="card">
        <h2>Employee Analytics</h2>
        <p><strong>Top Moderator:</strong> {{ $employees['top_moderator']['employee']->name ?? '-' }} | {{ number_format($employees['top_moderator']['confirmed_orders'] ?? 0) }} confirmed orders</p>
        <p><strong>Top Ad Manager:</strong> {{ $employees['top_ad_manager']['employee']->name ?? '-' }} | USD {{ number_format($employees['top_ad_manager']['approved_spend'] ?? 0, 2) }}</p>
        <p><strong>Top Performer:</strong> {{ $employees['top_performer']['employee']->name ?? '-' }} | BDT {{ number_format($employees['top_performer']['profit_contribution'] ?? 0, 2) }}</p>
        <p><strong>Lowest Performer:</strong> {{ $employees['lowest_performer']['employee']->name ?? '-' }}</p>
        <p><strong>Approval Rate:</strong> {{ number_format($employees['approval_rate'], 2) }}%</p>
        <p><strong>Average Orders:</strong> {{ number_format($employees['average_orders'], 2) }}</p>
        <p><strong>Average Spend:</strong> USD {{ number_format($employees['average_spend'], 2) }}</p>
    </div>

    <div class="card">
        <h2>Live Alerts</h2>
        <div class="alert-list">
            @foreach([
                'negative_salary_fund' => 'Clients with Negative Salary Fund',
                'negative_ads_fund' => 'Clients with Negative Ads Fund',
                'low_finance_accounts' => 'Finance Accounts Below Threshold',
                'upcoming_salary' => 'Upcoming Salary (5 days)',
                'unpaid_salary' => 'Unpaid Salary',
                'pending_daily_performance_merge' => 'Pending Daily Performance Merge',
                'pending_client_payments' => 'Pending Client Payments',
                'pending_payroll_approval' => 'Pending Payroll Approval',
                'missing_work_status' => 'Missing Work Status',
                'assignment_expired' => 'Assignment Expired',
            ] as $key => $label)
                <div class="kpi-card alert-item {{ ($alerts[$key] ?? 0) > 0 ? 'critical' : '' }}">
                    <p>{{ $label }}</p>
                    <h3>{{ number_format($alerts[$key] ?? 0) }}</h3>
                </div>
            @endforeach
        </div>
    </div>
</div>

<div class="executive-section two-column">
    <div class="card">
        <h2>Trend Charts</h2>
        <div class="trend-row" style="color:var(--cyan);font-weight:700;"><span>Date</span><span>Orders</span><span>Spend</span><span>Revenue</span><span>Profit</span></div>
        @foreach(array_slice($dashboard['trends'], -10) as $row)
            <div class="trend-row">
                <span>{{ $row['date'] }}</span>
                <span>{{ number_format($row['orders']) }}</span>
                <span>USD {{ number_format($row['spend'], 2) }}</span>
                <span>BDT {{ number_format($row['revenue'], 2) }}</span>
                <span>BDT {{ number_format($row['profit'], 2) }}</span>
            </div>
        @endforeach
    </div>

    <div class="card">
        <h2>Recent Activity</h2>
        <p><strong>Latest Client Payment:</strong> {{ $dashboard['recent']['client_payment']?->client?->company_name ?? '-' }}</p>
        <p><strong>Latest Salary Payment:</strong> {{ $dashboard['recent']['salary_payment']?->employee?->name ?? '-' }}</p>
        <p><strong>Latest Payroll:</strong> {{ $dashboard['recent']['payroll']?->employee?->name ?? '-' }}</p>
        <p><strong>Latest Finance Transaction:</strong> {{ $dashboard['recent']['finance_transaction']?->typeLabel() ?? '-' }}</p>
        <p><strong>Latest Daily Performance Merge:</strong> {{ $dashboard['recent']['daily_performance_merge']?->campaign?->client?->company_name ?? '-' }}</p>
        <p><strong>Latest Employee Submission:</strong> {{ $dashboard['recent']['employee_submission']?->employee?->name ?? '-' }}</p>
        <p><strong>Latest Assignment:</strong> {{ $dashboard['recent']['assignment']?->employee?->name ?? '-' }}</p>
    </div>
</div>

<div class="executive-section two-column">
    <div class="card">
        <h2>Quick Actions</h2>
        <div class="executive-toolbar">
            @foreach($dashboard['quick_actions'] as $action)
                <a class="btn" href="{{ $action['url'] }}">{{ $action['label'] }}</a>
            @endforeach
        </div>
    </div>

    <div class="card">
        <h2>Global Search</h2>
        <p><strong>Clients:</strong> {{ $dashboard['search']['clients']->pluck('company_name')->join(', ') ?: '-' }}</p>
        <p><strong>Employees:</strong> {{ $dashboard['search']['employees']->pluck('name')->join(', ') ?: '-' }}</p>
        <p><strong>Pages:</strong> {{ $dashboard['search']['pages']->pluck('page_name')->join(', ') ?: '-' }}</p>
        <p><strong>Campaigns:</strong> {{ $dashboard['search']['campaigns']->pluck('campaign_name')->join(', ') ?: '-' }}</p>
        <p><strong>Finance Accounts:</strong> {{ $dashboard['search']['finance_accounts']->pluck('account_name')->join(', ') ?: '-' }}</p>
    </div>
</div>
@endsection
