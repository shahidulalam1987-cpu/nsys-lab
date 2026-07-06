@extends('layouts.admin')

@section('content')
    <h1>Client Management Dashboard</h1>
    <p>Client management, client portal access, and employee salary fund overview.</p>

    @php
        $clientBalance = $clientFundSummary['available_balance'];
        $isClientDue = $clientBalance < 0;
    @endphp

    <div class="stats-grid">
        <div class="stat-card">
            <p>Clients</p>
            <h2>{{ number_format($totalClients) }}</h2>
        </div>
        <div class="stat-card">
            <p>Total Received</p>
            <h2>BDT {{ number_format($clientFundSummary['total_fund_received'], 2) }}</h2>
        </div>
        <div class="stat-card">
            <p>Pending Payments</p>
            <h2>BDT {{ number_format($pendingClientPayments, 2) }}</h2>
            <p>Pending</p>
        </div>
        <div class="stat-card" style="border-color:{{ $isClientDue ? '#f97316' : '#22c55e' }};">
            <p>Current Client Balance</p>
            <h2 style="color:{{ $isClientDue ? '#fb923c' : '#22c55e' }};">BDT {{ number_format(abs($clientBalance), 2) }}</h2>
            <p style="color:{{ $isClientDue ? '#fb923c' : '#22c55e' }};">{{ $isClientDue ? 'Due' : 'Available' }}</p>
        </div>
    </div>

    <div class="card">
        <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(240px,1fr)); gap:18px;">
            <div>
                <h3 style="margin-top:0;">Clients</h3>
                <div style="display:flex; flex-wrap:wrap; gap:8px;">
                    <a class="btn" href="/admin/clients">Client List</a>
                    <a class="btn" href="/admin/client-users">Client Users</a>
                </div>
            </div>
            <div>
                <h3 style="margin-top:0;">Payments</h3>
                <div style="display:flex; flex-wrap:wrap; gap:8px;">
                    <a class="btn" href="/admin/salary-payments/create">Receive Payment</a>
                    <a class="btn" href="/admin/salary-payments/pending">Pending Payments</a>
                    <a class="btn" href="/admin/salary-payments">Payment History</a>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <h2>Recent Clients</h2>
        <div class="table-wrap">
            <table>
                <tr>
                    <th>Client</th>
                    <th>Status</th>
                    <th>Client Rate (BDT/USD)</th>
                    <th>Action</th>
                </tr>
                @forelse($recentClients as $client)
                    <tr>
                        <td>{{ $client->company_name }}</td>
                        <td>{{ ucfirst($client->status) }}</td>
                        <td>{{ $client->client_rate ? number_format($client->client_rate, 2) : '-' }}</td>
                        <td><a href="/admin/clients/{{ $client->id }}">View Details</a></td>
                    </tr>
                @empty
                    <tr><td colspan="4">No clients found.</td></tr>
                @endforelse
            </table>
        </div>
    </div>

    <div class="card">
        <h2>Recent Client Fund Payments</h2>
        <div class="table-wrap">
            <table>
                <tr>
                    <th>Client</th>
                    <th>Payment Date</th>
                    <th>Amount</th>
                    <th>Status</th>
                </tr>
                @forelse($recentClientPayments as $payment)
                    <tr>
                        <td>{{ $payment->client?->company_name ?: '-' }}</td>
                        <td>{{ $payment->salary_month?->toDateString() ?: '-' }}</td>
                        <td>BDT {{ number_format($payment->amount, 2) }}</td>
                        <td>{{ ucfirst($payment->status) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4">No client fund payments found.</td></tr>
                @endforelse
            </table>
        </div>
    </div>
@endsection
