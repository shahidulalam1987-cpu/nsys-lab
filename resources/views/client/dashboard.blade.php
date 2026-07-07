@extends('layouts.client')

@section('content')
    <h1>Client Dashboard</h1>

    <p>{{ $client->company_name }} | Today: {{ $today }}</p>

    <div class="card">
        <h3>Employee Salary Fund</h3>
        <div class="stats-grid">
            <div class="stat-card"><p>Received</p><h2>BDT {{ number_format($funds['salary']['received'], 2) }}</h2></div>
            <div class="stat-card"><p>Used</p><h2>BDT {{ number_format($funds['salary']['used'], 2) }}</h2></div>
            <div class="stat-card"><p>Balance</p><h2 style="color:{{ $funds['salary']['balance'] < 0 ? '#ef4444' : '#22c55e' }};">BDT {{ number_format($funds['salary']['balance'], 2) }}</h2></div>
        </div>
        <a class="btn" href="/client/salary-fund">Salary Fund History</a>
    </div>

    <div class="card">
        <h3>Facebook Ads Fund</h3>
        <div class="stats-grid">
            <div class="stat-card"><p>Received</p><h2>BDT {{ number_format($funds['ads']['received'], 2) }}</h2></div>
            <div class="stat-card"><p>Spent</p><h2>BDT {{ number_format($funds['ads']['used'], 2) }}</h2></div>
            <div class="stat-card"><p>Balance</p><h2 style="color:{{ $funds['ads']['balance'] < 0 ? '#ef4444' : '#22c55e' }};">BDT {{ number_format($funds['ads']['balance'], 2) }}</h2></div>
        </div>
        <a class="btn" href="/client/statement">Ads Fund History</a>
    </div>

    @if($availableBalance < 5000)
        <div class="card" style="border-color:#ef4444;">
            <h3 style="color:#ef4444;">Low Balance Warning</h3>
            <p>Your available balance is low. Please submit a payment.</p>
        </div>
    @endif

    <div class="card">
        <h3>Balance Status</h3>

        @php
            $balancePercent = $approvedPayments > 0
                ? max(0, min(100, ($availableBalance / $approvedPayments) * 100))
                : 0;
        @endphp

        <div style="width:100%; height:16px; background:rgba(255,255,255,.10); border-radius:20px; overflow:hidden; border:1px solid rgba(255,255,255,.16);">
            <div style="width:{{ $balancePercent }}%; height:100%; background:{{ $availableBalance < 5000 ? '#ef4444' : '#22c55e' }};"></div>
        </div>

        <p style="margin-top:10px;">
            Used: BDT {{ number_format($totalSpendBdt, 2) }} /
            Paid: BDT {{ number_format($approvedPayments, 2) }}
        </p>

        <p>
            Current Due:
            <strong style="color:#ef4444;">BDT {{ number_format($currentDue, 2) }}</strong>
            <br>
            Available Balance:
            <strong style="color:#22c55e;">BDT {{ number_format($availableBalance, 2) }}</strong>
        </p>
    </div>

    <div class="card">
        <h3>Business Summary</h3>

        <div class="stats-grid">
            <div class="stat-card">
                <p>Total Spend USD</p>
                <h2>${{ number_format($totalSpendUsd, 2) }}</h2>
            </div>

            <div class="stat-card">
                <p>Total Spend BDT</p>
                <h2>BDT {{ number_format($totalSpendBdt, 2) }}</h2>
            </div>

            <div class="stat-card">
                <p>Total Orders</p>
                <h2>{{ number_format($totalOrders) }}</h2>
            </div>

            <div class="stat-card">
                <p>Avg Cost / Order</p>
                <h2>USD {{ number_format($avgCostPerOrder, 2) }}</h2>
            </div>

            <div class="stat-card">
                <p>Total Paid</p>
                <h2 style="color:#22c55e;">BDT {{ number_format($approvedPayments, 2) }}</h2>
            </div>

            <div class="stat-card">
                <p>Pending Payment</p>
                <h2 style="color:#f59e0b;">BDT {{ number_format($pendingPayments, 2) }}</h2>
            </div>

            <div class="stat-card">
                <p>Current Due</p>
                <h2 style="color:#ef4444;">BDT {{ number_format($currentDue, 2) }}</h2>
            </div>

            <div class="stat-card">
                <p>Payment Coverage</p>
                <h2>{{ number_format($paymentCoverage, 1) }}%</h2>
            </div>
        </div>
    </div>

    <div class="card">
        <h3>Last 7 Days Spend & Orders</h3>

        @if($monthlyReports->count())
            <table>
                <tr>
                    <th>Date</th>
                    <th>Spend</th>
                    <th>Orders</th>
                    <th>Spend Bar</th>
                </tr>

                @foreach($monthlyReports as $report)
                    <tr>
                        <td>{{ $report->date }}</td>
                        <td>${{ number_format($report->spend, 2) }}</td>
                        <td>{{ $report->orders }}</td>
                        <td>
                            <div style="width:100%; height:12px; background:rgba(255,255,255,.10); border-radius:20px; overflow:hidden;">
                                <div style="width:{{ min(100, $report->spend * 2) }}%; height:100%; background:linear-gradient(90deg, #2f8cff, #42e8ff);"></div>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </table>
        @else
            <div style="text-align:center; padding:40px; color:#94a3b8;">
                No chart data found.
            </div>
        @endif
    </div>

    <div class="stats-grid">
        <div class="stat-card">
            <p>Today Spend</p>
            <h2>${{ number_format($todaySpend, 2) }}</h2>
        </div>

        <div class="stat-card">
            <p>Today Orders</p>
            <h2>{{ $todayOrders }}</h2>
        </div>

        <div class="stat-card">
            <p>Today Cost Per Order</p>
            <h2>USD {{ number_format($todayCostPerOrder, 2) }}</h2>
        </div>

        <div class="stat-card">
            <p>Approved Payment</p>
            <h2>BDT {{ number_format($approvedPayments, 2) }}</h2>
        </div>

        <div class="stat-card">
            <p>Available Balance</p>
            <h2>BDT {{ number_format($availableBalance, 2) }}</h2>
        </div>
    </div>

    <div class="card">
        <a class="btn" href="/client/payments/create">Submit Payment</a>
        <a class="btn" href="/client/payments">Payment History</a>
        <a class="btn" href="/client/invoices">My Invoices</a>
        <a class="btn" href="/client/statement">Statement</a>
    </div>

    <div class="card">
        <h3>Today Modern Performance</h3>

        @if($todayReports->count())
            <table>
                <tr>
                    <th>Page</th>
                    <th>Campaign</th>
                    <th>Spend</th>
                    <th>Orders</th>
                    <th>Cost Per Order</th>
                </tr>

                @foreach($todayReports as $report)
                    <tr>
                        <td>{{ $report->campaign?->page?->page_name ?: '-' }}</td>
                        <td>{{ $report->campaign?->campaign_name ?: '-' }}</td>
                        <td>USD {{ number_format($report->spend, 2) }}</td>
                        <td>{{ $report->orders }}</td>
                        <td>USD {{ number_format($report->cpp, 2) }}</td>
                    </tr>
                @endforeach
            </table>
        @else
            <div style="text-align:center; padding:40px; color:#94a3b8;">
                No report submitted today.
            </div>
        @endif
    </div>

    <div class="card">
        <h3>Recent Modern Performance History</h3>

        <table>
            <tr>
                <th>Date</th>
                <th>Page</th>
                <th>Campaign</th>
                <th>Spend</th>
                <th>Orders</th>
                <th>Cost Per Order</th>
            </tr>

            @forelse($recentReports as $report)
                <tr>
                    <td>{{ $report->report_date }}</td>
                    <td>{{ $report->campaign?->page?->page_name ?: '-' }}</td>
                    <td>{{ $report->campaign?->campaign_name ?: '-' }}</td>
                    <td>USD {{ number_format($report->spend, 2) }}</td>
                    <td>{{ $report->orders }}</td>
                    <td>USD {{ number_format($report->cpp, 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6">No modern performance reports found.</td>
                </tr>
            @endforelse
        </table>
    </div>

    <div class="stats-grid">
        <div class="card">
            <h3>Page-wise Performance</h3>
            <div class="table-wrap"><table><thead><tr><th>Page</th><th>Spend</th><th>Orders</th><th>CPO</th></tr></thead><tbody>
                @forelse($pagePerformance as $row)<tr><td>{{ $row['label'] }}</td><td>USD {{ number_format($row['spend'], 2) }}</td><td>{{ $row['orders'] }}</td><td>USD {{ number_format($row['cpp'], 2) }}</td></tr>@empty<tr><td colspan="4">No page performance found.</td></tr>@endforelse
            </tbody></table></div>
        </div>
        <div class="card">
            <h3>Campaign-wise Performance</h3>
            <div class="table-wrap"><table><thead><tr><th>Campaign</th><th>Spend</th><th>Orders</th><th>CPO</th></tr></thead><tbody>
                @forelse($campaignPerformance as $row)<tr><td>{{ $row['label'] }}</td><td>USD {{ number_format($row['spend'], 2) }}</td><td>{{ $row['orders'] }}</td><td>USD {{ number_format($row['cpp'], 2) }}</td></tr>@empty<tr><td colspan="4">No campaign performance found.</td></tr>@endforelse
            </tbody></table></div>
        </div>
    </div>

    @if($legacyReports->isNotEmpty())
        <div class="card table-wrap">
            <h3>Legacy Reports</h3>
            <p>Historical reports from the previous reporting system.</p>
            <table><thead><tr><th>Date</th><th>Page</th><th>Spend</th><th>Orders</th></tr></thead><tbody>
                @foreach($legacyReports as $report)<tr><td>{{ $report->report_date }}</td><td>{{ $report->page_name }}</td><td>USD {{ number_format($report->dollar_spend, 2) }}</td><td>{{ $report->orders }}</td></tr>@endforeach
            </tbody></table>
        </div>
    @endif

    <div class="card">
        <h3>Recent Payments</h3>

        <table>
            <tr>
                <th>Invoice No</th>
                <th>Amount</th>
                <th>Method</th>
                <th>Status</th>
                <th>Reject Reason</th>
                <th>Date</th>
            </tr>

            @forelse($recentPayments as $payment)
                <tr>
                    <td>
                        @if($payment->invoice)
                            {{ $payment->invoice->invoice_number }}
                        @else
                            -
                        @endif
                    </td>
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
                    <td>{{ $payment->status === 'rejected' ? $payment->reject_reason : '-' }}</td>
                    <td>{{ $payment->created_at }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6">No payments found.</td>
                </tr>
            @endforelse
        </table>
    </div>
@endsection
