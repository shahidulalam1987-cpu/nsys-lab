@extends('layouts.admin')

@section('content')
    <h1>Admin Dashboard</h1>

    <p>Welcome NSYS Admin | Today: {{ $today }}</p>

    <div class="stats-grid">
        <div class="stat-card">
            <p>Total Clients</p>
            <h2>{{ $totalClients }}</h2>
        </div>

        <div class="stat-card">
            <p>Active Clients</p>
            <h2 style="color:#22c55e;">{{ $activeClients }}</h2>
        </div>

        <div class="stat-card">
            <p>Today Spend</p>
            <h2>${{ number_format($todayDollarSpend, 2) }}</h2>
        </div>

        <div class="stat-card">
            <p>Today Orders</p>
            <h2>{{ $todayOrders }}</h2>
        </div>
    </div>

    <div class="stats-grid">
        <div class="stat-card">
            <p>Approved Payments</p>
            <h2 style="color:#22c55e;">৳{{ number_format($totalApprovedPayments, 2) }}</h2>
        </div>

        <div class="stat-card">
            <p>Pending Payments</p>
            <h2 style="color:#f59e0b;">৳{{ number_format($totalPendingPayments, 2) }}</h2>
        </div>

        <div class="stat-card">
            <p>Total Profit</p>
            <h2 style="color:#22c55e;">৳{{ number_format($totalProfit, 2) }}</h2>
        </div>

        <div class="stat-card">
            <p>Total Balance</p>
            <h2 style="color:{{ $totalBalance >= 0 ? '#22c55e' : '#ef4444' }};">
                @if($totalBalance >= 0)
                    +৳{{ number_format($totalBalance, 2) }}
                @else
                    -৳{{ number_format(abs($totalBalance), 2) }}
                @endif
            </h2>
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
                <td>৳{{ number_format($totalRevenue, 2) }}</td>
                <td>৳{{ number_format($totalCost, 2) }}</td>
            </tr>
        </table>
    </div>

    <div class="card">
        <h2>Quick Actions</h2>

        <a class="btn" href="/admin/daily-reports/create">Add Daily Report</a>
        <a class="btn" href="/admin/payments/pending">Pending Payments</a>
        <a class="btn" href="/admin/clients/create">Add Client</a>
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
                <td>৳{{ number_format($payment->amount, 2) }}</td>
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
    <h2>Recent Daily Reports</h2>

    <table>
        <tr>
            <th>Client</th>
            <th>Date</th>
            <th>Page</th>
            <th>Spend</th>
            <th>Orders</th>
        </tr>

        @forelse($recentReports as $report)
            <tr>
                <td>{{ $report->client->company_name ?? 'N/A' }}</td>
                <td>{{ $report->report_date }}</td>
                <td>{{ $report->page_name }}</td>
                <td>${{ number_format($report->dollar_spend, 2) }}</td>
                <td>{{ $report->orders }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="5">No recent reports found.</td>
            </tr>
        @endforelse
    </table>
</div>
@endsection