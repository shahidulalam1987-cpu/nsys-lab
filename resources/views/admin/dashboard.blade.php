@extends('layouts.admin')

@section('content')
    @if(request()->is('admin/facebook-dashboard'))
        <h1>Business Management Dashboard</h1>
        <p>Advertising business summary for business managers, ad accounts, campaigns, and daily performance.</p>

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

        @if(! auth()->user()->isSuperAdmin())
            <div class="card">
                <h2>{{ auth()->user()->primaryRoleName() }} Dashboard</h2>
                <div class="stats-grid">
                    @if(auth()->user()->hasPermission('finance.view'))
                        <a class="stat-card" href="/admin/financial-management" style="text-decoration:none;"><p>Client Fund Available</p><h2>BDT {{ number_format($clientFundSummary['available_balance'], 2) }}</h2></a>
                        <a class="stat-card" href="/admin/facebook-financial/funding-dashboard" style="text-decoration:none;"><p>Total Available USD</p><h2>USD {{ number_format($fundingAlerts['total_available_usd'], 2) }}</h2></a>
                        <a class="stat-card" href="/admin/facebook-financial/profit-dashboard" style="text-decoration:none;"><p>Monthly Profit</p><h2>BDT {{ number_format($usdProfitSummary['monthly_actual_profit'], 2) }}</h2></a>
                        <a class="stat-card" href="/admin/client-fund" style="text-decoration:none;"><p>Client Due</p><h2>BDT {{ number_format($clientFundSummary['unpaid_salary_due'], 2) }}</h2></a>
                    @endif
                    @if(auth()->user()->hasPermission('employees.view'))
                        <a class="stat-card" href="/admin/employees" style="text-decoration:none;"><p>Total Employees</p><h2>{{ number_format($totalEmployees) }}</h2></a>
                        <a class="stat-card" href="/admin/attendance" style="text-decoration:none;"><p>Attendance</p><h2>Monitoring</h2></a>
                        <a class="stat-card" href="/admin/payroll?status=upcoming" style="text-decoration:none;"><p>Upcoming Salary</p><h2>{{ number_format($employeeAlerts['upcoming_count']) }}</h2></a>
                        <a class="stat-card" href="/admin/payroll?status=due" style="text-decoration:none;"><p>Unpaid Salary</p><h2>{{ number_format($employeeAlerts['unpaid_count']) }}</h2></a>
                    @endif
                    @if(auth()->user()->hasPermission('business_management.view'))
                        <a class="stat-card" href="/admin/profit-history" style="text-decoration:none;"><p>Monthly Spend</p><h2>USD {{ number_format($facebookAlerts['monthly_spend'], 2) }}</h2></a>
                        <a class="stat-card" href="/admin/profit-history" style="text-decoration:none;"><p>Total Orders</p><h2>{{ number_format($totalFacebookOrders) }}</h2></a>
                        <a class="stat-card" href="/admin/ad-accounts" style="text-decoration:none;"><p>Billing Alerts</p><h2>{{ number_format($facebookAlerts['upcoming_billing_accounts'] + $facebookAlerts['overdue_billing_accounts']) }}</h2></a>
                        <a class="stat-card" href="/admin/campaigns" style="text-decoration:none;"><p>Campaign Operations</p><h2>Open</h2></a>
                    @endif
                    @if(auth()->user()->hasRole('moderator'))
                        <a class="stat-card" href="/admin/work-status" style="text-decoration:none;"><p>My Work Status</p><h2>Open</h2></a>
                        <a class="stat-card" href="/admin/daily-reports" style="text-decoration:none;"><p>Assigned Daily Reports</p><h2>Open</h2></a>
                    @endif
                </div>
            </div>
        @else

        <div class="card">
            <h2>Action Alerts</h2>
            <div class="stats-grid">
                <a class="stat-card" href="/admin/notifications?priority=critical" style="text-decoration:none;border-color:#ef4444;"><p>Critical Alerts</p><h2>{{ number_format($notificationSummary['critical']) }}</h2></a>
                <a class="stat-card" href="/admin/payroll?status=due" style="text-decoration:none;border-color:#ef4444;"><p>Unpaid Salary</p><h2>{{ number_format($employeeAlerts['unpaid_count']) }}</h2><p>BDT {{ number_format($employeeAlerts['unpaid_amount'], 2) }}</p></a>
                <a class="stat-card" href="/admin/payroll?status=due&employee_scope=terminated" style="text-decoration:none;border-color:#f59e0b;"><p>Final Settlement</p><h2>{{ number_format($employeeAlerts['final_settlement_count']) }}</h2><p>BDT {{ number_format($employeeAlerts['final_settlement_amount'], 2) }}</p></a>
                <a class="stat-card" href="/admin/salary-payments/pending" style="text-decoration:none;"><p>Pending Client Payments</p><h2>{{ number_format($notificationSummary['pending_client_payments']) }}</h2></a>
                <a class="stat-card" href="/admin/facebook-financial/funding-dashboard" style="text-decoration:none;"><p>Low Funding Balance</p><h2>{{ number_format($notificationSummary['low_funding_balance']) }}</h2></a>
                <a class="stat-card" href="/admin/ad-accounts" style="text-decoration:none;"><p>Ad Account Billing Due</p><h2>{{ number_format($notificationSummary['ad_account_billing_due']) }}</h2></a>
                <a class="stat-card" href="/admin/payroll?status=upcoming" style="text-decoration:none;"><p>Upcoming Salary</p><h2>{{ number_format($employeeAlerts['upcoming_count']) }}</h2><p>BDT {{ number_format($employeeAlerts['upcoming_amount'], 2) }}</p></a>
            </div>

            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:14px;margin-top:18px;">
                @foreach(['critical' => 'Critical Alerts', 'warning' => 'Warning Alerts', 'information' => 'Information Alerts'] as $priority => $heading)
                    <div class="card" style="margin:0;background:rgba(255,255,255,.05);">
                        <h3>{{ $heading }}</h3>
                        @forelse($notificationGroups[$priority] as $notification)
                            <p style="margin:10px 0;">
                                <span class="badge {{ $notification->priorityBadgeClass() }}">{{ $notification->priorityLabel() }}</span>
                                <br>
                                <strong>{{ $notification->message }}</strong>
                                <br>
                                <span style="color:var(--muted);">{{ $notification->department }} | {{ $notification->target_team ?: 'Management' }}</span>
                                @if($notification->action_url)
                                    <br><a href="{{ $notification->action_url }}">Open Action</a>
                                @endif
                            </p>
                        @empty
                            <p>No {{ strtolower($heading) }}.</p>
                        @endforelse
                    </div>
                @endforeach
            </div>

            <p><a class="btn" href="/admin/notifications">View All Notifications</a></p>
        </div>

        <div class="card">
            <h2>Agency Snapshot</h2>
            <div class="stats-grid">
                <a class="stat-card" href="/admin/clients" style="text-decoration:none;"><p>Total Clients</p><h2>{{ number_format($totalClients) }}</h2></a>
                <a class="stat-card" href="/admin/employees" style="text-decoration:none;"><p>Total Employees</p><h2>{{ number_format($totalEmployees) }}</h2></a>
                <a class="stat-card" href="/admin/profit-history" style="text-decoration:none;"><p>Today Spend</p><h2>USD {{ number_format($usdProfitSummary['today_usd_spend'], 2) }}</h2></a>
                <a class="stat-card" href="/admin/profit-history" style="text-decoration:none;"><p>Monthly Spend</p><h2>USD {{ number_format($usdProfitSummary['monthly_usd_spend'], 2) }}</h2></a>
                <a class="stat-card" href="/admin/profit-history" style="text-decoration:none;"><p>Total Orders</p><h2>{{ number_format($totalFacebookOrders) }}</h2></a>
                <a class="stat-card" href="/admin/facebook-financial/profit-dashboard" style="text-decoration:none;"><p>Monthly Estimated Profit</p><h2>BDT {{ number_format($usdProfitSummary['monthly_estimated_profit'], 2) }}</h2></a>
                <a class="stat-card" href="/admin/client-fund" style="text-decoration:none;"><p>Client Due</p><h2>BDT {{ number_format($clientFundSummary['unpaid_salary_due'], 2) }}</h2></a>
                <a class="stat-card" href="/admin/facebook-financial/funding-dashboard" style="text-decoration:none;"><p>Total Available USD</p><h2>USD {{ number_format($fundingAlerts['total_available_usd'], 2) }}</h2></a>
            </div>
        </div>

        <div class="card">
            <h2>Department Shortcuts</h2>
            <div class="stats-grid">
                <a class="stat-card" href="/admin/marketing-operations" style="text-decoration:none;"><p>Agency Operations</p><h2>Open</h2></a>
                <a class="stat-card" href="/admin/client-dashboard" style="text-decoration:none;"><p>Clients</p><h2>Open</h2></a>
                <a class="stat-card" href="/admin/employee-dashboard" style="text-decoration:none;"><p>Employees</p><h2>Open</h2></a>
                <a class="stat-card" href="/admin/business-managers" style="text-decoration:none;"><p>Business Management</p><h2>Open</h2></a>
                <a class="stat-card" href="/admin/financial-management" style="text-decoration:none;"><p>Finance</p><h2>Open</h2></a>
                <a class="stat-card" href="/admin/automation" style="text-decoration:none;"><p>System Tools</p><h2>Open</h2></a>
            </div>
        </div>
        @endif
    @endif
@endsection
