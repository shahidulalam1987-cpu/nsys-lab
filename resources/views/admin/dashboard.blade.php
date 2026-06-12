@extends('layouts.admin')

@section('content')
    @if(request()->is('admin/facebook-dashboard'))
        <h1>Facebook Dashboard</h1>
        <p>Facebook advertising summary for BM, ad accounts, campaigns, and daily performance.</p>

        <div class="stats-grid">
            <div class="stat-card"><p>Total BM</p><h2>{{ number_format($totalBusinessManagers) }}</h2></div>
            <div class="stat-card"><p>Total Ad Accounts</p><h2>{{ number_format($totalAdAccounts) }}</h2></div>
            <div class="stat-card"><p>Active Campaigns</p><h2>{{ number_format($activeCampaigns) }}</h2></div>
            <div class="stat-card"><p>Today Spend</p><h2>USD {{ number_format($todayPerformanceSpend, 2) }}</h2></div>
            <div class="stat-card"><p>Today Orders</p><h2>{{ number_format($todayPerformanceOrders) }}</h2></div>
            <div class="stat-card"><p>Cost Per Order</p><h2>USD {{ number_format($todayPerformanceCpp, 2) }}</h2></div>
            <div class="stat-card"><p>Payment Issue Accounts</p><h2>{{ number_format($paymentIssueAdAccounts) }}</h2></div>
        </div>

        <div class="card">
            <h2>Quick Actions</h2>
            <a class="btn" href="/admin/business-managers/create">Add BM</a>
            <a class="btn" href="/admin/ad-accounts/create">Add Ad Account</a>
            <a class="btn" href="/admin/campaigns/create">Add Campaign</a>
            <a class="btn" href="/admin/daily-reports/create">Add Daily Performance</a>
            <a class="btn" href="/admin/profit-history">Analytics Dashboard</a>
            <a class="btn" href="/admin/export/daily-reports">Export Reports CSV</a>
        </div>

        <div class="card">
            <h2>Financial Control</h2>
            <table>
                <tr>
                    <th>Total Threshold</th>
                    <th>Remaining Threshold</th>
                    <th>Current Balance</th>
                    <th>Upcoming Billing</th>
                    <th>Critical Accounts</th>
                </tr>
                <tr>
                    <td>USD {{ number_format($totalThreshold, 2) }}</td>
                    <td>USD {{ number_format($remainingThreshold, 2) }}</td>
                    <td>USD {{ number_format($adAccountCurrentBalance, 2) }}</td>
                    <td>{{ number_format($upcomingBillingAccounts) }}</td>
                    <td>{{ number_format($criticalAdAccounts) }}</td>
                </tr>
            </table>
        </div>

        <div class="card">
            <h2>Recent Daily Performance</h2>
            <table>
                <tr>
                    <th>Campaign</th>
                    <th>Date</th>
                    <th>Client</th>
                    <th>Page</th>
                    <th>Spend</th>
                    <th>Orders</th>
                    <th>Cost Per Order</th>
                </tr>
                @forelse($recentPerformanceReports as $report)
                    <tr>
                        <td>{{ $report->campaign?->campaign_name ?: '-' }}</td>
                        <td>{{ $report->report_date?->toDateString() }}</td>
                        <td>{{ $report->campaign?->client?->company_name ?: '-' }}</td>
                        <td>{{ $report->campaign?->page?->page_name ?: '-' }}</td>
                        <td>USD {{ number_format((float) $report->spend, 2) }}</td>
                        <td>{{ number_format($report->orders) }}</td>
                        <td>USD {{ number_format((float) $report->cpp, 2) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7">No recent performance found.</td></tr>
                @endforelse
            </table>
        </div>
    @else
        <h1>Admin Dashboard</h1>
        <p>Overall agency summary | Today: {{ $today }}</p>

        <div class="stats-grid">
            <div class="stat-card"><p>Total Clients</p><h2>{{ number_format($totalClients) }}</h2></div>
            <div class="stat-card"><p>Total Employees</p><h2>{{ number_format($totalEmployees) }}</h2></div>
            <div class="stat-card"><p>Total Facebook Spend</p><h2>USD {{ number_format($totalFacebookSpend, 2) }}</h2></div>
            <div class="stat-card"><p>Total Orders</p><h2>{{ number_format($totalFacebookOrders) }}</h2></div>
            <div class="stat-card"><p>Client Fund Balance</p><h2>BDT {{ number_format($clientFundSummary['available_balance'], 2) }}</h2></div>
            <div class="stat-card"><p>Employee Salary Due</p><h2>BDT {{ number_format($employeeSalaryDue, 2) }}</h2></div>
            <div class="stat-card"><p>System Alerts</p><h2>{{ number_format($systemAlerts) }}</h2></div>
        </div>

        <div class="card">
            <h2>Department Entry Points</h2>
            <a class="btn" href="/admin/facebook-dashboard">Facebook</a>
            <a class="btn" href="/admin/tiktok">TikTok</a>
            <a class="btn" href="/admin/client-dashboard">Client Department</a>
            <a class="btn" href="/admin/employee-dashboard">Employee Department</a>
            <a class="btn" href="/admin/bug-tracker">System Tools</a>
        </div>
    @endif
@endsection
