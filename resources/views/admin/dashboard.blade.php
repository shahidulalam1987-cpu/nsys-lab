@extends('layouts.admin')

@section('content')
    <h1>Admin Dashboard</h1>

    <p>Welcome NSYS Admin | Today: {{ $today }}</p>

    <div class="stats-grid">
        <div class="stat-card">
            <p>Total BM</p>
            <h2>{{ number_format($totalBusinessManagers) }}</h2>
        </div>

        <div class="stat-card">
            <p>Total Ad Accounts</p>
            <h2>{{ number_format($totalAdAccounts) }}</h2>
        </div>

        <div class="stat-card">
            <p>Active Ad Accounts</p>
            <h2>{{ number_format($activeAdAccounts) }}</h2>
        </div>

        <div class="stat-card">
            <p>Payment Issue</p>
            <h2>{{ number_format($paymentIssueAdAccounts) }}</h2>
        </div>
    </div>

    <div class="stats-grid">
        <div class="stat-card">
            <p>Total Threshold</p>
            <h2>USD {{ number_format($totalThreshold, 2) }}</h2>
        </div>

        <div class="stat-card">
            <p>Remaining Threshold</p>
            <h2>USD {{ number_format($remainingThreshold, 2) }}</h2>
        </div>

        <div class="stat-card">
            <p>Current Balance</p>
            <h2>USD {{ number_format($adAccountCurrentBalance, 2) }}</h2>
        </div>

        <div class="stat-card">
            <p>Upcoming Billing</p>
            <h2>{{ number_format($upcomingBillingAccounts) }}</h2>
        </div>

        <div class="stat-card">
            <p>Critical Accounts</p>
            <h2>{{ number_format($criticalAdAccounts) }}</h2>
        </div>
    </div>

    <div class="stats-grid">
        <div class="stat-card">
            <p>Today's Spend</p>
            <h2>USD {{ number_format($todayPerformanceSpend, 2) }}</h2>
            <p>Today</p>
        </div>

        <div class="stat-card">
            <p>Today's Messages</p>
            <h2>{{ number_format($todayPerformanceMessages) }}</h2>
            <p>Today</p>
        </div>

        <div class="stat-card">
            <p>Today's Leads</p>
            <h2>{{ number_format($todayPerformanceLeads) }}</h2>
        </div>

        <div class="stat-card">
            <p>Today's Orders</p>
            <h2>{{ number_format($todayPerformanceOrders) }}</h2>
        </div>
        <div class="stat-card">
            <p>Today's Results</p>
            <h2>{{ number_format($todayPerformanceResults) }}</h2>
        </div>
        <div class="stat-card">
            <p>Today's CPM</p>
            <h2>USD {{ number_format($todayPerformanceCpm, 2) }}</h2>
        </div>
        <div class="stat-card">
            <p>Today's CPL</p>
            <h2>USD {{ number_format($todayPerformanceCpl, 2) }}</h2>
        </div>
        <div class="stat-card">
            <p>Today's CPP</p>
            <h2>USD {{ number_format($todayPerformanceCpp, 2) }}</h2>
        </div>
    </div>

    <div class="card">
        <h2>Business Summary</h2>

        <table>
            <tr>
                <th>Total Dollar Spend</th>
                <th>Total Orders</th>
                <th>Total Revenue</th>
                <th>Total Cost</th>
            </tr>
            <tr>
                <td>${{ number_format($totalDollarSpend, 2) }}</td>
                <td>{{ $totalOrders }}</td>
                <td>BDT {{ number_format($totalRevenue, 2) }}</td>
                <td>BDT {{ number_format($totalCost, 2) }}</td>
            </tr>
        </table>
    </div>

    <div class="card">
        <h2>Quick Actions</h2>

        <a class="btn" href="/admin/business-managers/create">Add BM</a>
        <a class="btn" href="/admin/ad-accounts/create">Add Ad Account</a>
        <a class="btn" href="/admin/campaigns/create">Add Campaign</a>
        <a class="btn" href="/admin/daily-reports/create">Add Daily Performance</a>
        <a class="btn" href="/admin/clients/create">Add Client</a>
        <a class="btn" href="/admin/payments/pending">Pending Payments</a>
        <a class="btn" href="/admin/profit-history">Profit History</a>
        <a class="btn" href="/admin/export/payments">Export Payments CSV</a>
        <a class="btn" href="/admin/export/daily-reports">Export Reports CSV</a>
        <a class="btn" href="/admin/export/profit-history">Export Profit CSV</a>
    </div>

    <div class="card">
        <h2>Recent Payments</h2>

        <table>
            <tr>
                <th>Client</th>
                <th>Amount</th>
                <th>Method</th>
                <th>Status</th>
                <th>Date</th>
            </tr>

            @forelse($recentPayments as $payment)
                <tr>
                    <td>{{ $payment->client->company_name ?? 'N/A' }}</td>
                    <td>BDT {{ number_format($payment->amount, 2) }}</td>
                    <td>{{ $payment->payment_method }}</td>
                    <td>
                        @if($payment->status == 'approved')
                            <span class="badge badge-success">Approved</span>
                        @elseif($payment->status == 'pending')
                            <span class="badge badge-warning">Pending</span>
                        @else
                            <span class="badge badge-danger">Rejected</span>
                        @endif
                    </td>
                    <td>{{ $payment->created_at }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5">No recent payments found.</td>
                </tr>
            @endforelse
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
                <th>Messages</th>
                <th>Leads</th>
                <th>Orders</th>
            </tr>

            @forelse($recentPerformanceReports as $report)
                <tr>
                    <td>{{ $report->campaign?->campaign_name ?: '-' }}</td>
                    <td>{{ $report->report_date?->toDateString() }}</td>
                    <td>{{ $report->campaign?->client?->company_name ?: '-' }}</td>
                    <td>{{ $report->campaign?->page?->page_name ?: '-' }}</td>
                    <td>USD {{ number_format((float) $report->spend, 2) }}</td>
                    <td>{{ number_format($report->messages) }}</td>
                    <td>{{ number_format($report->leads) }}</td>
                    <td>{{ number_format($report->orders) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="8">No recent performance found.</td>
                </tr>
            @endforelse
        </table>
    </div>
@endsection
