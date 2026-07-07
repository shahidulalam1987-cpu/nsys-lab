@extends('layouts.admin')

@section('content')
@php
    $today = $dashboard['today'];
    $month = $dashboard['month'];
    $finance = $dashboard['finance'];
    $filters = $dashboard['filters'];
    $employees = $dashboard['employees'];
    $health = $dashboard['health'];
    $trendLabels = collect($dashboard['trends'])->pluck('date')->values();
    $trendOrders = collect($dashboard['trends'])->pluck('orders')->values();
    $trendSpend = collect($dashboard['trends'])->pluck('spend')->values();
    $trendRevenue = collect($dashboard['trends'])->pluck('revenue')->values();
    $trendProfit = collect($dashboard['trends'])->pluck('profit')->values();
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
    .executive-actions,
    .quick-action-row {
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

    .kpi-grid,
    .snapshot-grid,
    .alert-grid {
        display: grid;
        gap: 12px;
        grid-template-columns: repeat(4, minmax(160px, 1fr));
        margin: 14px 0;
    }

    .kpi-grid {
        grid-template-columns: repeat(5, minmax(150px, 1fr));
    }

    .mini-grid {
        display: grid;
        gap: 12px;
        grid-template-columns: repeat(4, minmax(160px, 1fr));
    }

    .kpi-card,
    .status-card {
        background: rgba(255,255,255,.08);
        border: 1px solid rgba(255,255,255,.16);
        border-radius: 10px;
        color: inherit;
        display: block;
        min-height: 92px;
        padding: 14px;
        text-decoration: none;
    }

    .kpi-card:hover,
    .status-card:hover,
    .search-result:hover {
        border-color: rgba(93, 189, 255, .55);
        transform: translateY(-1px);
    }

    .kpi-card p,
    .status-card p {
        color: var(--muted);
        font-size: 12px;
        margin: 0 0 8px;
    }

    .kpi-card h2,
    .kpi-card h3,
    .status-card h3 {
        margin: 0;
    }

    .tone-positive {
        border-color: rgba(34,197,94,.48);
    }

    .tone-positive h2,
    .tone-positive h3 {
        color: #86efac;
    }

    .tone-negative {
        border-color: rgba(248,113,113,.55);
    }

    .tone-negative h2,
    .tone-negative h3 {
        color: #fca5a5;
    }

    .tone-neutral {
        border-color: rgba(148,163,184,.35);
    }

    .tone-neutral h2,
    .tone-neutral h3 {
        color: #cbd5e1;
    }

    .tone-warning {
        border-color: rgba(251,191,36,.55);
    }

    .tone-warning h2,
    .tone-warning h3 {
        color: #fde68a;
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
        min-width: 760px;
    }

    .compact-table td,
    .compact-table th {
        vertical-align: middle;
    }

    .severity {
        border-radius: 999px;
        display: inline-flex;
        font-size: 11px;
        font-weight: 700;
        padding: 3px 8px;
    }

    .severity.HIGH {
        background: rgba(248,113,113,.18);
        color: #fca5a5;
    }

    .severity.MEDIUM {
        background: rgba(251,191,36,.18);
        color: #fde68a;
    }

    .severity.LOW {
        background: rgba(96,165,250,.16);
        color: #93c5fd;
    }

    .timeline {
        display: grid;
        gap: 10px;
    }

    .timeline-item {
        border-left: 2px solid rgba(93,189,255,.48);
        padding: 0 0 0 12px;
    }

    .timeline-time {
        color: var(--cyan);
        font-size: 12px;
        font-weight: 700;
    }

    .search-groups {
        display: grid;
        gap: 12px;
        grid-template-columns: repeat(2, minmax(180px, 1fr));
    }

    .search-result {
        border: 1px solid rgba(255,255,255,.12);
        border-radius: 8px;
        color: inherit;
        display: block;
        margin-top: 6px;
        padding: 8px 10px;
        text-decoration: none;
    }

    .muted {
        color: var(--muted);
        font-size: 12px;
    }

    .empty-state {
        border: 1px dashed rgba(255,255,255,.18);
        border-radius: 10px;
        color: var(--muted);
        padding: 18px;
        text-align: center;
    }

    .chart-wrap {
        min-height: 320px;
        position: relative;
    }

    .action-icon {
        align-items: center;
        background: rgba(93,189,255,.16);
        border: 1px solid rgba(93,189,255,.25);
        border-radius: 7px;
        color: var(--cyan);
        display: inline-flex;
        font-size: 11px;
        font-weight: 800;
        height: 26px;
        justify-content: center;
        margin-right: 6px;
        min-width: 26px;
        padding: 0 6px;
    }

    @media (max-width: 1180px) {
        .kpi-grid,
        .snapshot-grid,
        .mini-grid,
        .alert-grid {
            grid-template-columns: repeat(2, minmax(150px, 1fr));
        }

        .two-column,
        .search-groups {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 700px) {
        .executive-header {
            display: block;
        }

        .executive-filter,
        .kpi-grid,
        .snapshot-grid,
        .mini-grid,
        .alert-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="executive-header">
    <div>
        <h1>Executive Dashboard</h1>
        <p>Read-only business intelligence overview for agency performance, finance, payroll, client fund, and operational alerts.</p>
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
                @foreach(['today' => 'Today', '7_days' => '7 Days', '30_days' => '30 Days', 'this_month' => 'This Month', 'last_month' => 'Last Month', 'custom' => 'Custom Date Range'] as $value => $label)
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
    <p class="muted">Applied filters: {{ $dashboard['export_meta']['filters'] }}</p>
</div>

<div class="executive-section">
    <h2>Today's Business Snapshot</h2>
    @if($dashboard['snapshot']['has_activity'])
        <div class="snapshot-grid">
            @foreach($dashboard['snapshot']['cards'] as $card)
                <a class="kpi-card tone-{{ $card['tone'] }}" href="{{ $card['url'] }}">
                    <p>{{ $card['label'] }}</p>
                    <h3>{{ $card['display'] }}</h3>
                </a>
            @endforeach
        </div>
    @else
        <div class="empty-state">No business activity today</div>
    @endif
</div>

<div class="executive-section two-column">
    <div class="card">
        <h2>Business Health</h2>
        <a class="status-card tone-{{ $health['tone'] }}" href="/admin/notifications">
            <p>Overall Health</p>
            <h2>{{ $health['status'] }}</h2>
            <p>{{ number_format($health['critical_count']) }} critical checks, {{ number_format($health['warning_count']) }} warning checks</p>
        </a>
        <div class="mini-grid" style="margin-top:12px;">
            @foreach($health['checks'] as $check)
                <div class="kpi-card tone-{{ $check['count'] > 0 ? 'negative' : 'neutral' }}">
                    <p>{{ $check['label'] }}</p>
                    <h3>{{ number_format($check['count']) }}</h3>
                </div>
            @endforeach
        </div>
    </div>

    <div class="card">
        <h2>Clickable KPI Drilldown</h2>
        <div class="mini-grid">
            @foreach($dashboard['kpi_cards'] as $card)
                <a class="kpi-card tone-{{ $card['tone'] }}" href="{{ $card['url'] }}">
                    <p>{{ $card['label'] }}</p>
                    <h3>{{ $card['display'] }}</h3>
                </a>
            @endforeach
        </div>
    </div>
</div>

<div class="executive-section">
    <h2>This Month</h2>
    <div class="kpi-grid">
        <a class="kpi-card tone-positive" href="/admin/executive-performance"><p>Total Orders</p><h2>{{ number_format($month['orders']) }}</h2></a>
        <a class="kpi-card tone-positive" href="/admin/executive-performance"><p>Total Spend</p><h2>USD {{ number_format($month['spend_usd'], 2) }}</h2><p>BDT {{ number_format($month['spend_bdt'], 2) }}</p></a>
        <a class="kpi-card tone-positive" href="/admin/client-fund"><p>Total Revenue</p><h2>BDT {{ number_format($month['revenue'], 2) }}</h2></a>
        <a class="kpi-card tone-{{ $month['profit'] > 0 ? 'positive' : ($month['profit'] < 0 ? 'negative' : 'neutral') }}" href="/admin/facebook-financial/profit-dashboard"><p>Estimated Profit</p><h2>BDT {{ number_format($month['profit'], 2) }}</h2></a>
        <a class="kpi-card tone-{{ $month['net_profit'] > 0 ? 'positive' : ($month['net_profit'] < 0 ? 'negative' : 'neutral') }}" href="/admin/facebook-financial/profit-dashboard"><p>Net Profit</p><h2>BDT {{ number_format($month['net_profit'], 2) }}</h2></a>
        <a class="kpi-card tone-positive" href="/admin/payroll/payment-report"><p>Payroll Paid</p><h2>BDT {{ number_format($month['payroll_paid'], 2) }}</h2></a>
        <a class="kpi-card tone-positive" href="/admin/client-fund"><p>Salary Fund Received</p><h2>BDT {{ number_format($month['salary_fund_received'], 2) }}</h2></a>
        <a class="kpi-card tone-negative" href="/admin/client-fund"><p>Salary Fund Used</p><h2>BDT {{ number_format($month['salary_fund_used'], 2) }}</h2></a>
        <a class="kpi-card tone-positive" href="/admin/client-fund"><p>Ads Fund Received</p><h2>BDT {{ number_format($month['ads_fund_received'], 2) }}</h2></a>
        <a class="kpi-card tone-negative" href="/admin/client-fund"><p>Ads Fund Used</p><h2>BDT {{ number_format($month['ads_fund_used'], 2) }}</h2></a>
    </div>
</div>

<div class="executive-section">
    <h2>Finance Summary</h2>
    <div class="mini-grid">
        <a class="kpi-card tone-neutral" href="/admin/finance/accounts"><p>Finance Accounts</p><h3>{{ number_format($finance['finance_accounts']) }}</h3></a>
        <a class="kpi-card tone-{{ $finance['finance_account_balance'] > 0 ? 'positive' : ($finance['finance_account_balance'] < 0 ? 'negative' : 'neutral') }}" href="/admin/finance/accounts"><p>Current Balance</p><h3>BDT {{ number_format($finance['finance_account_balance'], 2) }}</h3></a>
        <a class="kpi-card tone-{{ $finance['binance_balance'] > 0 ? 'positive' : 'neutral' }}" href="/admin/facebook-financial/funding-dashboard"><p>Binance Balance</p><h3>USD {{ number_format($finance['binance_balance'], 2) }}</h3></a>
        <a class="kpi-card tone-{{ $finance['facebook_card_balance'] > 0 ? 'positive' : 'neutral' }}" href="/admin/facebook-cards"><p>Facebook Card Balance</p><h3>USD {{ number_format($finance['facebook_card_balance'], 2) }}</h3></a>
        <a class="kpi-card tone-{{ $finance['salary_fund_balance'] > 0 ? 'positive' : ($finance['salary_fund_balance'] < 0 ? 'negative' : 'neutral') }}" href="/admin/client-fund"><p>Employee Salary Fund Balance</p><h3>BDT {{ number_format($finance['salary_fund_balance'], 2) }}</h3></a>
        <a class="kpi-card tone-{{ $finance['ads_fund_balance'] > 0 ? 'positive' : ($finance['ads_fund_balance'] < 0 ? 'negative' : 'neutral') }}" href="/admin/client-fund"><p>Facebook Ads Fund Balance</p><h3>BDT {{ number_format($finance['ads_fund_balance'], 2) }}</h3></a>
        <a class="kpi-card tone-{{ $finance['outstanding_client_due'] > 0 ? 'negative' : 'neutral' }}" href="/admin/clients"><p>Outstanding Client Due</p><h3>BDT {{ number_format($finance['outstanding_client_due'], 2) }}</h3></a>
        <a class="kpi-card tone-{{ $finance['outstanding_salary_due'] > 0 ? 'negative' : 'neutral' }}" href="/admin/payroll?status=due"><p>Outstanding Salary Due</p><h3>BDT {{ number_format($finance['outstanding_salary_due'], 2) }}</h3></a>
    </div>
</div>

<div class="executive-section two-column">
    <div class="card">
        <h2>Client Analytics</h2>
        <div class="table-scroll">
            <table class="compact-table">
                <tr><th>Metric</th><th>Client</th><th>Amount</th><th>Trend</th></tr>
                @foreach([
                    'highest_revenue' => 'Highest Revenue Client',
                    'highest_profit' => 'Highest Profit Client',
                    'highest_spend' => 'Highest Spend Client',
                    'highest_due' => 'Highest Due Client',
                    'highest_orders' => 'Highest Orders Client',
                    'highest_growth' => 'Highest Growth Client',
                ] as $key => $label)
                    @forelse($dashboard['clients'][$key] as $row)
                        <tr>
                            <td>{{ $label }}</td>
                            <td>{{ $row['client']->company_name ?? '-' }}</td>
                            <td>
                                @if($key === 'highest_spend')
                                    USD {{ number_format($row['spend'] ?? 0, 2) }}
                                @elseif($key === 'highest_orders')
                                    {{ number_format($row['orders'] ?? 0) }} orders
                                @elseif($key === 'highest_due')
                                    BDT {{ number_format($row['due'] ?? 0, 2) }}
                                @elseif($key === 'highest_growth')
                                    BDT {{ number_format($row['growth'] ?? 0, 2) }}
                                @elseif($key === 'highest_revenue')
                                    BDT {{ number_format($row['revenue'] ?? 0, 2) }}
                                @else
                                    BDT {{ number_format($row['profit'] ?? 0, 2) }}
                                @endif
                            </td>
                            <td>{{ $row['trend'] ?? ($key === 'highest_due' ? 'Needs Review' : 'Stable') }}</td>
                        </tr>
                    @empty
                        <tr><td>{{ $label }}</td><td colspan="3" class="muted">No client analytics found.</td></tr>
                    @endforelse
                @endforeach
            </table>
        </div>
    </div>

    <div class="card">
        <h2>Page Analytics</h2>
        <div class="table-scroll">
            <table class="compact-table">
                <tr><th>Page</th><th>Orders</th><th>Spend</th><th>Revenue</th><th>Profit</th><th>CPO</th><th>ROI</th></tr>
                @forelse($dashboard['pages']['top'] as $row)
                    <tr>
                        <td>{{ $row['page']->page_name ?? '-' }}</td>
                        <td>{{ number_format($row['orders']) }}</td>
                        <td>USD {{ number_format($row['spend'], 2) }}</td>
                        <td>BDT {{ number_format($row['revenue'], 2) }}</td>
                        <td>BDT {{ number_format($row['profit'], 2) }}</td>
                        <td>USD {{ number_format($row['cpo'], 2) }}</td>
                        <td>{{ $row['revenue'] > 0 ? number_format(($row['profit'] / $row['revenue']) * 100, 2) : '0.00' }}%</td>
                    </tr>
                @empty
                    <tr><td colspan="7">No approved Daily Performance Report is available for the selected period.</td></tr>
                @endforelse
            </table>
        </div>
    </div>
</div>

<div class="executive-section two-column">
    <div class="card">
        <h2>Employee Analytics</h2>
        <div class="mini-grid">
            <div class="kpi-card"><p>Top Moderator</p><h3>{{ $employees['top_moderator']['employee']->name ?? '-' }}</h3><p>{{ number_format($employees['top_moderator']['confirmed_orders'] ?? 0) }} orders</p></div>
            <div class="kpi-card"><p>Top Facebook Manager</p><h3>{{ $employees['top_ad_manager']['employee']->name ?? '-' }}</h3><p>USD {{ number_format($employees['top_ad_manager']['approved_spend'] ?? 0, 2) }}</p></div>
            <div class="kpi-card"><p>Top Performer</p><h3>{{ $employees['top_performer']['employee']->name ?? '-' }}</h3><p>BDT {{ number_format($employees['top_performer']['profit_contribution'] ?? 0, 2) }}</p></div>
            <div class="kpi-card"><p>Lowest Performer</p><h3>{{ $employees['lowest_performer']['employee']->name ?? '-' }}</h3><p>{{ number_format($employees['lowest_performer']['approval_rate'] ?? 0, 2) }}% approval</p></div>
            <div class="kpi-card"><p>Approval Rate</p><h3>{{ number_format($employees['approval_rate'], 2) }}%</h3></div>
            <div class="kpi-card"><p>Average Orders</p><h3>{{ number_format($employees['average_orders'], 2) }}</h3></div>
            <div class="kpi-card"><p>Average Spend</p><h3>USD {{ number_format($employees['average_spend'], 2) }}</h3></div>
            <div class="kpi-card"><p>Average Productivity</p><h3>{{ number_format($employees['average_productivity'], 2) }}%</h3></div>
            <a class="kpi-card tone-{{ $employees['pending_reviews'] > 0 ? 'negative' : 'neutral' }}" href="/admin/performance-verification"><p>Pending Reviews</p><h3>{{ number_format($employees['pending_reviews']) }}</h3></a>
        </div>
    </div>

    <div class="card">
        <h2>Live Alerts</h2>
        <div class="alert-grid">
            @foreach($dashboard['alerts'] as $alert)
                <a class="status-card tone-{{ $alert['count'] > 0 ? ($alert['severity'] === 'HIGH' ? 'negative' : 'warning') : 'neutral' }}" href="{{ $alert['url'] }}">
                    <span class="severity {{ $alert['severity'] }}">{{ $alert['severity'] }}</span>
                    <p style="margin-top:8px;">{{ $alert['label'] }}</p>
                    <h3>{{ number_format($alert['count']) }}</h3>
                    <p>{{ $alert['explanation'] }}</p>
                </a>
            @endforeach
        </div>
    </div>
</div>

<div class="executive-section two-column">
    <div class="card">
        <h2>Trend Charts</h2>
        <p class="muted">Orders, spend, revenue, and profit for {{ $filters['label'] }}.</p>
        @if(count($dashboard['trends']))
            <div class="chart-wrap">
                <canvas id="executiveTrendChart"></canvas>
            </div>
            <div class="table-scroll">
                <table class="compact-table">
                    <tr><th>Date</th><th>Orders</th><th>Spend</th><th>Revenue</th><th>Profit</th></tr>
                    @foreach(array_slice($dashboard['trends'], -10) as $row)
                        <tr>
                            <td>{{ $row['date'] }}</td>
                            <td>{{ number_format($row['orders']) }}</td>
                            <td>USD {{ number_format($row['spend'], 2) }}</td>
                            <td>BDT {{ number_format($row['revenue'], 2) }}</td>
                            <td>BDT {{ number_format($row['profit'], 2) }}</td>
                        </tr>
                    @endforeach
                </table>
            </div>
        @else
            <div class="empty-state">No performance available.</div>
        @endif
    </div>

    <div class="card">
        <h2>Recent Activity Timeline</h2>
        @forelse($dashboard['recent'] as $activity)
            <a class="timeline-item search-result" href="{{ $activity['url'] }}">
                <div class="timeline-time">{{ $activity['time']->format('h:i A') }}</div>
                <strong>{{ $activity['label'] }}</strong>
                <div class="muted">{{ $activity['detail'] }}</div>
            </a>
        @empty
            <div class="empty-state">No recent activity.</div>
        @endforelse
    </div>
</div>

<div class="executive-section two-column">
    <div class="card">
        <h2>Quick Actions</h2>
        @foreach($dashboard['quick_actions'] as $group => $actions)
            <h3>{{ $group }}</h3>
            <div class="quick-action-row" style="margin-bottom:12px;">
                @foreach($actions as $action)
                    <a class="btn" href="{{ $action['url'] }}"><span class="action-icon">{{ $action['icon'] }}</span>{{ $action['label'] }}</a>
                @endforeach
            </div>
        @endforeach
    </div>

    <div class="card">
        <h2>Global Search Preview</h2>
        <div class="search-groups">
            <div>
                <h3>Clients</h3>
                @forelse($dashboard['search']['clients'] as $client)
                    <a class="search-result" href="/admin/clients/{{ $client->id }}">{{ $client->company_name }}</a>
                @empty
                    <div class="empty-state">No clients found.</div>
                @endforelse
            </div>
            <div>
                <h3>Employees</h3>
                @forelse($dashboard['search']['employees'] as $employee)
                    <a class="search-result" href="/admin/employees/{{ $employee->id }}">{{ $employee->employee_id }} | {{ $employee->name }}</a>
                @empty
                    <div class="empty-state">No employees found.</div>
                @endforelse
            </div>
            <div>
                <h3>Pages</h3>
                @forelse($dashboard['search']['pages'] as $page)
                    <a class="search-result" href="/admin/client-pages/{{ $page->id }}/edit">{{ $page->page_name }}</a>
                @empty
                    <div class="empty-state">No pages found.</div>
                @endforelse
            </div>
            <div>
                <h3>Campaigns</h3>
                @forelse($dashboard['search']['campaigns'] as $campaign)
                    <a class="search-result" href="/admin/campaigns/{{ $campaign->id }}">{{ $campaign->campaign_name }}<br><span class="muted">{{ $campaign->campaign_id }}</span></a>
                @empty
                    <div class="empty-state">No campaigns found.</div>
                @endforelse
            </div>
            <div>
                <h3>Finance Accounts</h3>
                @forelse($dashboard['search']['finance_accounts'] as $account)
                    <a class="search-result" href="/admin/finance/accounts/{{ $account->id }}/edit">{{ $account->account_name }} <span class="muted">{{ $account->currency }}</span></a>
                @empty
                    <div class="empty-state">No finance accounts found.</div>
                @endforelse
            </div>
            <div>
                <h3>Assignments</h3>
                @forelse($dashboard['search']['assignments'] as $assignment)
                    <a class="search-result" href="/admin/assignments/{{ $assignment->id }}">{{ $assignment->employee?->name ?? 'Employee' }}<br><span class="muted">{{ $assignment->client?->company_name ?? 'No client' }}</span></a>
                @empty
                    <div class="empty-state">No assignments found.</div>
                @endforelse
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const executiveTrendCanvas = document.getElementById('executiveTrendChart');
    if (executiveTrendCanvas && window.Chart) {
        new Chart(executiveTrendCanvas, {
            type: 'line',
            data: {
                labels: @json($trendLabels),
                datasets: [
                    { label: 'Orders', data: @json($trendOrders), borderColor: '#60a5fa', backgroundColor: 'rgba(96,165,250,.12)', tension: .32 },
                    { label: 'Spend', data: @json($trendSpend), borderColor: '#22c55e', backgroundColor: 'rgba(34,197,94,.12)', tension: .32 },
                    { label: 'Revenue', data: @json($trendRevenue), borderColor: '#f59e0b', backgroundColor: 'rgba(245,158,11,.12)', tension: .32 },
                    { label: 'Profit', data: @json($trendProfit), borderColor: '#a78bfa', backgroundColor: 'rgba(167,139,250,.12)', tension: .32 },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { labels: { color: '#e5e7eb' } },
                },
                scales: {
                    x: { ticks: { color: '#94a3b8' }, grid: { color: 'rgba(148,163,184,.16)' } },
                    y: { ticks: { color: '#94a3b8' }, grid: { color: 'rgba(148,163,184,.16)' } },
                },
            },
        });
    }
</script>
@endsection
