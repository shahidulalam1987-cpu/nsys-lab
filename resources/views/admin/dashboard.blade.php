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
            <div class="stat-card"><p>Client Assigned Employees</p><h2>{{ number_format($clientAssignedEmployees) }}</h2></div>
            <div class="stat-card"><p>Agency Internal Employees</p><h2>{{ number_format($agencyInternalEmployees) }}</h2></div>
            <div class="stat-card"><p>Total Facebook Spend</p><h2>USD {{ number_format($totalFacebookSpend, 2) }}</h2></div>
            <div class="stat-card"><p>Total Orders</p><h2>{{ number_format($totalFacebookOrders) }}</h2></div>
            <div class="stat-card"><p>Client Fund Balance</p><h2>BDT {{ number_format($clientFundSummary['available_balance'], 2) }}</h2></div>
            <div class="stat-card"><p>Employee Salary Due</p><h2>BDT {{ number_format($employeeSalaryDue, 2) }}</h2></div>
            <div class="stat-card"><p>Facebook Billing Alerts</p><h2>{{ number_format($facebookBillingAlerts) }}</h2></div>
            <div class="stat-card"><p>Card Balance</p><h2>USD {{ number_format($cardAlerts['total_balance'], 2) }}</h2></div>
            <div class="stat-card"><p>Binance Balance</p><h2>USD {{ number_format($fundingAlerts['binance_balance'], 2) }}</h2></div>
            <div class="stat-card"><p>RedotPay Balance</p><h2>USD {{ number_format($fundingAlerts['redotpay_balance'], 2) }}</h2></div>
            <div class="stat-card"><p>Tavao Balance</p><h2>USD {{ number_format($fundingAlerts['tavao_balance'], 2) }}</h2></div>
            <div class="stat-card"><p>Total Available USD</p><h2>USD {{ number_format($fundingAlerts['total_available_usd'], 2) }}</h2></div>
        </div>

        <div class="card">
            <h2>Department Entry Points</h2>
            <a class="btn" href="/admin/facebook-dashboard">Facebook</a>
            <a class="btn" href="/admin/tiktok">TikTok</a>
            <a class="btn" href="/admin/client-dashboard">Client Department</a>
            <a class="btn" href="/admin/employee-dashboard">Employee Department</a>
            <a class="btn" href="/admin/bug-tracker">System Tools</a>
        </div>

        <div class="card">
            <h2>Employee Department Counts</h2>
            <p>
                @forelse($employeeDepartmentCounts as $department => $count)
                    <span class="badge badge-info" style="margin:4px;">{{ $department }}: {{ number_format($count) }}</span>
                @empty
                    No employee department data found.
                @endforelse
            </p>
        </div>

        <div class="card">
            <h2>Agency Alert Center</h2>
            <div class="stats-grid">
                <a class="stat-card" href="/admin/payroll/upcoming" style="text-decoration:none;">
                    <p>Upcoming Salary</p>
                    <h2>{{ number_format($employeeAlerts['upcoming_count']) }}</h2>
                    <p>BDT {{ number_format($employeeAlerts['upcoming_amount'], 2) }}</p>
                </a>
                <a class="stat-card" href="/admin/payroll/unpaid" style="text-decoration:none;">
                    <p>Unpaid Salary</p>
                    <h2>{{ number_format($employeeAlerts['unpaid_count']) }}</h2>
                    <p>BDT {{ number_format($employeeAlerts['unpaid_amount'], 2) }}</p>
                </a>
                <a class="stat-card" href="/admin/client-fund" style="text-decoration:none;">
                    <p>Client Balance</p>
                    <h2>BDT {{ number_format($clientFundSummary['available_balance'], 2) }}</h2>
                    <p>Available Balance</p>
                </a>
                <a class="stat-card" href="/admin/client-fund" style="text-decoration:none;">
                    <p>Client Due</p>
                    <h2>BDT {{ number_format($clientFundSummary['unpaid_salary_due'], 2) }}</h2>
                    <p>{{ number_format($clientFundSummary['unpaid_employee_count']) }} Employees</p>
                </a>
                <a class="stat-card" href="/admin/ad-accounts?billing_status=upcoming" style="text-decoration:none;">
                    <p>Facebook Upcoming Billing</p>
                    <h2>{{ number_format($facebookAlerts['upcoming_billing_accounts']) }}</h2>
                    <p>{{ number_format($facebookAlerts['overdue_billing_accounts']) }} Overdue</p>
                </a>
                <a class="stat-card" href="/admin/profit-history" style="text-decoration:none;">
                    <p>Monthly Facebook Transactions</p>
                    <h2>{{ number_format($facebookAlerts['monthly_transactions']) }}</h2>
                    <p>Spend USD {{ number_format($facebookAlerts['monthly_spend'], 2) }}</p>
                </a>
                <a class="stat-card" href="/admin/facebook-cards" style="text-decoration:none;">
                    <p>Card Balance</p>
                    <h2>USD {{ number_format($cardAlerts['total_balance'], 2) }}</h2>
                    <p>{{ number_format($cardAlerts['low_balance_cards']) }} Low | {{ number_format($cardAlerts['negative_balance_cards']) }} Negative</p>
                </a>
                <a class="stat-card" href="/admin/facebook-financial/funding-dashboard" style="text-decoration:none;">
                    <p>Total Available USD</p>
                    <h2>USD {{ number_format($fundingAlerts['total_available_usd'], 2) }}</h2>
                    <p>Binance, RedotPay, Tavao</p>
                </a>
            </div>
        </div>

        <div class="card">
            <h2>Client Fund Alerts</h2>
            <div class="stats-grid">
                <div class="stat-card"><p>Total Client Fund Received</p><h2>BDT {{ number_format($clientFundSummary['total_fund_received'], 2) }}</h2></div>
                <div class="stat-card"><p>Total Client Spend</p><h2>BDT {{ number_format($clientFundSummary['total_salary_used'], 2) }}</h2></div>
                <div class="stat-card"><p>Total Client Due</p><h2>BDT {{ number_format($clientFundSummary['unpaid_salary_due'], 2) }}</h2></div>
                <div class="stat-card"><p>Available Balance</p><h2>BDT {{ number_format($clientFundSummary['available_balance'], 2) }}</h2></div>
            </div>
            <div class="table-wrap">
                <table>
                    <tr>
                        <th>Client</th>
                        <th>Total Fund</th>
                        <th>Total Spend</th>
                        <th>Balance</th>
                        <th>Due</th>
                    </tr>
                    @forelse($clientFundRows->take(8) as $row)
                        <tr>
                            <td><a href="/admin/client-fund/{{ $row['client']->id }}">{{ $row['client']->company_name }}</a></td>
                            <td>BDT {{ number_format($row['fund_received'], 2) }}</td>
                            <td>BDT {{ number_format($row['salary_used'], 2) }}</td>
                            <td>BDT {{ number_format($row['available_balance'], 2) }}</td>
                            <td>BDT {{ number_format($row['unpaid_salary_due'], 2) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5">No client fund data found.</td></tr>
                    @endforelse
                </table>
            </div>
        </div>

        <div class="card">
            <h2>Facebook Ad Account Alerts</h2>
            <div class="stats-grid">
                <a class="stat-card" href="/admin/ad-accounts?billing_status=upcoming" style="text-decoration:none;"><p>Upcoming Billing Accounts</p><h2>{{ number_format($facebookAlerts['upcoming_billing_accounts']) }}</h2></a>
                <a class="stat-card" href="/admin/ad-accounts?billing_status=overdue" style="text-decoration:none;"><p>Overdue Billing Accounts</p><h2>{{ number_format($facebookAlerts['overdue_billing_accounts']) }}</h2></a>
                <a class="stat-card" href="/admin/ad-accounts?status=payment_issue" style="text-decoration:none;"><p>Payment Issue Accounts</p><h2>{{ number_format($facebookAlerts['payment_issue_accounts']) }}</h2></a>
                <a class="stat-card" href="/admin/ad-accounts?balance_status=low" style="text-decoration:none;"><p>Low Balance Accounts</p><h2>{{ number_format($facebookAlerts['low_balance_accounts']) }}</h2></a>
                <a class="stat-card" href="/admin/ad-accounts?threshold_status=critical" style="text-decoration:none;"><p>Critical Threshold Accounts</p><h2>{{ number_format($facebookAlerts['critical_threshold_accounts']) }}</h2></a>
                <div class="stat-card"><p>Monthly Billing Amount</p><h2>USD {{ number_format($facebookAlerts['monthly_billing_amount'], 2) }}</h2></div>
                <a class="stat-card" href="/admin/facebook-financial/card-transactions" style="text-decoration:none;"><p>High Fee Transactions</p><h2>{{ number_format($cardAlerts['high_fee_transactions']) }}</h2></a>
            </div>
        </div>

        <div class="card">
            <div style="display:flex;justify-content:space-between;gap:16px;align-items:center;flex-wrap:wrap;">
                <h2>Card Balance</h2>
                <a class="btn" href="/admin/facebook-cards">Manage Cards</a>
            </div>
            <div class="table-wrap">
                <table>
                    <tr>
                        <th>Card</th>
                        <th>Last 4</th>
                        <th>Provider</th>
                        <th>Assigned Ad Account</th>
                        <th>Current Balance</th>
                        <th>Status</th>
                    </tr>
                    @forelse($cards->take(8) as $card)
                        <tr>
                            <td><a href="/admin/facebook-cards/{{ $card->id }}">{{ $card->card_name }}</a></td>
                            <td>{{ $card->card_last_four ?: '-' }}</td>
                            <td>{{ $card->provider ?: '-' }}</td>
                            <td>{{ $card->adAccount?->ad_account_name ?: '-' }}</td>
                            <td>USD {{ number_format((float) $card->current_balance, 2) }}</td>
                            <td><span class="badge {{ $card->statusBadgeClass() }}">{{ $card->statusLabel() }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="6">No cards found.</td></tr>
                    @endforelse
                </table>
            </div>
        </div>
    @endif
@endsection
