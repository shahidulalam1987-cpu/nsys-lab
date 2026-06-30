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
        <p><strong>Client Rate:</strong> BDT {{ number_format($summary['client_rate'], 2) }}</p>
        <p><strong>Buy Rate:</strong> BDT {{ number_format($summary['buy_rate'], 2) }}</p>
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
                <th>Current Due</th>
                <th>Available Balance</th>
                <th>Profit</th>
            </tr>
            <tr>
                <td>BDT {{ number_format($summary['total_credit'], 2) }}</td>
                <td>BDT {{ number_format($summary['pending_payment'], 2) }}</td>
                <td>${{ number_format($summary['total_spend_usd'], 2) }}</td>
                <td>BDT {{ number_format($summary['total_revenue'], 2) }}</td>
                <td>BDT {{ number_format($summary['total_cost'], 2) }}</td>
                <td>{{ $summary['total_orders'] }}</td>
                <td><span class="badge badge-danger">BDT {{ number_format($summary['current_due'], 2) }}</span></td>
                <td><span class="badge badge-success">BDT {{ number_format($summary['available_balance'], 2) }}</span></td>
                <td>BDT {{ number_format($summary['profit'], 2) }}</td>
            </tr>
        </table>
    </div>

    <div class="card">
        <h2>Employee Department Summary</h2>

        <div class="stats-grid">
            <div class="stat-card"><p>Total Assigned</p><h2>{{ number_format($employeeSummary['total']) }}</h2></div>
            <div class="stat-card"><p>Active</p><h2>{{ number_format($employeeSummary['active']) }}</h2></div>
            <div class="stat-card"><p>Probation</p><h2>{{ number_format($employeeSummary['probation']) }}</h2></div>
            <div class="stat-card"><p>On Leave</p><h2>{{ number_format($employeeSummary['on_leave']) }}</h2></div>
            <div class="stat-card"><p>Inactive</p><h2>{{ number_format($employeeSummary['inactive']) }}</h2></div>
            <div class="stat-card"><p>Terminated</p><h2>{{ number_format($employeeSummary['terminated']) }}</h2></div>
        </div>

        <div class="table-wrap">
            <table>
                <tr>
                    <th>Employee ID</th>
                    <th>Name</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Assigned From</th>
                    <th>Assigned To</th>
                    <th>Monthly Salary</th>
                </tr>

                @forelse($employeeAssignments as $assignment)
                    <tr>
                        <td>
                            @if($assignment->employee)
                                <a href="/admin/employees/{{ $assignment->employee->id }}">{{ $assignment->employee->employee_id }}</a>
                            @else
                                -
                            @endif
                        </td>
                        <td>{{ $assignment->employee?->name ?: '-' }}</td>
                        <td>{{ $assignment->employee?->roleName() ?: '-' }}</td>
                        <td>{{ $assignment->employee?->statusLabel() ?: '-' }}</td>
                        <td>{{ $assignment->assigned_from?->toDateString() }}</td>
                        <td>{{ $assignment->assigned_to?->toDateString() ?: '-' }}</td>
                        <td>BDT {{ number_format($assignment->employee?->monthly_salary ?? 0, 2) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7">No assigned employees found for this client.</td>
                    </tr>
                @endforelse
            </table>
        </div>
    </div>

    <div class="card">
        <h2>Boosting Performance Summary</h2>
        <div class="stats-grid">
            <div class="stat-card"><p>Total Spend</p><h2>USD {{ number_format($boostingPerformanceSummary['total_spend'], 2) }}</h2></div>
            <div class="stat-card"><p>Total Orders</p><h2>{{ number_format($boostingPerformanceSummary['total_orders']) }}</h2></div>
            <div class="stat-card"><p>Cost Per Order</p><h2>USD {{ number_format($boostingPerformanceSummary['cpp'], 2) }}</h2></div>
            <div class="stat-card"><p>Campaign Count</p><h2>{{ number_format($boostingPerformanceSummary['campaign_count']) }}</h2></div>
        </div>
    </div>

    <div class="card">
        <h2>Ledger</h2>

        <table>
            <tr>
                <th>Date</th>
                <th>Transaction Type</th>
                <th>Page</th>
                <th>Invoice</th>
                <th>Debit</th>
                <th>Credit</th>
                <th>Running Due Balance</th>
            </tr>

            @forelse($ledger['rows'] as $row)
                <tr>
                    <td>{{ $row['date'] }}</td>
                    <td>{{ $row['transaction_type'] }}</td>
                    <td>{{ $row['page'] }}</td>
                    <td>{{ $row['invoice_number'] ?: '-' }}</td>
                    <td>BDT {{ number_format($row['debit'], 2) }}</td>
                    <td>BDT {{ number_format($row['credit'], 2) }}</td>
                    <td>
                        @if($row['running_balance'] >= 0)
                            BDT {{ number_format($row['running_balance'], 2) }}
                        @else
                            -BDT {{ number_format(abs($row['running_balance']), 2) }}
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7">No ledger entries found.</td>
                </tr>
            @endforelse
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
                <th>Invoice</th>
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
                    <td>{{ $payment->invoice?->invoice_number ?? '-' }}</td>
                    <td>BDT {{ number_format($payment->amount, 2) }}</td>
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
                    <td>{{ $payment->status === 'rejected' ? $payment->reject_reason : '-' }}</td>
                    <td>{{ $payment->approved_at ?: $payment->created_at }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="9">No payments found.</td>
                </tr>
            @endforelse
        </table>
    </div>
@endsection
