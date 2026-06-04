@extends('layouts.admin')

@section('content')
    <h1>Client Details</h1>

    <a class="btn" href="/admin/clients">Back to Clients</a>
    <a class="btn" href="/admin/clients/{{ $client->id }}/edit">Edit Client</a>
    <a class="btn" href="/admin/daily-reports/create">Add Report</a>
    <a class="btn" href="/admin/clients/{{ $client->id }}/export-statement">Export Statement CSV</a>
    <a class="btn" href="/admin/clients/{{ $client->id }}/statement-pdf">Download PDF Statement</a>

    <div class="card" style="margin-top:20px;">
        <h2>{{ $client->company_name }}</h2>

        <p><strong>Phone:</strong> {{ $client->phone }}</p>
        <p>
            <strong>Status:</strong>
            @if($client->status == 'active')
                <span class="badge badge-success">Active</span>
            @elseif($client->status == 'pending')
                <span class="badge badge-warning">Pending</span>
            @else
                <span class="badge badge-danger">Inactive</span>
            @endif
        </p>
        <p><strong>Client Rate:</strong> {{ $client->client_rate }}</p>
        <p><strong>Buy Rate:</strong> {{ $client->buy_rate }}</p>
    </div>

    <div class="card">
        <h2>Financial Summary</h2>

        <table>
            <tr>
                <th>Approved Payment</th>
                <th>Pending Payment</th>
                <th>Total Spend USD</th>
                <th>Total Revenue</th>
                <th>Total Cost</th>
                <th>Total Orders</th>
                <th>Balance</th>
                <th>Profit</th>
            </tr>
            <tr>
                <td>৳{{ number_format($approvedPayment, 2) }}</td>
                <td>৳{{ number_format($pendingPayment, 2) }}</td>
                <td>${{ number_format($totalDollarSpend, 2) }}</td>
                <td>৳{{ number_format($totalRevenue, 2) }}</td>
                <td>৳{{ number_format($totalCost, 2) }}</td>
                <td>{{ $totalOrders }}</td>
                <td>
                    @if($balance >= 0)
                        <span class="badge badge-success">+৳{{ number_format($balance, 2) }}</span>
                    @else
                        <span class="badge badge-danger">-৳{{ number_format(abs($balance), 2) }}</span>
                    @endif
                </td>
                <td>৳{{ number_format($totalProfit, 2) }}</td>
            </tr>
        </table>
    </div>

    <div class="card">
        <h2>Recent Daily Reports</h2>

        <table>
            <tr>
                <th>ID</th>
                <th>Date</th>
                <th>Page</th>
                <th>Dollar Spend</th>
                <th>Orders</th>
            </tr>

            @forelse($reports->take(10) as $report)
                <tr>
                    <td>{{ $report->id }}</td>
                    <td>{{ $report->report_date }}</td>
                    <td>{{ $report->page_name }}</td>
                    <td>${{ number_format($report->dollar_spend, 2) }}</td>
                    <td>{{ $report->orders }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5">No reports found.</td>
                </tr>
            @endforelse
        </table>
    </div>

    <div class="card">
        <h2>Recent Payments</h2>

        <table>
            <tr>
                <th>ID</th>
                <th>Amount</th>
                <th>Method</th>
                <th>Transaction ID</th>
                <th>Proof</th>
                <th>Status</th>
                <th>Reject Reason</th>
                <th>Date</th>
            </tr>

            @forelse($payments->take(10) as $payment)
                <tr>
                    <td>{{ $payment->id }}</td>
                    <td>৳{{ number_format($payment->amount, 2) }}</td>
                    <td>{{ $payment->payment_method }}</td>
                    <td>{{ $payment->transaction_id }}</td>
                    <td>
                        @if($payment->screenshot)
                            <a href="{{ asset('storage/' . $payment->screenshot) }}" target="_blank">View Proof</a>
                        @else
                            No Proof
                        @endif
                    </td>
                    <td>
                        @if($payment->status == 'approved')
                            <span class="badge badge-success">Approved</span>
                        @elseif($payment->status == 'pending')
                            <span class="badge badge-warning">Pending</span>
                        @else
                            <span class="badge badge-danger">Rejected</span>
                        @endif
                    </td>
                    <td>
                        @if($payment->status === 'rejected')
                            {{ $payment->reject_reason }}
                        @else
                            -
                        @endif
                    </td>
                    <td>{{ $payment->created_at }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="8">No payments found.</td>
                </tr>
            @endforelse
        </table>
    </div>
@endsection