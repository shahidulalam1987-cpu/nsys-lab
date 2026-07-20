@extends('layouts.admin')

@section('content')
    <h1>Client Details</h1>

    <a class="btn" href="/admin/clients">Back to Clients</a>
    <a class="btn" href="/admin/clients/{{ $client->id }}/edit">Edit Client</a>
    <a class="btn" href="/admin/daily-reports/create">Add Daily Performance</a>
    <a class="btn" href="/admin/client-fund/{{ $client->id }}/details">Client Fund Ledger</a>
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
        <p><strong>Client Rate:</strong> BDT {{ number_format((float) $client->client_rate, 2) }}</p>
        <p><strong>Buy Rate:</strong> BDT {{ number_format((float) $client->buy_rate, 2) }}</p>
    </div>

    @include('admin.documents.partials.related-widget', [
        'ownerModule' => 'client',
        'ownerId' => $client->id,
        'category' => 'Client',
    ])

    <div class="card">
        <h2>Client Fund Summary</h2>

        <table>
            <tr>
                <th>Salary Received</th>
                <th>Salary Used</th>
                <th>Salary Balance</th>
                <th>Ads Received</th>
                <th>Ads Spent</th>
                <th>Ads Balance</th>
                <th>Combined Balance</th>
                <th>Pending Payments</th>
            </tr>
            <tr>
                <td>BDT {{ number_format($fundSummary['fund_received'], 2) }}</td>
                <td>BDT {{ number_format($fundSummary['salary_used'], 2) }}</td>
                <td>BDT {{ number_format($fundSummary['available_balance'], 2) }}</td>
                <td>BDT {{ number_format($fundSummary['ads_received'], 2) }}</td>
                <td>BDT {{ number_format($fundSummary['ads_spent'], 2) }}</td>
                <td>BDT {{ number_format($fundSummary['ads_balance'], 2) }}</td>
                <td><span class="badge {{ ($fundSummary['combined_balance'] ?? 0) < 0 ? 'badge-danger' : 'badge-success' }}">BDT {{ number_format(abs($fundSummary['combined_balance'] ?? 0), 2) }} {{ ($fundSummary['combined_balance'] ?? 0) < 0 ? 'Due' : 'Available' }}</span></td>
                <td>BDT {{ number_format($fundSummary['pending_payments'], 2) }}</td>
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
        <h2>Performance Summary</h2>
        <div class="stats-grid">
            <div class="stat-card"><p>Total Spend</p><h2>USD {{ number_format($boostingPerformanceSummary['total_spend'], 2) }}</h2></div>
            <div class="stat-card"><p>Total Orders</p><h2>{{ number_format($boostingPerformanceSummary['total_orders']) }}</h2></div>
            <div class="stat-card"><p>Cost Per Order</p><h2>USD {{ number_format($boostingPerformanceSummary['cpp'], 2) }}</h2></div>
            <div class="stat-card"><p>Campaign Count</p><h2>{{ number_format($boostingPerformanceSummary['campaign_count']) }}</h2></div>
        </div>
    </div>

    <div class="card">
        <h2>Client Fund Ledger</h2>

        <table>
            <tr>
                <th>Date</th>
                <th>Fund Movement</th>
                <th>Reference</th>
                <th>Description</th>
                <th>Debit</th>
                <th>Credit</th>
                <th>Running Balance</th>
            </tr>

            @forelse($fundLedger->take(15) as $row)
                <tr>
                    <td>{{ $row['date'] }}</td>
                    <td>{{ $row['type'] }}</td>
                    <td>{{ $row['reference'] ?: '-' }}</td>
                    <td>{{ $row['description'] ?: '-' }}</td>
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
                    <td colspan="6">No client fund ledger entries found.</td>
                </tr>
            @endforelse
        </table>
        <p><a class="btn" href="/admin/client-fund/{{ $client->id }}/details">View Full Ledger</a></p>
    </div>

    <div class="card">
        <h2>Recent Daily Performance</h2>

        <table>
            <tr>
                <th>ID</th>
                <th>Date</th>
                <th>Page</th>
                <th>Campaign</th>
                <th>Spend</th>
                <th>Orders</th>
                <th>Cost Per Order</th>
            </tr>

            @forelse($performanceReports->take(10) as $report)
                <tr>
                    <td>{{ $report->id }}</td>
                    <td>{{ $report->report_date?->toDateString() }}</td>
                    <td>{{ $report->campaign?->page?->page_name ?: '-' }}</td>
                    <td>{{ $report->campaign?->campaign_name ?: '-' }}</td>
                    <td>USD {{ number_format((float) $report->spend, 2) }}</td>
                    <td>{{ $report->orders }}</td>
                    <td>USD {{ number_format((float) $report->cpp, 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7">No daily performance found.</td>
                </tr>
            @endforelse
        </table>
    </div>

    <div class="card">
        <h2>Recent Client Payments</h2>

        <table>
            <tr>
                <th>Receipt</th>
                <th>Purpose</th>
                <th>Amount</th>
                <th>Method</th>
                <th>Transaction ID</th>
                <th>Status</th>
                <th>Reject Reason</th>
                <th>Payment Date</th>
            </tr>

            @forelse($payments->take(10) as $payment)
                <tr>
                    <td><a href="/admin/salary-payments/{{ $payment->id }}">{{ $payment->receiptNumber() }}</a></td>
                    <td>{{ ($payment->fund_type ?? 'employee_salary') === 'facebook_ads' ? 'Ads Fund' : 'Salary Fund' }}</td>
                    <td>BDT {{ number_format($payment->amount, 2) }}</td>
                    <td>{{ $payment->payment_method }}</td>
                    <td>{{ $payment->transaction_id }}</td>
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
                    <td>{{ $payment->salary_month?->toDateString() ?: '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="8">No client payments found.</td>
                </tr>
            @endforelse
        </table>
        <p><a class="btn" href="/admin/salary-payments?client_id={{ $client->id }}">View Payment History</a></p>
    </div>
@endsection
